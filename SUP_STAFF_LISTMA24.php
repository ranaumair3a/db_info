<?php
// index.php
declare(strict_types=1);
mb_internal_encoding('UTF-8');

include_once('config_seci.php'); // $con (mysqli)

function esc(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function grade_from_percent(float $p): string {
    if ($p >= 90) return 'A+';
    if ($p >= 80) return 'A';
    if ($p >= 70) return 'B';
    if ($p >= 60) return 'C';
    if ($p >= 50) return 'D';
    return 'F';
}
function pass_from_scores(int $obt, int $total): int {
    if ($total <= 0) return 0;
    return ($obt * 100.0 / $total) >= 33.0 ? 1 : 0; // 33% rule
}

// ---- Resolve Roll No (GET/POST) ----
$rno = 0;
if (isset($_POST['rno'])) {
    $rno = (int)$_POST['rno'];
} elseif (isset($_POST['update_roll_no'])) {
    $rno = (int)$_POST['update_roll_no'];
} elseif (isset($_GET['rno'])) {
    $rno = (int)$_GET['rno'];
}

$data = [];
if ($rno) {
    // Validate roll range
    if (strlen((string)$rno) !== 6 || $rno < 100000 || $rno > 360000) {
        http_response_code(400);
        $errorText = "Invalid Roll No. (must be 6 digits in allowed range)";
    } else {
        $stmt = mysqli_stmt_init($con);
        $query = "SELECT * FROM NANA2024000_RESULT WHERE RNO = ?";
        mysqli_stmt_prepare($stmt, $query);
        mysqli_stmt_bind_param($stmt, "i", $rno);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($result && mysqli_num_rows($result) >= 1) {
            $data = mysqli_fetch_assoc($result);
        } else {
            $errorText = "Wrong Roll No.";
        }
    }
}

// ---- Handle update (AJAX fetch/POST) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_roll_no'])) {
    if (!$data) {
        // Re-fetch when coming directly to POST
        $stmt = mysqli_stmt_init($con);
        $query = "SELECT * FROM NANA2024000_RESULT WHERE RNO = ?";
        mysqli_stmt_prepare($stmt, $query);
        mysqli_stmt_bind_param($stmt, "i", $rno);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $data = $result ? mysqli_fetch_assoc($result) : [];
        if (!$data) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'msg' => 'Roll not found']);
            exit;
        }
    }

    // Map subject slots to DB columns
    // 1: Urdu (fixed name) -> UR
    // 2: English (fixed name) -> ENG
    // 3: APPEAR3 -> ISL
    // 4: Pakistan Studies (fixed name) -> PS
    // 5: APPEAR5 -> MATHS
    // 6: APPEAR6 -> MARKS6
    // 7: APPEAR7 -> MARKS7
    // 8: APPEAR8 -> MARKS8
    // 9: APPEAR9 -> THQ
    $subjects = [
        1 => ['name' => 'Urdu',               'mark_col' => 'UR',     'tmarks_col' => 'SUB1_TMARKS', 'per_col' => 'SUB1_PER', 'grade_col' => 'SUB1_GRADE', 'pass_col' => 'PASS1'],
        2 => ['name' => 'English',            'mark_col' => 'ENG',    'tmarks_col' => 'SUB2_TMARKS', 'per_col' => 'SUB2_PER', 'grade_col' => 'SUB2_GRADE', 'pass_col' => 'PASS2'],
        3 => ['name' => trim($data['APPEAR3'] ?? 'Subject 3'), 'mark_col' => 'ISL',    'tmarks_col' => 'SUB3_TMARKS', 'per_col' => 'SUB3_PER', 'grade_col' => 'SUB3_GRADE', 'pass_col' => 'PASS3'],
        4 => ['name' => 'Pakistan Studies',   'mark_col' => 'PS',     'tmarks_col' => 'SUB4_TMARKS', 'per_col' => 'SUB4_PER', 'grade_col' => 'SUB4_GRADE', 'pass_col' => 'PASS4'],
        5 => ['name' => trim($data['APPEAR5'] ?? 'Subject 5'), 'mark_col' => 'MATHS', 'tmarks_col' => 'SUB5_TMARKS', 'per_col' => 'SUB5_PER', 'grade_col' => 'SUB5_GRADE', 'pass_col' => 'PASS5'],
        6 => ['name' => trim($data['APPEAR6'] ?? 'Subject 6'), 'mark_col' => 'MARKS6','tmarks_col' => 'SUB6_TMARKS', 'per_col' => 'SUB6_PER', 'grade_col' => 'SUB6_GRADE', 'pass_col' => 'PASS6'],
        7 => ['name' => trim($data['APPEAR7'] ?? 'Subject 7'), 'mark_col' => 'MARKS7','tmarks_col' => 'SUB7_TMARKS', 'per_col' => 'SUB7_PER', 'grade_col' => 'SUB7_GRADE', 'pass_col' => 'PASS7'],
        8 => ['name' => trim($data['APPEAR8'] ?? 'Subject 8'), 'mark_col' => 'MARKS8','tmarks_col' => 'SUB8_TMARKS', 'per_col' => 'SUB8_PER', 'grade_col' => 'SUB8_GRADE', 'pass_col' => 'PASS8'],
        9 => ['name' => trim($data['APPEAR9'] ?? 'Subject 9'), 'mark_col' => 'THQ',   'tmarks_col' => 'SUB9_TMARKS', 'per_col' => 'SUB9_PER', 'grade_col' => 'SUB9_GRADE', 'pass_col' => 'PASS9'],
    ];

    // Collect incoming numbers (only sanitize numeric)
    $incoming = [];
    foreach ($subjects as $idx => $def) {
        $key = strtolower($def['mark_col']); // input name matches lower-case mark_col
        if (isset($_POST[$key]) && $_POST[$key] !== '') {
            $val = (int)$_POST[$key];
        } else {
            $val = (int)($data[$def['mark_col']] ?? 0);
        }
        $incoming[$def['mark_col']] = max(0, $val); // no negatives
    }

    // Compute per/grade/pass for each subject & total obtained
    $sumObtained = 0;
    $allPass = 1;
    $updateFields = [];   // column => value
    foreach ($subjects as $idx => $def) {
        $obt = (int)$incoming[$def['mark_col']];
        $tot = (int)($data[$def['tmarks_col']] ?? 0);
        $sumObtained += $obt;
        $percent = ($tot > 0) ? round(($obt * 100.0) / $tot, 2) : 0.0;
        $grade = grade_from_percent($percent);
        $pass  = pass_from_scores($obt, $tot);
        $allPass = $allPass && $pass;

        $updateFields[$def['mark_col']]  = $obt;               // e.g., UR, ENG, ...
        $updateFields[$def['per_col']]   = (string)$percent;   // SUBx_PER (string/decimal column)
        $updateFields[$def['grade_col']] = $grade;             // SUBx_GRADE
        $updateFields[$def['pass_col']]  = $pass;              // PASSx (0/1)
    }

    // GAZRES (total obtained) & PASS (overall)
    $updateFields['GAZRES'] = $sumObtained;
    $updateFields['PASS']   = (int)$allPass;

    // Build dynamic UPDATE safely
    // e.g., UPDATE ... SET UR=?, SUB1_PER=?, SUB1_GRADE=?, PASS1=?, ... , GAZRES=?, PASS=? WHERE RNO=?
    $cols = array_keys($updateFields);
    $placeholders = implode('=?, ', $cols) . '=?';
    $types = ''; $values = [];
    foreach ($cols as $c) {
        // Guess type: integers vs strings/decimals (per/grade)
        if (preg_match('/^(UR|ENG|ISL|THQ|PS|MATHS|MARKS6|MARKS7|MARKS8|PASS\d+|PASS|GAZRES)$/', $c)) {
            $types .= 'i';
            $values[] = (int)$updateFields[$c];
        } else {
            // PER and GRADE are stored as string/decimal/varchar
            $types .= 's';
            $values[] = (string)$updateFields[$c];
        }
    }
    $types .= 'i';
    $values[] = $rno;

    mysqli_begin_transaction($con);
    try {
        $sql = "UPDATE NANA2024000_RESULT SET " . $placeholders . " WHERE RNO = ?";
        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$values);
        mysqli_stmt_execute($stmt);
        mysqli_commit($con);

        // Respond JSON for fetch() caller
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['ok' => true, 'msg' => 'Result updated successfully!', 'total_obtained' => $sumObtained, 'overall_pass' => (int)$allPass]);
    } catch (Throwable $e) {
        mysqli_rollback($con);
        http_response_code(500);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['ok' => false, 'msg' => 'Update failed']);
    }
    exit;
}

