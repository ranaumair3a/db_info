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

    function calculateGrade($percentage) {
        if ($percentage >= 90) return 'A+';
        else if ($percentage >= 80) return 'A';
        else if ($percentage >= 70) return 'B';
        else if ($percentage >= 60) return 'C';
        else if ($percentage >= 50) return 'D';
        else return 'F';
    }

    function calculatePercentage($obtained, $total) {
        if ($total == 0) return 0;
        return round(($obtained / $total) * 100, 2);
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

        // Calculate percentages and grades
        $sub1_per = calculatePercentage($updatedMarks['ur'], $data['SUB1_TMARKS']);
        $sub2_per = calculatePercentage($updatedMarks['eng'], $data['SUB2_TMARKS']);
        $sub3_per = calculatePercentage($updatedMarks['isl'], $data['SUB3_TMARKS']);
        $sub4_per = calculatePercentage($updatedMarks['ps'], $data['SUB4_TMARKS']);
        $sub5_per = calculatePercentage($updatedMarks['maths'], $data['SUB5_TMARKS']);
        $sub6_per = calculatePercentage($updatedMarks['marks6'], $data['SUB6_TMARKS']);
        $sub7_per = calculatePercentage($updatedMarks['marks7'], $data['SUB7_TMARKS']);
        $sub8_per = calculatePercentage($updatedMarks['marks8'], $data['SUB8_TMARKS']);
        $sub9_per = calculatePercentage($updatedMarks['thq'], $data['SUB9_TMARKS']);

        $sub1_grade = calculateGrade($sub1_per);
        $sub2_grade = calculateGrade($sub2_per);
        $sub3_grade = calculateGrade($sub3_per);
        $sub4_grade = calculateGrade($sub4_per);
        $sub5_grade = calculateGrade($sub5_per);
        $sub6_grade = calculateGrade($sub6_per);
        $sub7_grade = calculateGrade($sub7_per);
        $sub8_grade = calculateGrade($sub8_per);
        $sub9_grade = calculateGrade($sub9_per);

        // Calculate pass status (assuming 33% is passing)
        $pass1 = $sub1_per >= 33 ? 1 : 0;
        $pass2 = $sub2_per >= 33 ? 1 : 0;
        $pass3 = $sub3_per >= 33 ? 1 : 0;
        $pass4 = $sub4_per >= 33 ? 1 : 0;
        $pass5 = $sub5_per >= 33 ? 1 : 0;
        $pass6 = $sub6_per >= 33 ? 1 : 0;
        $pass7 = $sub7_per >= 33 ? 1 : 0;
        $pass8 = $sub8_per >= 33 ? 1 : 0;
        $pass9 = $sub9_per >= 33 ? 1 : 0;

        // Calculate total marks obtained
        $totalObtained = $updatedMarks['ur'] + $updatedMarks['eng'] + $updatedMarks['isl'] + 
                        $updatedMarks['thq'] + $updatedMarks['ps'] + $updatedMarks['maths'] + 
                        $updatedMarks['marks6'] + $updatedMarks['marks7'] + $updatedMarks['marks8'];

        // Determine overall result
        $allPassed = ($pass1 && $pass2 && $pass3 && $pass4 && $pass5 && $pass6 && $pass7 && $pass8 && $pass9);
        $gazres = $allPassed ? $totalObtained : 'FAIL';

        $updateQuery = "UPDATE NANA2024000_RESULT SET 
                       UR = ?, ENG = ?, ISL = ?, THQ = ?, PS = ?, MATHS = ?, MARKS6 = ?, MARKS7 = ?, MARKS8 = ?,
                       SUB1_PER = ?, SUB2_PER = ?, SUB3_PER = ?, SUB4_PER = ?, SUB5_PER = ?, 
                       SUB6_PER = ?, SUB7_PER = ?, SUB8_PER = ?, SUB9_PER = ?,
                       SUB1_GRADE = ?, SUB2_GRADE = ?, SUB3_GRADE = ?, SUB4_GRADE = ?, SUB5_GRADE = ?,
                       SUB6_GRADE = ?, SUB7_GRADE = ?, SUB8_GRADE = ?, SUB9_GRADE = ?,
                       PASS1 = ?, PASS2 = ?, PASS3 = ?, PASS4 = ?, PASS5 = ?,
                       PASS6 = ?, PASS7 = ?, PASS8 = ?, PASS9 = ?, GAZRES = ?
                       WHERE RNO = ?";
        
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $updateQuery);
        mysqli_stmt_bind_param($stmt, "iiiiiiiiidddddddddssssssssiiiiiiiisi", 
            $updatedMarks['ur'], $updatedMarks['eng'], $updatedMarks['isl'], $updatedMarks['thq'], 
            $updatedMarks['ps'], $updatedMarks['maths'], $updatedMarks['marks6'], $updatedMarks['marks7'], $updatedMarks['marks8'],
            $sub1_per, $sub2_per, $sub3_per, $sub4_per, $sub5_per, $sub6_per, $sub7_per, $sub8_per, $sub9_per,
            $sub1_grade, $sub2_grade, $sub3_grade, $sub4_grade, $sub5_grade, $sub6_grade, $sub7_grade, $sub8_grade, $sub9_grade,
            $pass1, $pass2, $pass3, $pass4, $pass5, $pass6, $pass7, $pass8, $pass9, $gazres, $rno);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Result updated successfully!');</script>";
        } else {
            echo "<script>alert('Error updating result.');</script>";
        }
        
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
                    input.addEventListener('input', calculateUpdatesLive);
                });
            } else {
                editButton.textContent = 'Edit';
                updateButton.classList.add('hidden');
                document.querySelectorAll('.marks-input').forEach(input => {
                    input.removeEventListener('input', calculateUpdatesLive);
                });
                // Reset to original values
                location.reload();
            }
        }

        function calculateUpdatesLive() {
            const marksInputs = document.querySelectorAll('.marks-input');
            let totalMarksObtained = 0;
            let totalMarksPossible = 0;

            marksInputs.forEach(input => {
                const row = input.closest('tr');
                const totalMarks = parseInt(row.querySelector('td:nth-child(2)').textContent) || 0;
                const marksObtained = parseInt(input.value) || 0;
                
                // Validate input
                if (marksObtained > totalMarks) {
                    input.value = totalMarks;
                    return;
                }
                if (marksObtained < 0) {
                    input.value = 0;
                    return;
                }

                totalMarksObtained += marksObtained;
                totalMarksPossible += totalMarks;

                const percentile = totalMarks > 0 ? ((marksObtained / totalMarks) * 100) : 0;
                row.querySelector('td:nth-child(4)').textContent = percentile.toFixed(2);

                let grade = '';
                if (percentile >= 90) grade = 'A+';
                else if (percentile >= 80) grade = 'A';
                else if (percentile >= 70) grade = 'B';
                else if (percentile >= 60) grade = 'C';
                else if (percentile >= 50) grade = 'D';
                else grade = 'F';
                row.querySelector('td:nth-child(5)').textContent = grade;

                // Update pass/fail status
                const remarksCell = row.querySelector('td:nth-child(6)');
                if (percentile >= 33) {
                    remarksCell.textContent = 'PASS';
                    remarksCell.className = 'border p-3 text-center text-green-600 font-bold';
                } else {
                    remarksCell.textContent = 'FAIL';
                    remarksCell.className = 'border p-3 text-center text-red-600 font-bold';
                }
            });

            // Update total row
            document.getElementById('totalMarks').textContent = totalMarksPossible;
            document.querySelector('tr.bg-gray-200 td:nth-child(3)').textContent = totalMarksObtained;
            
            // Update overall result
            const allPassed = Array.from(marksInputs).every(input => {
                const row = input.closest('tr');
                const totalMarks = parseInt(row.querySelector('td:nth-child(2)').textContent) || 0;
                const marksObtained = parseInt(input.value) || 0;
                const percentile = totalMarks > 0 ? ((marksObtained / totalMarks) * 100) : 0;
                return percentile >= 33;
            });
            
            const resultCell = document.querySelector('tr.bg-gray-200 td:nth-child(3)');
            if (allPassed) {
                resultCell.textContent = totalMarksObtained;
                resultCell.className = 'border p-3 text-center font-bold text-green-600';
            } else {
                resultCell.textContent = 'FAIL';
                resultCell.className = 'border p-3 text-center font-bold text-red-600';
            }
        }

        function updateResult() {
            const formData = new FormData();
            formData.append('update_roll_no', '<?php echo $rno; ?>');
            
            document.querySelectorAll('.marks-input').forEach(input => {
                formData.append(input.name, input.value);
            });

            // Also update editable name and father name if changed
            const nameInput = document.querySelector('input[value="<?php echo addslashes(trim($data['NAME'])); ?>"]');
            const fatherInput = document.querySelector('input[value="<?php echo addslashes(trim($data['FATHER'])); ?>"]');
            const instInput = document.querySelector('input[value="<?php echo addslashes(trim($data['INST_NAME'])); ?>"]');
            
            if (nameInput) formData.append('name', nameInput.value);
            if (fatherInput) formData.append('father', fatherInput.value);
            if (instInput) formData.append('inst_name', instInput.value);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            }).then(response => response.text())
            .then(data => {
                location.reload(); // Reload to reflect updated data
            }).catch(error => {
                alert('Error updating result: ' + error);
            });
        }

        // Add input validation on load
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.marks-input').forEach(input => {
                input.addEventListener('input', function() {
                    const row = this.closest('tr');
                    const totalMarks = parseInt(row.querySelector('td:nth-child(2)').textContent) || 0;
                    if (parseInt(this.value) > totalMarks) {
                        this.value = totalMarks;
                    }
                    if (parseInt(this.value) < 0) {
                        this.value = 0;
                    }
                });
            });
        });
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
                    <button onclick="window.print()" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">Print</button>
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
                                    <input type="number" name="ur" value="<?php echo trim($data['UR']); ?>" 
                                           class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" 
                                           min="0" max="<?php echo trim($data['SUB1_TMARKS']); ?>" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold"><?php echo ($data['SUB1_PER']); ?></td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB1_GRADE']); ?></td>
                                <td class="border p-3 text-center <?php echo trim($data['PASS1']) == '1' ? 'text-green-600' : 'text-red-600'; ?> font-bold">
                                    <?php echo trim($data['PASS1']) == '1' ? 'PASS' : (trim($data['UR']) > 0 ? 'FAIL' : ''); ?>
                                </td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="border p-3 text-lg uppercase">English</td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB2_TMARKS']); ?></td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" name="eng" value="<?php echo trim($data['ENG']); ?>" 
                                           class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" 
                                           min="0" max="<?php echo trim($data['SUB2_TMARKS']); ?>" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold"><?php echo ($data['SUB2_PER']); ?></td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB2_GRADE']); ?></td>
                                <td class="border p-3 text-center <?php echo trim($data['PASS2']) == '1' ? 'text-green-600' : 'text-red-600'; ?> font-bold">
                                    <?php echo trim($data['PASS2']) == '1' ? 'PASS' : (trim($data['ENG']) > 0 ? 'FAIL' : ''); ?>
                                </td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="border p-3 text-lg uppercase"><?php echo trim($data['APPEAR3']); ?></td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB3_TMARKS']); ?></td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" name="isl" value="<?php echo trim($data['ISL']); ?>" 
                                           class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" 
                                           min="0" max="<?php echo trim($data['SUB3_TMARKS']); ?>" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB3_PER']); ?></td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB3_GRADE']); ?></td>
                                <td class="border p-3 text-center <?php echo trim($data['PASS3']) == '1' ? 'text-green-600' : 'text-red-600'; ?> font-bold">
                                    <?php echo trim($data['PASS3']) == '1' ? 'PASS' : (trim($data['ISL']) > 0 ? 'FAIL' : ''); ?>
                                </td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="border p-3 text-lg uppercase"><?php echo trim($data['APPEAR9']); ?></td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB9_TMARKS']); ?></td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" name="thq" value="<?php echo trim($data['THQ']); ?>" 
                                           class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" 
                                           min="0" max="<?php echo trim($data['SUB9_TMARKS']); ?>" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB9_PER']); ?></td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB9_GRADE']); ?></td>
                                <td class="border p-3 text-center <?php echo trim($data['PASS9']) == '1' ? 'text-green-600' : 'text-red-600'; ?> font-bold">
                                    <?php echo trim($data['PASS9']) == '1' ? 'PASS' : (trim($data['THQ']) > 0 ? 'FAIL' : ''); ?>
                                </td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="border p-3 text-lg uppercase">Pakistan Studies</td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB4_TMARKS']); ?></td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" name="ps" value="<?php echo trim($data['PS']); ?>" 
                                           class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" 
                                           min="0" max="<?php echo trim($data['SUB4_TMARKS']); ?>" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB4_PER']); ?></td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB4_GRADE']); ?></td>
                                <td class="border p-3 text-center <?php echo trim($data['PASS4']) == '1' ? 'text-green-600' : 'text-red-600'; ?> font-bold">
                                    <?php echo trim($data['PASS4']) == '1' ? 'PASS' : (trim($data['PS']) > 0 ? 'FAIL' : ''); ?>
                                </td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="border p-3 text-lg uppercase"><?php echo trim($data['APPEAR5']); ?></td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB5_TMARKS']); ?></td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" name="maths" value="<?php echo trim($data['MATHS']); ?>" 
                                           class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50"
                                           min="0" max="<?php echo trim($data['SUB5_TMARKS']); ?>" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB5_PER']); ?></td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB5_GRADE']); ?></td>
                                <td class="border p-3 text-center <?php echo trim($data['PASS5']) == '1' ? 'text-green-600' : 'text-red-600'; ?> font-bold">
                                    <?php echo trim($data['PASS5']) == '1' ? 'PASS' : (trim($data['MATHS']) > 0 ? 'FAIL' : ''); ?>
                                </td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="border p-3 text-lg uppercase"><?php echo trim($data['APPEAR6']); ?></td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB6_TMARKS']); ?></td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" name="marks6" value="<?php echo trim($data['MARKS6']); ?>" 
                                           class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50"
                                           min="0" max="<?php echo trim($data['SUB6_TMARKS']); ?>" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB6_PER']); ?></td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB6_GRADE']); ?></td>
                                <td class="border p-3 text-center <?php echo trim($data['PASS6']) == '1' ? 'text-green-600' : 'text-red-600'; ?> font-bold">
                                    <?php echo trim($data['PASS6']) == '1' ? 'PASS' : (trim($data['MARKS6']) > 0 ? 'FAIL' : ''); ?>
                                </td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="border p-3 text-lg uppercase"><?php echo trim($data['APPEAR7']); ?></td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB7_TMARKS']); ?></td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" name="marks7" value="<?php echo trim($data['MARKS7']); ?>" 
                                           class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50"
                                           min="0" max="<?php echo trim($data['SUB7_TMARKS']); ?>" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB7_PER']); ?></td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB7_GRADE']); ?></td>
                                <td class="border p-3 text-center <?php echo trim($data['PASS7']) == '1' ? 'text-green-600' : 'text-red-600'; ?> font-bold">
                                    <?php echo trim($data['PASS7']) == '1' ? 'PASS' : (trim($data['MARKS7']) > 0 ? 'FAIL' : ''); ?>
                                </td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="border p-3 text-lg uppercase"><?php echo trim($data['APPEAR8']); ?></td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB8_TMARKS']); ?></td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" name="marks8" value="<?php echo trim($data['MARKS8']); ?>" 
                                           class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50"
                                           min="0" max="<?php echo trim($data['SUB8_TMARKS']); ?>" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB8_PER']); ?></td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB8_GRADE']); ?></td>
                                <td class="border p-3 text-center <?php echo trim($data['PASS8']) == '1' ? 'text-green-600' : 'text-red-600'; ?> font-bold">
                                    <?php echo trim($data['PASS8']) == '1' ? 'PASS' : (trim($data['MARKS8']) > 0 ? 'FAIL' : ''); ?>
                                </td>
                            </tr>
                            <tr class="bg-gray-200">
                                <td class="border p-3 text-lg font-bold">Result</td>
                                <td class="border p-3 text-center font-bold">
                                    <span id="totalMarks"><?php
                                        $GTOTAL = trim($data['SUB1_TMARKS']) + trim($data['SUB2_TMARKS']) + trim($data['SUB3_TMARKS']) + trim($data['SUB4_TMARKS']) + trim($data['SUB5_TMARKS']) + trim($data['SUB6_TMARKS']) + trim($data['SUB7_TMARKS']) + trim($data['SUB8_TMARKS']) + trim($data['SUB9_TMARKS']);
                                        echo $GTOTAL;
                                    ?></span>
                                </td>
                                <td class="border p-3 text-center font-bold <?php echo is_numeric(trim($data['GAZRES'])) ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo trim($data['GAZRES']); ?>
                                </td>
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
                    
                    <?php if (!is_numeric(trim($data['GAZRES']))): ?>
                    <div class="mt-4 p-4 bg-red-100 border border-red-400 rounded-lg">
                        <p class="text-red-700 font-bold">⚠️ RESULT STATUS: <?php echo trim($data['GAZRES']); ?></p>
                        <p class="text-red-600">The candidate needs to improve performance in failed subjects.</p>
                    </div>
                    <?php else: ?>
                    <div class="mt-4 p-4 bg-green-100 border border-green-400 rounded-lg">
                        <p class="text-green-700 font-bold">✅ RESULT STATUS: PASSED</p>
                        <p class="text-green-600">Total Marks Obtained: <?php echo trim($data['GAZRES']); ?> out of <?php echo $GTOTAL; ?></p>
                        <p class="text-green-600">Overall Percentage: <?php echo round((trim($data['GAZRES'])/$GTOTAL)*100, 2); ?>%</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Stats Section -->
            <div class="bg-white shadow-md rounded-lg p-6 mt-6 no-print">
                <h3 class="text-xl font-semibold mb-4">Quick Statistics</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-blue-100 p-4 rounded-lg text-center">
                        <h4 class="font-bold text-blue-800">Total Subjects</h4>
                        <p class="text-2xl font-bold text-blue-600">9</p>
                    </div>
                    <div class="bg-green-100 p-4 rounded-lg text-center">
                        <h4 class="font-bold text-green-800">Subjects Passed</h4>
                        <p class="text-2xl font-bold text-green-600">
                            <?php 
                            $passedSubjects = 0;
                            $passedSubjects += trim($data['PASS1']) == '1' ? 1 : 0;
                            $passedSubjects += trim($data['PASS2']) == '1' ? 1 : 0;
                            $passedSubjects += trim($data['PASS3']) == '1' ? 1 : 0;
                            $passedSubjects += trim($data['PASS4']) == '1' ? 1 : 0;
                            $passedSubjects += trim($data['PASS5']) == '1' ? 1 : 0;
                            $passedSubjects += trim($data['PASS6']) == '1' ? 1 : 0;
                            $passedSubjects += trim($data['PASS7']) == '1' ? 1 : 0;
                            $passedSubjects += trim($data['PASS8']) == '1' ? 1 : 0;
                            $passedSubjects += trim($data['PASS9']) == '1' ? 1 : 0;
                            echo $passedSubjects;
                            ?>
                        </p>
                    </div>
                    <div class="bg-red-100 p-4 rounded-lg text-center">
                        <h4 class="font-bold text-red-800">Subjects Failed</h4>
                        <p class="text-2xl font-bold text-red-600"><?php echo 9 - $passedSubjects; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Additional JavaScript for enhanced functionality -->
    <script>
        // Auto-save functionality (optional)
        let autoSaveTimer;
        function enableAutoSave() {
            document.querySelectorAll('.marks-input').forEach(input => {
                input.addEventListener('input', function() {
                    clearTimeout(autoSaveTimer);
                    autoSaveTimer = setTimeout(() => {
                        // Auto-save after 2 seconds of inactivity
                        console.log('Auto-saving changes...');
                        // updateResult(); // Uncomment if you want auto-save
                    }, 2000);
                });
            });
        }

        // Grade color coding
        function updateGradeColors() {
            document.querySelectorAll('td:nth-child(5)').forEach(cell => {
                const grade = cell.textContent.trim();
                cell.classList.remove('text-green-600', 'text-yellow-600', 'text-orange-600', 'text-red-600');
                
                switch(grade) {
                    case 'A+':
                    case 'A':
                        cell.classList.add('text-green-600');
                        break;
                    case 'B':
                    case 'C':
                        cell.classList.add('text-yellow-600');
                        break;
                    case 'D':
                        cell.classList.add('text-orange-600');
                        break;
                    case 'F':
                        cell.classList.add('text-red-600');
                        break;
                }
            });
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateGradeColors();
        });

        // Export functionality
        function exportToCSV() {
            const table = document.querySelector('table');
            let csv = '';
            
            // Get table headers
            const headers = table.querySelectorAll('thead tr th');
            headers.forEach((header, index) => {
                csv += header.textContent.replace(/\s+/g, ' ').trim();
                if (index < headers.length - 1) csv += ',';
            });
            csv += '\n';
            
            // Get table data
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                cells.forEach((cell, index) => {
                    let cellText = cell.textContent.replace(/\s+/g, ' ').trim();
                    // Handle input fields
                    const input = cell.querySelector('input');
                    if (input) {
                        cellText = input.value;
                    }
                    csv += cellText;
                    if (index < cells.length - 1) csv += ',';
                });
                csv += '\n';
            });
            
            // Download CSV
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'result_<?php echo $rno; ?>.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+E to toggle edit mode
            if (e.ctrlKey && e.key === 'e') {
                e.preventDefault();
                toggleEdit();
            }
            // Ctrl+S to save (when in edit mode)
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                const updateButton = document.getElementById('updateButton');
                if (!updateButton.classList.contains('hidden')) {
                    updateResult();
                }
            }
            // Ctrl+P to print
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });
    </script>
</body>
</html>
