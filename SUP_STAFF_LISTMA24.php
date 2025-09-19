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

    function get_grade($per) {
        if ($per >= 90) return 'A+';
        else if ($per >= 80) return 'A';
        else if ($per >= 70) return 'B';
        else if ($per >= 60) return 'C';
        else if ($per >= 50) return 'D+';
        else if ($per >= 40) return 'D';
        else if ($per >= 33) return 'E';
        else return 'F';
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
        $updatedName = escape($_POST['name'] ?? $data['NAME']);
        $updatedFather = escape($_POST['father'] ?? $data['FATHER']);
        $updatedInst = escape($_POST['inst_name'] ?? $data['INST_NAME']);

        $updatedMarks = [
            'ur' => (int)($_POST['ur'] ?? $data['UR']),
            'eng' => (int)($_POST['eng'] ?? $data['ENG']),
            'isl' => (int)($_POST['isl'] ?? $data['ISL']),
            'thq' => (int)($_POST['thq'] ?? $data['THQ']),
            'ps' => (int)($_POST['ps'] ?? $data['PS']),
            'maths' => (int)($_POST['maths'] ?? $data['MATHS']),
            'marks6' => (int)($_POST['marks6'] ?? $data['MARKS6']),
            'marks7' => (int)($_POST['marks7'] ?? $data['MARKS7']),
            'marks8' => (int)($_POST['marks8'] ?? $data['MARKS8'])
        ];

        $updatedPers = [
            'sub1_per' => (float)($_POST['sub1_per'] ?? $data['SUB1_PER']),
            'sub2_per' => (float)($_POST['sub2_per'] ?? $data['SUB2_PER']),
            'sub3_per' => (float)($_POST['sub3_per'] ?? $data['SUB3_PER']),
            'sub9_per' => (float)($_POST['sub9_per'] ?? $data['SUB9_PER']),
            'sub4_per' => (float)($_POST['sub4_per'] ?? $data['SUB4_PER']),
            'sub5_per' => (float)($_POST['sub5_per'] ?? $data['SUB5_PER']),
            'sub6_per' => (float)($_POST['sub6_per'] ?? $data['SUB6_PER']),
            'sub7_per' => (float)($_POST['sub7_per'] ?? $data['SUB7_PER']),
            'sub8_per' => (float)($_POST['sub8_per'] ?? $data['SUB8_PER'])
        ];

        $updatedGrades = [
            'sub1_grade' => escape($_POST['sub1_grade'] ?? $data['SUB1_GRADE']),
            'sub2_grade' => escape($_POST['sub2_grade'] ?? $data['SUB2_GRADE']),
            'sub3_grade' => escape($_POST['sub3_grade'] ?? $data['SUB3_GRADE']),
            'sub9_grade' => escape($_POST['sub9_grade'] ?? $data['SUB9_GRADE']),
            'sub4_grade' => escape($_POST['sub4_grade'] ?? $data['SUB4_GRADE']),
            'sub5_grade' => escape($_POST['sub5_grade'] ?? $data['SUB5_GRADE']),
            'sub6_grade' => escape($_POST['sub6_grade'] ?? $data['SUB6_GRADE']),
            'sub7_grade' => escape($_POST['sub7_grade'] ?? $data['SUB7_GRADE']),
            'sub8_grade' => escape($_POST['sub8_grade'] ?? $data['SUB8_GRADE'])
        ];

        $updatedPasses = [
            'pass1' => ($_POST['pass1'] == 'PASS' ? 1 : 0),
            'pass2' => ($_POST['pass2'] == 'PASS' ? 1 : 0),
            'pass3' => ($_POST['pass3'] == 'PASS' ? 1 : 0),
            'pass9' => ($_POST['pass9'] == 'PASS' ? 1 : 0),
            'pass4' => ($_POST['pass4'] == 'PASS' ? 1 : 0),
            'pass5' => ($_POST['pass5'] == 'PASS' ? 1 : 0),
            'pass6' => ($_POST['pass6'] == 'PASS' ? 1 : 0),
            'pass7' => ($_POST['pass7'] == 'PASS' ? 1 : 0),
            'pass8' => ($_POST['pass8'] == 'PASS' ? 1 : 0)
        ];

        $gazres = array_sum($updatedMarks);

        $all_pass = min(...array_values($updatedPasses));

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
        mysqli_stmt_bind_param($stmt, "sss d s i  d s i  d s i  d s i  d s i  d s i  d s i  d s i  d s i i i",
            $updatedName, $updatedFather, $updatedInst,
            $updatedMarks['ur'], $updatedPers['sub1_per'], $updatedGrades['sub1_grade'], $updatedPasses['pass1'],
            $updatedMarks['eng'], $updatedPers['sub2_per'], $updatedGrades['sub2_grade'], $updatedPasses['pass2'],
            $updatedMarks['isl'], $updatedPers['sub3_per'], $updatedGrades['sub3_grade'], $updatedPasses['pass3'],
            $updatedMarks['thq'], $updatedPers['sub9_per'], $updatedGrades['sub9_grade'], $updatedPasses['pass9'],
            $updatedMarks['ps'], $updatedPers['sub4_per'], $updatedGrades['sub4_grade'], $updatedPasses['pass4'],
            $updatedMarks['maths'], $updatedPers['sub5_per'], $updatedGrades['sub5_grade'], $updatedPasses['pass5'],
            $updatedMarks['marks6'], $updatedPers['sub6_per'], $updatedGrades['sub6_grade'], $updatedPasses['pass6'],
            $updatedMarks['marks7'], $updatedPers['sub7_per'], $updatedGrades['sub7_grade'], $updatedPasses['pass7'],
            $updatedMarks['marks8'], $updatedPers['sub8_per'], $updatedGrades['sub8_grade'], $updatedPasses['pass8'],
            $gazres, $all_pass, $rno
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
                row.querySelector('td:nth-child(4) input').value = percentile.toFixed(2);

                let grade = '';
                if (percentile >= 90) grade = 'A+';
                else if (percentile >= 80) grade = 'A';
                else if (percentile >= 70) grade = 'B';
                else if (percentile >= 60) grade = 'C';
                else if (percentile >= 50) grade = 'D+';
                else if (percentile >= 40) grade = 'D';
                else if (percentile >= 33) grade = 'E';
                else grade = 'F';
                row.querySelector('td:nth-child(5) input').value = grade;

                let remarks = (percentile >= 33) ? 'PASS' : '';
                row.querySelector('td:nth-child(6) input').value = remarks;
            });

            document.getElementById('totalMarks').textContent = totalMarksPossible;
            document.getElementById('obtainedMarks').textContent = totalMarksObtained;
        }

        function updateResult() {
            const formData = new FormData();
            formData.append('update_roll_no', '<?php echo $rno; ?>');
            formData.append('name', document.querySelector('input[name="name"]').value);
            formData.append('father', document.querySelector('input[name="father"]').value);
            formData.append('inst_name', document.querySelector('input[name="inst_name"]').value);
            document.querySelectorAll('.marks-input, .per-input, .grade-input, .remarks-input').forEach(input => {
                formData.append(input.name, input.value);
            });

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
            <h1 class="text-2xl font-bold text-gray-800">BOARD OFF INTERMEDIATE AND SECONDARY EDUCATION DERA GHAZI KHAN</h1>
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
                            <tr class="hover:bg-gray-50">
                                <td class="border p-3 text-lg uppercase">Urdu</td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB1_TMARKS']); ?></td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" name="ur" value="<?php echo trim($data['UR']); ?>" class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" step="0.01" name="sub1_per" value="<?php echo number_format($data['SUB1_PER'], 2); ?>" class="per-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="text" name="sub1_grade" value="<?php echo trim($data['SUB1_GRADE']); ?>" class="grade-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center">
                                    <input type="text" name="pass1" value="<?php echo trim($data['PASS1']) == '1' ? 'PASS' : ''; ?>" class="remarks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="border p-3 text-lg uppercase">English</td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB2_TMARKS']); ?></td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" name="eng" value="<?php echo trim($data['ENG']); ?>" class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" step="0.01" name="sub2_per" value="<?php echo number_format($data['SUB2_PER'], 2); ?>" class="per-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="text" name="sub2_grade" value="<?php echo trim($data['SUB2_GRADE']); ?>" class="grade-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center">
                                    <input type="text" name="pass2" value="<?php echo trim($data['PASS2']) == '1' ? 'PASS' : ''; ?>" class="remarks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="border p-3 text-lg uppercase"><?php echo trim($data['APPEAR3']); ?></td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB3_TMARKS']); ?></td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" name="isl" value="<?php echo trim($data['ISL']); ?>" class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" step="0.01" name="sub3_per" value="<?php echo number_format($data['SUB3_PER'], 2); ?>" class="per-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="text" name="sub3_grade" value="<?php echo trim($data['SUB3_GRADE']); ?>" class="grade-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center">
                                    <input type="text" name="pass3" value="<?php echo trim($data['PASS3']) == '1' ? 'PASS' : ''; ?>" class="remarks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="border p-3 text-lg uppercase"><?php echo trim($data['APPEAR9']); ?></td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB9_TMARKS']); ?></td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" name="thq" value="<?php echo trim($data['THQ']); ?>" class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" step="0.01" name="sub9_per" value="<?php echo number_format($data['SUB9_PER'], 2); ?>" class="per-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="text" name="sub9_grade" value="<?php echo trim($data['SUB9_GRADE']); ?>" class="grade-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center">
                                    <input type="text" name="pass9" value="<?php echo trim($data['PASS9']) == '1' ? 'PASS' : ''; ?>" class="remarks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="border p-3 text-lg uppercase">Pakistan Studies</td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB4_TMARKS']); ?></td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" name="ps" value="<?php echo trim($data['PS']); ?>" class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" step="0.01" name="sub4_per" value="<?php echo number_format($data['SUB4_PER'], 2); ?>" class="per-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="text" name="sub4_grade" value="<?php echo trim($data['SUB4_GRADE']); ?>" class="grade-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center">
                                    <input type="text" name="pass4" value="<?php echo trim($data['PASS4']) == '1' ? 'PASS' : ''; ?>" class="remarks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="border p-3 text-lg uppercase"><?php echo trim($data['APPEAR5']); ?></td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB5_TMARKS']); ?></td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" name="maths" value="<?php echo trim($data['MATHS']); ?>" class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" step="0.01" name="sub5_per" value="<?php echo number_format($data['SUB5_PER'], 2); ?>" class="per-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="text" name="sub5_grade" value="<?php echo trim($data['SUB5_GRADE']); ?>" class="grade-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center">
                                    <input type="text" name="pass5" value="<?php echo trim($data['PASS5']) == '1' ? 'PASS' : ''; ?>" class="remarks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="border p-3 text-lg uppercase"><?php echo trim($data['APPEAR6']); ?></td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB6_TMARKS']); ?></td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" name="marks6" value="<?php echo trim($data['MARKS6']); ?>" class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" step="0.01" name="sub6_per" value="<?php echo number_format($data['SUB6_PER'], 2); ?>" class="per-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="text" name="sub6_grade" value="<?php echo trim($data['SUB6_GRADE']); ?>" class="grade-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center">
                                    <input type="text" name="pass6" value="<?php echo trim($data['PASS6']) == '1' ? 'PASS' : ''; ?>" class="remarks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="border p-3 text-lg uppercase"><?php echo trim($data['APPEAR7']); ?></td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB7_TMARKS']); ?></td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" name="marks7" value="<?php echo trim($data['MARKS7']); ?>" class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" step="0.01" name="sub7_per" value="<?php echo number_format($data['SUB7_PER'], 2); ?>" class="per-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="text" name="sub7_grade" value="<?php echo trim($data['SUB7_GRADE']); ?>" class="grade-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center">
                                    <input type="text" name="pass7" value="<?php echo trim($data['PASS7']) == '1' ? 'PASS' : ''; ?>" class="remarks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="border p-3 text-lg uppercase"><?php echo trim($data['APPEAR8']); ?></td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB8_TMARKS']); ?></td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" name="marks8" value="<?php echo trim($data['MARKS8']); ?>" class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" step="0.01" name="sub8_per" value="<?php echo number_format($data['SUB8_PER'], 2); ?>" class="per-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="text" name="sub8_grade" value="<?php echo trim($data['SUB8_GRADE']); ?>" class="grade-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center">
                                    <input type="text" name="pass8" value="<?php echo trim($data['PASS8']) == '1' ? 'PASS' : ''; ?>" class="remarks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                            </tr>
                            <tr class="bg-gray-200">
                                <td class="border p-3 text-lg font-bold" colspan="2">Result</td>
                                <td class="border p-3 text-center font-bold" colspan="4">
                                    <span id="obtainedMarks"><?php echo trim($data['GAZRES']); ?></span> / <span id="totalMarks"><?php
                                        $GTOTAL = (int)trim($data['SUB1_TMARKS']) + (int)trim($data['SUB2_TMARKS']) + (int)trim($data['SUB3_TMARKS']) + (int)trim($data['SUB4_TMARKS']) + (int)trim($data['SUB5_TMARKS']) + (int)trim($data['SUB6_TMARKS']) + (int)trim($data['SUB7_TMARKS']) + (int)trim($data['SUB8_TMARKS']) + (int)trim($data['SUB9_TMARKS']);
                                        echo $GTOTAL;
                                    ?></span>
                                </td>
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
