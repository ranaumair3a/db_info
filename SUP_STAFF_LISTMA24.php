<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BISE Dera Ghazi Khan - SSC Part-I Result 2024</title>
    <!-- Tailwind CSS CDN -->
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
    <?php
    include_once('config_seci.php'); // Assuming this contains your DB connection

    function escape($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    $rno = 0;
    if (isset($_POST['rno'])) {
        $rno = (int)($_POST['rno']);
    } else if (isset($_POST['update_roll_no'])) {
        $rno = (int)($_POST['update_roll_no']);
    }
    $data = [];

    if ($rno) {
        $rno = escape($rno);
        if (strlen($rno) != 6 || $rno < 100000 || $rno > 360000) {
            echo "<div class='text-red-600 font-bold text-center'>Invalid Roll No.</div>";
            exit();
        }

        $query = "SELECT * FROM NANA2024000_RESULT WHERE RNO = ?";
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $query);
        mysqli_stmt_bind_param($stmt, "i", $rno);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) < 1) {
            echo "<div class='text-red-600 font-bold text-center'>Wrong Roll No</div>";
            exit();
        }

        while ($row = mysqli_fetch_array($result)) {
            $data = $row;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_roll_no'])) {
        // Fetch current data for totals and other fixed fields
        $query = "SELECT * FROM NANA2024000_RESULT WHERE RNO = ?";
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $query);
        mysqli_stmt_bind_param($stmt, "i", $rno);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $current_data = mysqli_fetch_array($result);

        $updatedFields = [
            'NAME' => $_POST['name'] ?? $current_data['NAME'],
            'FATHER' => $_POST['father'] ?? $current_data['FATHER'],
            'INST_NAME' => $_POST['inst_name'] ?? $current_data['INST_NAME'],
            'UR' => (int)($_POST['ur'] ?? $current_data['UR']),
            'ENG' => (int)($_POST['eng'] ?? $current_data['ENG']),
            'ISL' => (int)($_POST['isl'] ?? $current_data['ISL']),
            'THQ' => (int)($_POST['thq'] ?? $current_data['THQ']),
            'PS' => (int)($_POST['ps'] ?? $current_data['PS']),
            'MATHS' => (int)($_POST['maths'] ?? $current_data['MATHS']),
            'MARKS6' => (int)($_POST['marks6'] ?? $current_data['MARKS6']),
            'MARKS7' => (int)($_POST['marks7'] ?? $current_data['MARKS7']),
            'MARKS8' => (int)($_POST['marks8'] ?? $current_data['MARKS8'])
        ];

        // Calculate derived fields
        $subjects = [
            ['obt_key' => 'UR', 'total_key' => 'SUB1_TMARKS', 'per_key' => 'SUB1_PER', 'grade_key' => 'SUB1_GRADE', 'pass_key' => 'PASS1'],
            ['obt_key' => 'ENG', 'total_key' => 'SUB2_TMARKS', 'per_key' => 'SUB2_PER', 'grade_key' => 'SUB2_GRADE', 'pass_key' => 'PASS2'],
            ['obt_key' => 'ISL', 'total_key' => 'SUB3_TMARKS', 'per_key' => 'SUB3_PER', 'grade_key' => 'SUB3_GRADE', 'pass_key' => 'PASS3'],
            ['obt_key' => 'THQ', 'total_key' => 'SUB9_TMARKS', 'per_key' => 'SUB9_PER', 'grade_key' => 'SUB9_GRADE', 'pass_key' => 'PASS9'],
            ['obt_key' => 'PS', 'total_key' => 'SUB4_TMARKS', 'per_key' => 'SUB4_PER', 'grade_key' => 'SUB4_GRADE', 'pass_key' => 'PASS4'],
            ['obt_key' => 'MATHS', 'total_key' => 'SUB5_TMARKS', 'per_key' => 'SUB5_PER', 'grade_key' => 'SUB5_GRADE', 'pass_key' => 'PASS5'],
            ['obt_key' => 'MARKS6', 'total_key' => 'SUB6_TMARKS', 'per_key' => 'SUB6_PER', 'grade_key' => 'SUB6_GRADE', 'pass_key' => 'PASS6'],
            ['obt_key' => 'MARKS7', 'total_key' => 'SUB7_TMARKS', 'per_key' => 'SUB7_PER', 'grade_key' => 'SUB7_GRADE', 'pass_key' => 'PASS7'],
            ['obt_key' => 'MARKS8', 'total_key' => 'SUB8_TMARKS', 'per_key' => 'SUB8_PER', 'grade_key' => 'SUB8_GRADE', 'pass_key' => 'PASS8']
        ];

        $gazres = 0;
        $all_pass = true;
        foreach ($subjects as &$sub) {
            $obt = $updatedFields[$sub['obt_key']];
            $total = (int)$current_data[$sub['total_key']];
            if ($total > 0) {
                $per = ($obt / $total) * 100;
                $updatedFields[$sub['per_key']] = number_format($per, 2);
                if ($per >= 90) $grade = 'A+';
                elseif ($per >= 80) $grade = 'A';
                elseif ($per >= 70) $grade = 'B';
                elseif ($per >= 60) $grade = 'C';
                elseif ($per >= 50) $grade = 'D';
                else $grade = 'F';
                $updatedFields[$sub['grade_key']] = $grade;
                $pass = ($obt >= $total * 0.33) ? 1 : 0;
                $updatedFields[$sub['pass_key']] = $pass;
                $gazres += $obt;
                if ($pass == 0) $all_pass = false;
            } else {
                $updatedFields[$sub['per_key']] = 0;
                $updatedFields[$sub['grade_key']] = '';
                $updatedFields[$sub['pass_key']] = '';
            }
        }
        $updatedFields['GAZRES'] = $gazres;
        $updatedFields['PASS'] = $all_pass ? 1 : 0;

        // Update query with all fields
        $updateQuery = "UPDATE NANA2024000_RESULT SET 
            NAME = ?, FATHER = ?, INST_NAME = ?,
            UR = ?, SUB1_PER = ?, SUB1_GRADE = ?, PASS1 = ?,
            ENG = ?, SUB2_PER = ?, SUB2_GRADE = ?, PASS2 = ?,
            ISL = ?, SUB3_PER = ?, SUB3_GRADE = ?, PASS3 = ?,
            THQ = ?, SUB9_PER = ?, SUB9_GRADE = ?, PASS9 = ?,
            PS = ?, SUB4_PER = ?, SUB4_GRADE = ?, PASS4 = ?,
            MATHS = ?, SUB5_PER = ?, SUB5_GRADE = ?, PASS5 = ?,
            MARKS6 = ?, SUB6_PER = ?, SUB6_GRADE = ?, PASS6 = ?,
            MARKS7 = ?, SUB7_PER = ?, SUB7_GRADE = ?, PASS7 = ?,
            MARKS8 = ?, SUB8_PER = ?, SUB8_GRADE = ?, PASS8 = ?,
            GAZRES = ?, PASS = ?
            WHERE RNO = ?";

        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $updateQuery);
        mysqli_stmt_bind_param($stmt, "sss" .
            "idsi" . "idsi" . "idsi" . "idsi" . "idsi" . "idsi" . "idsi" . "idsi" . "idsi" .
            "ii",
            $updatedFields['NAME'], $updatedFields['FATHER'], $updatedFields['INST_NAME'],
            $updatedFields['UR'], $updatedFields['SUB1_PER'], $updatedFields['SUB1_GRADE'], $updatedFields['PASS1'],
            $updatedFields['ENG'], $updatedFields['SUB2_PER'], $updatedFields['SUB2_GRADE'], $updatedFields['PASS2'],
            $updatedFields['ISL'], $updatedFields['SUB3_PER'], $updatedFields['SUB3_GRADE'], $updatedFields['PASS3'],
            $updatedFields['THQ'], $updatedFields['SUB9_PER'], $updatedFields['SUB9_GRADE'], $updatedFields['PASS9'],
            $updatedFields['PS'], $updatedFields['SUB4_PER'], $updatedFields['SUB4_GRADE'], $updatedFields['PASS4'],
            $updatedFields['MATHS'], $updatedFields['SUB5_PER'], $updatedFields['SUB5_GRADE'], $updatedFields['PASS5'],
            $updatedFields['MARKS6'], $updatedFields['SUB6_PER'], $updatedFields['SUB6_GRADE'], $updatedFields['PASS6'],
            $updatedFields['MARKS7'], $updatedFields['SUB7_PER'], $updatedFields['SUB7_GRADE'], $updatedFields['PASS7'],
            $updatedFields['MARKS8'], $updatedFields['SUB8_PER'], $updatedFields['SUB8_GRADE'], $updatedFields['PASS8'],
            $updatedFields['GAZRES'], $updatedFields['PASS'],
            $rno
        );
        mysqli_stmt_execute($stmt);

        echo "<script>alert('Result updated successfully!');</script>";
        // Refresh data after update
        $query = "SELECT * FROM NANA2024000_RESULT WHERE RNO = ?";
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $query);
        mysqli_stmt_bind_param($stmt, "i", $rno);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $data = mysqli_fetch_array($result);
    }
    ?>

    <script>
        function maxLengthCheck(object) {
            if (object.value.length > object.maxLength) object.value = object.value.slice(0, object.maxLength);
        }

        function disableclick(event) {
            if (event.button == 2) { alert("Right Click Disabled"); return false; }
        }
        document.onmousedown = disableclick;

        function toggleEdit() {
            const inputs = document.querySelectorAll('.editable');
            const editButton = document.getElementById('editButton');
            const updateButton = document.getElementById('updateButton');
            inputs.forEach(input => input.disabled = !input.disabled);
            if (editButton.textContent === 'Edit') {
                editButton.textContent = 'Cancel';
                updateButton.classList.remove('hidden');
                document.querySelectorAll('.marks-input').forEach(input => {
                    input.addEventListener('input', calculateUpdates);
                });
            } else {
                editButton.textContent = 'Edit';
                updateButton.classList.add('hidden');
                document.querySelectorAll('.marks-input').forEach(input => {
                    input.removeEventListener('input', calculateUpdates);
                });
                location.reload(); // Reset to original if cancel
            }
        }

        function calculateUpdates() {
            const marksInputs = document.querySelectorAll('.marks-input');
            let totalMarksObtained = 0;
            let totalMarksPossible = 0;

            marksInputs.forEach(input => {
                const row = input.closest('tr');
                const totalMarks = parseInt(row.querySelector('td:nth-child(2)').textContent) || 0;
                const marksObtained = parseInt(input.value) || 0;
                totalMarksObtained += marksObtained;
                totalMarksPossible += totalMarks;

                const percentile = totalMarks > 0 ? (marksObtained / totalMarks) * 100 : 0;
                row.querySelector('td:nth-child(4)').textContent = percentile.toFixed(2);

                let grade = '';
                if (percentile >= 90) grade = 'A+';
                else if (percentile >= 80) grade = 'A';
                else if (percentile >= 70) grade = 'B';
                else if (percentile >= 60) grade = 'C';
                else if (percentile >= 50) grade = 'D';
                else grade = 'F';
                row.querySelector('td:nth-child(5)').textContent = grade;

                const remarks = (marksObtained >= totalMarks * 0.33) ? 'PASS' : '';
                row.querySelector('td:nth-child(6)').textContent = remarks;
            });

            document.getElementById('totalMarks').textContent = totalMarksPossible;
            document.querySelector('tr.bg-gray-200 td:nth-child(3)').textContent = totalMarksObtained;
        }

        function updateResult() {
            const formData = new FormData();
            formData.append('update_roll_no', '<?php echo $rno; ?>');
            // Append marks
            document.querySelectorAll('.marks-input').forEach(input => {
                formData.append(input.name, input.value);
            });
            // Append name, father, inst_name
            formData.append('name', document.querySelector('input[name="name"]').value);
            formData.append('father', document.querySelector('input[name="father"]').value);
            formData.append('inst_name', document.querySelector('input[name="inst_name"]').value);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            }).then(response => {
                location.reload(); // Reload to reflect updated data
            }).catch(error => {
                alert('Error updating result.');
            });
        }
    </script>

    <div class="container mx-auto p-4 max-w-4xl">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800">BOARD OF INTERMEDIATE AND SECONDARY EDUCATION DERA GHAZI KHAN</h1>
            <h2 class="text-3xl font-semibold text-blue-900 mt-2">Online Result For SSC (Part-I) 1<sup>st</sup> Annual 2024</h2>
        </div>

        <div class="bg-white shadow-md rounded-lg p-6 mx-auto max-w-lg no-print">
            <h3 class="text-xl font-semibold text-center mb-4">Search By Roll No</h3>
            <form method="post" name="slip" action="" class="space-y-4">
                <div class="flex items-center space-x-4">
                    <label class="text-lg font-semibold">Enter Your Roll No</label>
                    <input type="number" maxlength="6" name="rno" value="" placeholder="100001-200000 or 300000-360000"
                           oninput="maxLengthCheck(this)" class="border rounded-lg p-2 w-full focus:outline-none focus:ring-2 focus:ring-blue-500"/>
                </div>
                <div class="text-center">
                    <input type="submit" value="Search for Result" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700"/>
                </div>
            </form>
        </div>

        <?php if ($rno && $data): ?>
            <div class="bg-white shadow-md rounded-lg p-6 mt-8">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-4">
                        <img src="images/logo.gif" alt="Logo" class="w-24 h-24">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800">Board Of Intermediate & Secondary Education, Dera Ghazi Khan</h2>
                            <p class="text-lg">Roll No: <span class="font-bold"><?php echo trim($data['RNO']); ?></span></p>
                            <p class="text-lg">PROVISIONAL RESULT INTIMATION</p>
                            <p class="text-lg">Sr. No: <span class="font-bold"><?php echo trim($data['SRNO']); ?></span></p>
                        </div>
                    </div>
                    <p class="text-lg font-bold">Reg No: <?php echo trim($data['REGNO']); ?></p>
                </div>
                <p class="text-base">SECONDARY SCHOOL CERTIFICATE (PART-I) 1<sup>st</sup> ANNUAL EXAMINATION 2024</p>
                <p class="text-base font-bold">GROUP: <?php echo trim($data['S_GROUP']) == 'S' ? 'SCIENCE' : (trim($data['S_GROUP']) == 'G' ? 'GENERAL' : 'DEAF & DUMB'); ?></p>

                <div class="mt-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-lg font-semibold">Candidate's Name:</label>
                            <input type="text" name="name" value="<?php echo trim($data['NAME']); ?>" class="editable border rounded-lg p-2 w-full bg-gray-50" disabled>
                        </div>
                        <div>
                            <label class="text-lg font-semibold">Father's Name:</label>
                            <input type="text" name="father" value="<?php echo trim($data['FATHER']); ?>" class="editable border rounded-lg p-2 w-full bg-gray-50" disabled>
                        </div>
                        <div>
                            <label class="text-lg font-semibold">Institute/District:</label>
                            <input type="text" name="inst_name" value="<?php echo trim($data['INST_NAME']); ?>" class="editable border rounded-lg p-2 w-full bg-gray-50" disabled>
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex space-x-4 no-print">
                    <button id="editButton" onclick="toggleEdit()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Edit</button>
                    <button id="updateButton" onclick="updateResult()" class="hidden bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">Update</button>
                </div>

                <div class="mt-6 overflow-x-auto">
                    <table class="w-full border-collapse">
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
                            <?php if ((int)$data['SUB1_TMARKS'] > 0): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="border p-3 text-lg uppercase">Urdu</td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB1_TMARKS']); ?></td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" name="ur" value="<?php echo trim($data['UR']); ?>" class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB1_PER']); ?></td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB1_GRADE']); ?></td>
                                <td class="border p-3 text-center"><?php echo trim($data['PASS1']) == '1' ? 'PASS' : ''; ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ((int)$data['SUB2_TMARKS'] > 0): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="border p-3 text-lg uppercase">English</td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB2_TMARKS']); ?></td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" name="eng" value="<?php echo trim($data['ENG']); ?>" class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB2_PER']); ?></td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB2_GRADE']); ?></td>
                                <td class="border p-3 text-center"><?php echo trim($data['PASS2']) == '1' ? 'PASS' : ''; ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ((int)$data['SUB3_TMARKS'] > 0 && trim($data['APPEAR3'])): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="border p-3 text-lg uppercase"><?php echo trim($data['APPEAR3']); ?></td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB3_TMARKS']); ?></td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" name="isl" value="<?php echo trim($data['ISL']); ?>" class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB3_PER']); ?></td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB3_GRADE']); ?></td>
                                <td class="border p-3 text-center"><?php echo trim($data['PASS3']) == '1' ? 'PASS' : ''; ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ((int)$data['SUB9_TMARKS'] > 0 && trim($data['APPEAR9'])): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="border p-3 text-lg uppercase"><?php echo trim($data['APPEAR9']); ?></td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB9_TMARKS']); ?></td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" name="thq" value="<?php echo trim($data['THQ']); ?>" class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB9_PER']); ?></td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB9_GRADE']); ?></td>
                                <td class="border p-3 text-center"><?php echo trim($data['PASS9']) == '1' ? 'PASS' : ''; ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ((int)$data['SUB4_TMARKS'] > 0): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="border p-3 text-lg uppercase">Pakistan Studies</td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB4_TMARKS']); ?></td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" name="ps" value="<?php echo trim($data['PS']); ?>" class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB4_PER']); ?></td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB4_GRADE']); ?></td>
                                <td class="border p-3 text-center"><?php echo trim($data['PASS4']) == '1' ? 'PASS' : ''; ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ((int)$data['SUB5_TMARKS'] > 0 && trim($data['APPEAR5'])): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="border p-3 text-lg uppercase"><?php echo trim($data['APPEAR5']); ?></td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB5_TMARKS']); ?></td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" name="maths" value="<?php echo trim($data['MATHS']); ?>" class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB5_PER']); ?></td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB5_GRADE']); ?></td>
                                <td class="border p-3 text-center"><?php echo trim($data['PASS5']) == '1' ? 'PASS' : ''; ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ((int)$data['SUB6_TMARKS'] > 0 && trim($data['APPEAR6'])): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="border p-3 text-lg uppercase"><?php echo trim($data['APPEAR6']); ?></td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB6_TMARKS']); ?></td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" name="marks6" value="<?php echo trim($data['MARKS6']); ?>" class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB6_PER']); ?></td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB6_GRADE']); ?></td>
                                <td class="border p-3 text-center"><?php echo trim($data['PASS6']) == '1' ? 'PASS' : ''; ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ((int)$data['SUB7_TMARKS'] > 0 && trim($data['APPEAR7'])): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="border p-3 text-lg uppercase"><?php echo trim($data['APPEAR7']); ?></td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB7_TMARKS']); ?></td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" name="marks7" value="<?php echo trim($data['MARKS7']); ?>" class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB7_PER']); ?></td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB7_GRADE']); ?></td>
                                <td class="border p-3 text-center"><?php echo trim($data['PASS7']) == '1' ? 'PASS' : ''; ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ((int)$data['SUB8_TMARKS'] > 0 && trim($data['APPEAR8'])): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="border p-3 text-lg uppercase"><?php echo trim($data['APPEAR8']); ?></td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB8_TMARKS']); ?></td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" name="marks8" value="<?php echo trim($data['MARKS8']); ?>" class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB8_PER']); ?></td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB8_GRADE']); ?></td>
                                <td class="border p-3 text-center"><?php echo trim($data['PASS8']) == '1' ? 'PASS' : ''; ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr class="bg-gray-200">
                                <td class="border p-3 text-lg font-bold">Result</td>
                                <td class="border p-3 text-center font-bold">
                                    <span id="totalMarks"><?php
                                        $GTOTAL = (int)trim($data['SUB1_TMARKS']) + (int)trim($data['SUB2_TMARKS']) + (int)trim($data['SUB3_TMARKS']) + (int)trim($data['SUB9_TMARKS']) + (int)trim($data['SUB4_TMARKS']) + (int)trim($data['SUB5_TMARKS']) + (int)trim($data['SUB6_TMARKS']) + (int)trim($data['SUB7_TMARKS']) + (int)trim($data['SUB8_TMARKS']);
                                        echo $GTOTAL;
                                    ?></span>
                                </td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['GAZRES']); ?></td>
                                <td class="border p-3"></td>
                                <td class="border p-3"></td>
                                <td class="border p-3"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    <p class="text-base font-bold">NOTE 1: The marks awarded are the best prediction of the performance & has been awarded under COVID-19 Examination Policy, hence considered as valid and fair.</p>
                    <p class="text-base font-bold">2: Errors and omissions are excepted. For any query send email at <a href="mailto:bise786@gmail.com" class="text-blue-600">bise786@gmail.com</a>.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