// Helper to human group label
function group_label($s) {
    $s = trim($s);
    if ($s === 'S') return 'SCIENCE';
    if ($s === 'G') return 'GENERAL';
    if ($s === 'D') return 'DEAF & DUMB';
    return $s;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BISE Dera Ghazi Khan - SSC Part-I Result 2024</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
@media print {
  @page { margin-top: 0; margin-bottom: 0; }
  body { padding-top: 72px; padding-bottom: 72px; }
  .no-print { display: none; }
}
</style>
</head>
<body class="bg-gray-100 font-sans">
<div class="container mx-auto p-4 max-w-4xl">
  <div class="text-center mb-8">
    <h1 class="text-2xl font-bold text-gray-800">BOARD OF INTERMEDIATE AND SECONDARY EDUCATION DERA GHAZI KHAN</h1>
    <h2 class="text-3xl font-semibold text-blue-900 mt-2">Online Result For SSC (Part-I) 1<sup>st</sup> Annual 2024</h2>
  </div>

  <div class="bg-white shadow-md rounded-lg p-6 mx-auto max-w-lg no-print">
    <h3 class="text-xl font-semibold text-center mb-4">Search By Roll No</h3>
    <form method="post" action="" class="space-y-4">
      <div class="flex items-center space-x-4">
        <label class="text-lg font-semibold">Enter Your Roll No</label>
        <input type="number" maxlength="6" name="rno" value="<?= $rno ? esc((string)$rno) : '' ?>" placeholder="100000-200000 or 300000-360000"
               oninput="if(this.value.length>6)this.value=this.value.slice(0,6)" class="border rounded-lg p-2 w-full focus:outline-none focus:ring-2 focus:ring-blue-500"/>
      </div>
      <div class="text-center">
        <input type="submit" value="Search for Result" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700"/>
      </div>
    </form>
    <?php if (!empty($errorText)): ?>
      <p class="mt-3 text-center text-red-600 font-semibold"><?= esc($errorText) ?></p>
    <?php endif; ?>
  </div>

  <?php if ($rno && $data): ?>
    <?php
      $subjects = [
          1 => ['name' => 'Urdu',               'mark_col' => 'UR',     'tmarks_col' => 'SUB1_TMARKS', 'per_col' => 'SUB1_PER', 'grade_col' => 'SUB1_GRADE', 'pass_col' => 'PASS1'],
          2 => ['name' => 'English',            'mark_col' => 'ENG',    'tmarks_col' => 'SUB2_TMARKS', 'per_col' => 'SUB2_PER', 'grade_col' => 'SUB2_GRADE', 'pass_col' => 'PASS2'],
          3 => ['name' => trim($data['APPEAR3'] ?? 'Subject 3'), 'mark_col' => 'ISL',    'tmarks_col' => 'SUB3_TMARKS', 'per_col' => 'SUB3_PER', 'grade_col' => 'SUB3_GRADE', 'pass_col' => 'PASS3'],
          4 => ['name' => 'Pakistan Studies',   'mark_col' => 'PS',     'tmarks_col' => 'SUB4_TMARKS', 'per_col' => 'SUB4_PER', 'grade_col' => 'SUB4_GRADE', 'pass_col' => 'PASS4'],
          5 => ['name' => trim($data['APPEAR5'] ?? 'Subject 5'), 'mark_col' => 'MATHS', 'tmarks_col' => 'SUB5_TMARKS', 'per_col' => 'SUB5_PER', 'grade_col' => 'SUB5_GRADE', 'pass_col' => 'PASS5'],
          6 => ['name' => trim($data['APPEAR6'] ?? 'Subject 6'), 'mark_col' => 'MARKS6','tmarks_col' => 'SUB6_TMARKS', 'per_col' => 'SUB6_PER', 'grade_col' => 'SUB6_GRADE', 'pass_col' => 'PASS6'],
          7 => ['name' => trim($data['APPEAR7'] ?? 'Subject 7'), 'mark_col' => 'MARKS7','tmarks_col' => 'SUB7_TMARKS', 'per_col' => 'SUB7_PER', 'grade_col' => 'SUB7_GRADE', 'pass_col' => 'PASS7'],
          8 => ['name' => trim($data['APPEAR8'] ?? 'Subject 8'), 'mark_col' => 'MARKS8','tmarks_col' => 'SUB8_TMARKS', 'per_col' => 'SUB8_PER', 'grade_col' => 'SUB8_GRADE', 'pass_col' => 'PASS8'],
          9 => ['name' => trim($data['APPEAR9'] ?? 'Subject 9'), 'mark_col' => 'THQ',   'tmarks_col' => 'SUB9_TMARKS', 'per_col' => 'SUB9_PER', 'grade_col' => 'SUB9_GRADE', 'pass_col' => 'PASS9'],
      ];
      $group = group_label($data['S_GROUP'] ?? '');
      $totalPossible = 0;
      foreach ($subjects as $s) $totalPossible += (int)($data[$s['tmarks_col']] ?? 0);
      $totalObtained = (int)($data['GAZRES'] ?? 0);
    ?>
    <div class="bg-white shadow-md rounded-lg p-6 mt-8">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center space-x-4">
          <img src="images/logo.gif" alt="Logo" class="w-24 h-24">
          <div>
            <h2 class="text-2xl font-bold text-gray-800">Board Of Intermediate & Secondary Education, Dera Ghazi Khan</h2>
            <p class="text-lg">Roll No: <span class="font-bold"><?= esc((string)$data['RNO']) ?></span></p>
            <p class="text-lg">PROVISIONAL RESULT INTIMATION</p>
            <p class="text-lg">Sr. No: <span class="font-bold"><?= esc((string)$data['SRNO']) ?></span></p>
          </div>
        </div>
        <p class="text-lg font-bold">Reg No: <?= esc((string)$data['REGNO']) ?></p>
      </div>
      <p class="text-base">SECONDARY SCHOOL CERTIFICATE (PART-I) 1<sup>st</sup> ANNUAL EXAMINATION 2024</p>
      <p class="text-base font-bold">GROUP: <?= esc($group) ?></p>

      <div class="mt-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="text-lg font-semibold">Candidate's Name:</label>
            <input type="text" value="<?= esc((string)$data['NAME']) ?>" class="editable border rounded-lg p-2 w-full bg-gray-50" disabled>
          </div>
          <div>
            <label class="text-lg font-semibold">Father's Name:</label>
            <input type="text" value="<?= esc((string)$data['FATHER']) ?>" class="editable border rounded-lg p-2 w-full bg-gray-50" disabled>
          </div>
          <div>
            <label class="text-lg font-semibold">Institute/District:</label>
            <input type="text" value="<?= esc((string)$data['INST_NAME']) ?>" class="editable border rounded-lg p-2 w-full bg-gray-50" disabled>
          </div>
        </div>
      </div>

      <div class="mt-4 flex space-x-4 no-print">
        <button id="editButton" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Edit</button>
        <button id="updateButton" class="hidden bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">Update</button>
      </div>

      <div class="mt-6 overflow-x-auto">
        <table class="w-full border-collapse" id="resultTable">
          <thead>
            <tr class="bg-teal-200">
              <th class="border p-3 text-lg font-bold">NAME OF SUBJECT</th>
              <th class="border p-3 text-lg font-bold">TOTAL MARKS</th>
              <th class="border p-3 text-lg font-bold">MARKS OBTAINED</th>
              <th class="border p-3 text-lg font-bold">PERCENTILE SCORE</th>
              <th class="border p-3 text-lg font-bold">RELATIVE GRADE</th>
              <th class="border p-3 text-lg font-bold">REMARKS</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($subjects as $idx => $s): 
                $t = (int)($data[$s['tmarks_col']] ?? 0);
                $o = (int)($data[$s['mark_col']] ?? 0);
                $per = (string)($data[$s['per_col']] ?? '');
                $grade = (string)($data[$s['grade_col']] ?? '');
                $pass = (string)($data[$s['pass_col']] ?? '');
                $remarks = ($pass === '1') ? 'PASS' : '';
                $inputName = strtolower($s['mark_col']); // input name we will POST
            ?>
            <tr class="hover:bg-gray-50">
              <td class="border p-3 text-lg uppercase"><?= esc($s['name']) ?></td>
              <td class="border p-3 text-center font-bold total-col"><?= esc((string)$t) ?></td>
              <td class="border p-3 text-center font-bold">
                <input type="number"
                       name="<?= esc($inputName) ?>"
                       value="<?= esc((string)$o) ?>"
                       class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50"
                       disabled>
              </td>
              <td class="border p-3 text-center font-bold percent-col"><?= esc($per) ?></td>
              <td class="border p-3 text-center font-bold grade-col"><?= esc($grade) ?></td>
              <td class="border p-3 text-center remarks-col"><?= esc($remarks) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="bg-gray-200">
              <td class="border p-3 text-lg font-bold">Result</td>
              <td class="border p-3 text-center font-bold"><span id="totalMarks"><?= esc((string)$totalPossible) ?></span></td>
              <td class="border p-3 text-center font-bold"><span id="totalObtained"><?= esc((string)$totalObtained) ?></span></td>
              <td class="border p-3"></td>
              <td class="border p-3"></td>
              <td class="border p-3"></td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="mt-6">
        <p class="text-base font-bold">1. This result is a notice only. Errors and omissions are excepted. This computer generated result has no legal status. For any query send email at <a href="mailto:bise786@gmail.com" class="text-blue-600">bise786@gmail.com</a>.</p>
      </div>
    </div>
  <?php endif; ?>
</div>

<script>
(function(){
  const editBtn = document.getElementById('editButton');
  const updateBtn = document.getElementById('updateButton');
  if (!editBtn) return;

  function toGrade(p){
    p = Number(p);
    if (p >= 90) return 'A+';
    if (p >= 80) return 'A';
    if (p >= 70) return 'B';
    if (p >= 60) return 'C';
    if (p >= 50) return 'D';
    return 'F';
  }
  function isPass(obt, total){
    if (!total) return false;
    return (obt * 100 / total) >= 33.0;
  }

  function recalcAll(){
    const rows = document.querySelectorAll('#resultTable tbody tr');
    let totalObt = 0;
    rows.forEach((tr) => {
      if (tr.classList.contains('bg-gray-200')) return; // skip summary
      const totalCell = tr.querySelector('.total-col');
      const input = tr.querySelector('input.marks-input');
      const percentCell = tr.querySelector('.percent-col');
      const gradeCell = tr.querySelector('.grade-col');
      const remarksCell = tr.querySelector('.remarks-col');
      const total = parseInt(totalCell?.textContent || '0', 10) || 0;
      const obt = parseInt(input?.value || '0', 10) || 0;
      totalObt += obt;

      let p = 0;
      if (total > 0) p = (obt * 100) / total;
      percentCell.textContent = p.toFixed(2);
      gradeCell.textContent = toGrade(p);
      remarksCell.textContent = isPass(obt, total) ? 'PASS' : '';
    });
    const totalObtainedEl = document.getElementById('totalObtained');
    if (totalObtainedEl) totalObtainedEl.textContent = String(totalObt);
  }

  function toggleEdit(){
    const inputs = document.querySelectorAll('.editable');
    const marks = document.querySelectorAll('.marks-input');
    const isEditing = (editBtn.textContent.trim() === 'Edit');
    inputs.forEach(inp => { inp.disabled = !isEditing ? true : (inp.classList.contains('marks-input') ? false : true); });
    marks.forEach(inp => { inp.disabled = !isEditing ? true : false; });
    if (isEditing) {
      editBtn.textContent = 'Cancel';
      updateBtn.classList.remove('hidden');
      marks.forEach(inp => inp.addEventListener('input', recalcAll));
    } else {
      editBtn.textContent = 'Edit';
      updateBtn.classList.add('hidden');
      marks.forEach(inp => inp.removeEventListener('input', recalcAll));
      recalcAll(); // ensure consistent
    }
  }

  async function doUpdate(){
    const formData = new FormData();
    formData.append('update_roll_no', '<?= esc((string)$rno) ?>');
    // append all marks inputs using their name (ur, eng, isl, thq, ps, maths, marks6..8)
    document.querySelectorAll('input.marks-input').forEach(input => {
      formData.append(input.name, input.value);
    });

    updateBtn.disabled = true;
    try {
      const res = await fetch(window.location.href, { method: 'POST', body: formData });
      const json = await res.json();
      if (json && json.ok) {
        alert('Result updated successfully!');
        // Keep UI in sync (already recalculated), but ensure summary obtained matches server echo (optional):
        if (json.total_obtained !== undefined) {
          const totalObtainedEl = document.getElementById('totalObtained');
          if (totalObtainedEl) totalObtainedEl.textContent = String(json.total_obtained);
        }
        // Switch back to view mode
        editBtn.click();
      } else {
        alert((json && json.msg) ? json.msg : 'Error updating result.');
      }
    } catch (e) {
      alert('Error updating result.');
    } finally {
      updateBtn.disabled = false;
    }
  }

  editBtn.addEventListener('click', toggleEdit);
  updateBtn.addEventListener('click', doUpdate);
})();
</script>
</body>
</html>
