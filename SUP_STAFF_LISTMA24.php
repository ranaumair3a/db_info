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
    $data = [];
    $updateSuccess = false;

    if (isset($_POST['rno'])) {
        $rno = (int)($_POST['rno']);
    } else if (isset($_POST['update_roll_no'])) {
        $rno = (int)($_POST['update_roll_no']);
        // Handle update logic
        $updatedMarks = [
            'ur' => isset($_POST['ur']) ? (int)$_POST['ur'] : 0,
            'eng' => isset($_POST['eng']) ? (int)$_POST['eng'] : 0,
            'isl' => isset($_POST['isl']) ? (int)$_POST['isl'] : 0,
            'thq' => isset($_POST['thq']) ? (int)$_POST['thq'] : 0,
            'ps' => isset($_POST['ps']) ? (int)$_POST['ps'] : 0,
            'maths' => isset($_POST['maths']) ? (int)$_POST['maths'] : 0,
            'marks6' => isset($_POST['marks6']) ? (int)$_POST['marks6'] : 0,
            'marks7' => isset($_POST['marks7']) ? (int)$_POST['marks7'] : 0,
            'marks8' => isset($_POST['marks8']) ? (int)$_POST['marks8'] : 0
        ];

        $updateQuery = "UPDATE NANA2024000_RESULT SET UR = ?, ENG = ?, ISL = ?, THQ = ?, PS = ?, MATHS = ?, MARKS6 = ?, MARKS7 = ?, MARKS8 = ? WHERE RNO = ?";
        $stmt = mysqli_stmt_init($con);
        if (mysqli_stmt_prepare($stmt, $updateQuery)) {
            mysqli_stmt_bind_param($stmt, "iiiiiiiiii", $updatedMarks['ur'], $updatedMarks['eng'], $updatedMarks['isl'], $updatedMarks['thq'], $updatedMarks['ps'], $updatedMarks['maths'], $updatedMarks['marks6'], $updatedMarks['marks7'], $updatedMarks['marks8'], $rno);
            if (mysqli_stmt_execute($stmt)) {
                $updateSuccess = true;
            } else {
                echo "<script>alert('Error updating result.');</script>";
            }
            mysqli_stmt_close($stmt);
        }
    }

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

        $data = mysqli_fetch_array($result);
    }

    if ($updateSuccess) {
        echo "<script>alert('Result updated successfully!');</script>";
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

                const percentile = (marksObtained / totalMarks) * 100;
                row.querySelector('td:nth-child(4)').textContent = percentile.toFixed(2);

                let grade = '';
                if (percentile >= 90) grade = 'A+';
                else if (percentile >= 80) grade = 'A';
                else if (percentile >= 70) grade = 'B';
                else if (percentile >= 60) grade = 'C';
                else if (percentile >= 50) grade = 'D';
                else grade = 'F';
                row.querySelector('td:nth-child(5)').textContent = grade;
            });

            document.getElementById('totalMarks').textContent = totalMarksPossible;
            document.querySelector('tr.bg-gray-200 td:nth-child(3)').textContent = totalMarksObtained;
        }

        function updateResult() {
            document.getElementById('updateForm').submit();
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
            <form id="updateForm" method="post" action="" class="bg-white shadow-md rounded-lg p-6 mt-8">
                <input type="hidden" name="update_roll_no" value="<?php echo $rno; ?>">
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
                            <input type="text" value="<?php echo trim($data['NAME']); ?>" class="editable border rounded-lg p-2 w-full bg-gray-50" disabled>
                        </div>
                        <div>
                            <label class="text-lg font-semibold">Father's Name:</label>
                            <input type="text" value="<?php echo trim($data['FATHER']); ?>" class="editable border rounded-lg p-2 w-full bg-gray-50" disabled>
                        </div>
                        <div>
                            <label class="text-lg font-semibold">Institute/District:</label>
                            <input type="text" value="<?php echo trim($data['INST_NAME']); ?>" class="editable border rounded-lg p-2 w-full bg-gray-50" disabled>
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex space-x-4 no-print">
                    <button id="editButton" onclick="toggleEdit()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Edit</button>
                    <button id="updateButton" type="button" onclick="updateResult()" class="hidden bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">Update</button>
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
                                <td class="border p-3 text-center font-bold"><?php echo ($data['SUB1_PER']); ?></td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB1_GRADE']); ?></td>
                                <td class="border p-3 text-center"><?php echo trim($data['PASS1']) == '1' ? 'PASS' : ''; ?></td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="border p-3 text-lg uppercase">English</td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB2_TMARKS']); ?></td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" name="eng" value="<?php echo trim($data['ENG']); ?>" class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold"><?php echo ($data['SUB2_PER']); ?></td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['SUB2_GRADE']); ?></td>
                                <td class="border p-3 text-center"><?php echo trim($data['PASS2']) == '1' ? 'PASS' : ''; ?></td>
                            </tr>
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
                            <tr class="bg-gray-200">
                                <td class="border p-3 text-lg font-bold">Result</td>
                                <td class="border p-3 text-center font-bold">
                                    <span id="totalMarks"><?php
                                        $GTOTAL = trim($data['SUB1_TMARKS']) + trim($data['SUB2_TMARKS']) + trim($data['SUB3_TMARKS']) + trim($data['SUB4_TMARKS']) + trim($data['SUB5_TMARKS']) + trim($data['SUB6_TMARKS']) + trim($data['SUB7_TMARKS']) + trim($data['SUB8_TMARKS']) + trim($data['SUB9_TMARKS']);
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
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
