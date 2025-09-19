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
            @page {
                margin-top: 0;
                margin-bottom: 0;
            }
            body {
                padding-top: 72px;
                padding-bottom: 72px;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <script>
        // Function to calculate grade based on percentile
        function calculateGrade(percentile) {
            if (percentile >= 90) return 'A+';
            if (percentile >= 80) return 'A';
            if (percentile >= 70) return 'B';
            if (percentile >= 60) return 'C';
            if (percentile >= 50) return 'D';
            if (percentile >= 40) return 'E';
            return 'F';
        }

        // Function to update percentile, grade, and total marks
        function updateFields() {
            const marksInputs = document.querySelectorAll('.marks-input');
            const totalMarksElement = document.getElementById('totalMarks');
            let totalMarks = 0;

            marksInputs.forEach(input => {
                const marks = parseInt(input.value) || 0;
                const totalSubjectMarks = parseInt(input.closest('tr').querySelector('.total-marks').textContent) || 100;
                totalMarks += marks;

                // Calculate percentile
                const percentile = ((marks / totalSubjectMarks) * 100).toFixed(2);
                const percentileCell = input.closest('tr').querySelector('.percentile');
                percentileCell.textContent = percentile;

                // Calculate grade
                const gradeCell = input.closest('tr').querySelector('.grade');
                gradeCell.textContent = calculateGrade(percentile);

                // Update remarks
                const remarksCell = input.closest('tr').querySelector('.remarks');
                remarksCell.textContent = marks >= 33 ? 'PASS' : '';
            });

            // Update total marks
            totalMarksElement.textContent = totalMarks;
        }

        // Function to toggle edit mode
        function toggleEdit() {
            const inputs = document.querySelectorAll('.editable');
            const editButton = document.getElementById('editButton');
            const updateButton = document.getElementById('updateButton');
            inputs.forEach(input => {
                input.disabled = !input.disabled;
            });
            if (editButton.textContent === 'Edit') {
                editButton.textContent = 'Cancel';
                updateButton.classList.remove('hidden');
            } else {
                editButton.textContent = 'Edit';
                updateButton.classList.add('hidden');
            }
        }

        // Function to update result and save to database
        function updateResult() {
            const marksInputs = document.querySelectorAll('.marks-input');
            const rollNo = document.querySelector('.roll-no').textContent;
            const data = {
                rno: rollNo,
                marks: {}
            };

            marksInputs.forEach(input => {
                const subject = input.dataset.subject;
                const marks = parseInt(input.value) || 0;
                const totalSubjectMarks = parseInt(input.closest('tr').querySelector('.total-marks').textContent) || 100;

                // Validate marks
                if (marks < 0 || marks > totalSubjectMarks) {
                    alert(`Invalid marks for ${subject}. Marks should be between 0 and ${totalSubjectMarks}.`);
                    return;
                }

                data.marks[subject] = {
                    marks: marks,
                    percentile: ((marks / totalSubjectMarks) * 100).toFixed(2),
                    grade: calculateGrade((marks / totalSubjectMarks) * 100),
                    pass: marks >= 33 ? 1 : 0
                };
            });

            // Send data to server via AJAX
            fetch('update_result.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Result updated successfully!');
                    toggleEdit(); // Disable inputs after update
                } else {
                    alert('Error updating result: ' + result.message);
                }
            })
            .catch(error => {
                alert('Error updating result: ' + error.message);
            });
        }

        // Add event listeners to marks inputs for real-time updates
        document.addEventListener('DOMContentLoaded', () => {
            const marksInputs = document.querySelectorAll('.marks-input');
            marksInputs.forEach(input => {
                input.addEventListener('input', () => {
                    const maxMarks = parseInt(input.closest('tr').querySelector('.total-marks').textContent) || 100;
                    if (input.value > maxMarks) {
                        input.value = maxMarks;
                    }
                    if (input.value < 0) {
                        input.value = 0;
                    }
                    updateFields();
                });
            });
        });

        // Disable right-click
        document.onmousedown = function(event) {
            if (event.button === 2) {
                alert('Right Click Disabled');
                return false;
            }
        };
    </script>

    <div class="container mx-auto p-4 max-w-4xl">
        <!-- Header Section -->
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800">BOARD OF INTERMEDIATE AND SECONDARY EDUCATION DERA GHAZI KHAN</h1>
            <h2 class="text-3xl font-semibold text-blue-900 mt-2">Online Result For SSC (Part-I) 1<sup>st</sup> Annual 2024</h2>
        </div>

        <!-- Search Form -->
        <div class="bg-white shadow-md rounded-lg p-6 mx-auto max-w-lg no-print">
            <h3 class="text-xl font-semibold text-center mb-4">Search By Roll No</h3>
            <form method="post" name="slip" action="" onsubmit="return checkEmpty();" class="space-y-4">
                <div class="flex items-center space-x-4">
                    <label class="text-lg font-semibold">Enter Your Roll No</label>
                    <input type="number" maxlength="6" name="rno" value="" placeholder="100001-200000 or 300000-360000"
                           oninput="if(this.value.length > 6) this.value = this.value.slice(0, 6);" class="border rounded-lg p-2 w-full focus:outline-none focus:ring-2 focus:ring-blue-500"/>
                </div>
                <div class="text-center">
                    <input type="submit" value="Search for Result" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700"/>
                </div>
            </form>
        </div>

        <br><br>

        <?php
        include_once('config_seci.php');

        function escape($string) {
            return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
        }

        $rno = 0;
        if (isset($_POST['rno'])) {
            $rno = (int)($_POST['rno']);
        } else {
            echo ".";
            exit();
        }
        $rno = escape($rno);
        if (strlen($rno) !== 6) {
            echo "<div class='text-red-600 font-bold text-center'>Roll No. must consist of 6 digits.</div>";
            exit();
        }

        if ($rno < 100000 || ($rno > 200000 && $rno < 300000) || $rno > 360000) {
            echo "<div class='text-red-600 font-bold text-center'>Roll No. range is from 100000 to 200000 OR 300000 to 360000 only.</div>";
            exit();
        }

        $rno = filter_var($rno, FILTER_SANITIZE_NUMBER_INT);
        if (!filter_var($rno, FILTER_VALIDATE_INT)) {
            echo "<div class='text-red-600 font-bold text-center'>INVALID ROLL NO.</div>";
            exit;
        }

        $data = array();
        $query = "SELECT * FROM NANA2024000_RESULT WHERE RNO=?";
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $query);
        mysqli_stmt_bind_param($stmt, "i", $rno);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $num_rows = mysqli_num_rows($result);

        if ($num_rows < 1) {
            echo "<div class='text-red-600 font-bold text-center'>Wrong Roll No</div>";
            exit;
        }

        while ($row = mysqli_fetch_array($result)) {
            $data = $row;
        }

        $DISTT = trim($data['DISTRICT_N']);
        $INST_CODE = trim($data['STATUS']);
        $OBJECTION = trim($data['OBJECTION']);
        $OBJECTION2 = trim($data['OBJECTION2']);

        if ($OBJECTION == 1 || $OBJECTION2 == 1) {
            $GAZRES = $data['GAZRES'];
            ?>
            <div class="text-center bg-white shadow-md rounded-lg p-6">
                <div class="text-lg font-bold">
                    <p>Sr No: <?php echo trim($data['SRNO']); ?></p>
                    <p>Roll No: <?php echo trim($data['RNO']); ?></p>
                    <p>Name: <?php echo trim($data['NAME']); ?></p>
                    <p><?php echo $data['GAZRES'] == 'ABSENT' ? "Result:" : "Result Block/Objection:"; ?> <?php echo trim($data['GAZRES']); ?></p>
                </div>
            </div>
            <?php
            exit;
        }
        ?>

        <!-- Result Section -->
        <div class="bg-white shadow-md rounded-lg p-6 mt-8">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-4">
                    <img src="images/logo.gif" alt="Logo" class="w-24 h-24">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Board Of Intermediate & Secondary Education, Dera Ghazi Khan</h2>
                        <p class="text-lg">Roll No: <span class="font-bold roll-no"><?php echo trim($data['RNO']); ?></span></p>
                        <p class="text-lg">PROVISIONAL RESULT INTIMATION</p>
                        <p class="text-lg">Sr. No: <span class="font-bold"><?php echo trim($data['SRNO']); ?></span></p>
                    </div>
                </div>
                <p class="text-lg font-bold">Reg No: <?php echo trim($data['REGNO']); ?></p>
            </div>
            <p class="text-base">SECONDARY SCHOOL CERTIFICATE (PART-I) 1<sup>st</sup> ANNUAL EXAMINATION 2024</p>
            <p class="text-base font-bold">GROUP: <?php
                $SGROUP = trim($data['S_GROUP']);
                if ($SGROUP == 'S') {
                    $SGROUP = 'SCIENCE';
                } elseif ($SGROUP == 'G') {
                    $SGROUP = 'GENERAL';
                } elseif ($SGROUP == 'D') {
                    $SGROUP = 'DEAF & DUMB';
                }
                echo $SGROUP;
                ?></p>

            <!-- Candidate Info -->
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
                        <input type="text" value="<?php echo $INST_CODE == 'R' ? trim($data['INST_NAME']) : $DISTT; ?>" class="editable border rounded-lg p-2 w-full bg-gray-50" disabled>
                    </div>
                </div>
            </div>

            <!-- Edit and Update Buttons -->
            <div class="mt-4 flex space-x-4 no-print">
                <button id="editButton" onclick="toggleEdit()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Edit</button>
                <button id="updateButton" onclick="updateResult()" class="hidden bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">Update</button>
            </div>

            <!-- Result Table -->
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
                        <!-- Urdu -->
                        <tr class="hover:bg-gray-50">
                            <td class="border p-3 text-lg uppercase">Urdu</td>
                            <td class="border p-3 text-center font-bold total-marks"><?php echo trim($data['SUB1_TMARKS']); ?></td>
                            <td class="border p-3 text-center font-bold">
                                <input type="number" value="<?php echo trim($data['UR']); ?>" data-subject="UR" class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                            </td>
                            <td class="border p-3 text-center font-bold percentile"><?php echo ($data['SUB1_PER']); ?></td>
                            <td class="border p-3 text-center font-bold grade"><?php echo trim($data['SUB1_GRADE']); ?></td>
                            <td class="border p-3 text-center remarks"><?php echo trim($data['PASS1']) == '1' ? 'PASS' : ''; ?></td>
                        </tr>
                        <!-- English -->
                        <tr class="hover:bg-gray-50">
                            <td class="border p-3 text-lg uppercase">English</td>
                            <td class="border p-3 text-center font-bold total-marks"><?php echo trim($data['SUB2_TMARKS']); ?></td>
                            <td class="border p-3 text-center font-bold">
                                <input type="number" value="<?php echo trim($data['ENG']); ?>" data-subject="ENG" class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                            </td>
                            <td class="border p-3 text-center font-bold percentile"><?php echo ($data['SUB2_PER']); ?></td>
                            <td class="border p-3 text-center font-bold grade"><?php echo trim($data['SUB2_GRADE']); ?></td>
                            <td class="border p-3 text-center remarks"><?php echo trim($data['PASS2']) == '1' ? 'PASS' : ''; ?></td>
                        </tr>
                        <!-- Subject 3 -->
                        <tr class="hover:bg-gray-50">
                            <td class="border p-3 text-lg uppercase"><?php echo trim($data['APPEAR3']); ?></td>
                            <td class="border p-3 text-center font-bold total-marks"><?php echo trim($data['SUB3_TMARKS']); ?></td>
                            <td class="border p-3 text-center font-bold">
                                <input type="number" value="<?php echo trim($data['ISL']); ?>" data-subject="ISL" class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                            </td>
                            <td class="border p-3 text-center font-bold percentile"><?php echo trim($data['SUB3_PER']); ?></td>
                            <td class="border p-3 text-center font-bold grade"><?php echo trim($data['SUB3_GRADE']); ?></td>
                            <td class="border p-3 text-center remarks"><?php echo trim($data['PASS3']) == '1' ? 'PASS' : ''; ?></td>
                        </tr>
                        <!-- Subject 9 -->
                        <tr class="hover:bg-gray-50">
                            <td class="border p-3 text-lg uppercase"><?php echo trim($data['APPEAR9']); ?></td>
                            <td class="border p-3 text-center font-bold total-marks"><?php echo trim($data['SUB9_TMARKS']); ?></td>
                            <td class="border p-3 text-center font-bold">
                                <input type="number" value="<?php echo trim($data['THQ']); ?>" data-subject="THQ" class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                            </td>
                            <td class="border p-3 text-center font-bold percentile"><?php echo trim($data['SUB9_PER']); ?></td>
                            <td class="border p-3 text-center font-bold grade"><?php echo trim($data['SUB9_GRADE']); ?></td>
                            <td class="border p-3 text-center remarks"><?php echo trim($data['PASS9']) == '1' ? 'PASS' : ''; ?></td>
                        </tr>
                        <!-- Pakistan Studies -->
                        <tr class="hover:bg-gray-50">
                            <td class="border p-3 text-lg uppercase">Pakistan Studies</td>
                            <td class="border p-3 text-center font-bold total-marks"><?php echo trim($data['SUB4_TMARKS']); ?></td>
                            <td class="border p-3 text-center font-bold">
                                <input type="number" value="<?php echo trim($data['PS']); ?>" data-subject="PS" class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                            </td>
                            <td class="border p-3 text-center font-bold percentile"><?php echo trim($data['SUB4_PER']); ?></td>
                            <td class="border p-3 text-center font-bold grade"><?php echo trim($data['SUB4_GRADE']); ?></td>
                            <td class="border p-3 text-center remarks"><?php echo trim($data['PASS4']) == '1' ? 'PASS' : ''; ?></td>
                        </tr>
                        <!-- Subject 5 -->
                        <tr class="hover:bg-gray-50">
                            <td class="border p-3 text-lg uppercase"><?php echo trim($data['APPEAR5']); ?></td>
                            <td class="border p-3 text-center font-bold total-marks"><?php echo trim($data['SUB5_TMARKS']); ?></td>
                            <td class="border p-3 text-center font-bold">
                                <input type="number" value="<?php echo trim($data['MATHS']); ?>" data-subject="MATHS" class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                            </td>
                            <td class="border p-3 text-center font-bold percentile"><?php echo trim($data['SUB5_PER']); ?></td>
                            <td class="border p-3 text-center font-bold grade"><?php echo trim($data['SUB5_GRADE']); ?></td>
                            <td class="border p-3 text-center remarks"><?php echo trim($data['PASS5']) == '1' ? 'PASS' : ''; ?></td>
                        </tr>
                        <!-- Subject 6 -->
                        <tr class="hover:bg-gray-50">
                            <td class="border p-3 text-lg uppercase"><?php echo trim($data['APPEAR6']); ?></td>
                            <td class="border p-3 text-center font-bold total-marks"><?php echo trim($data['SUB6_TMARKS']); ?></td>
                            <td class="border p-3 text-center font-bold">
                                <input type="number" value="<?php echo trim($data['MARKS6']); ?>" data-subject="MARKS6" class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                            </td>
                            <td class="border p-3 text-center font-bold percentile"><?php echo trim($data['SUB6_PER']); ?></td>
                            <td class="border p-3 text-center font-bold grade"><?php echo trim($data['SUB6_GRADE']); ?></td>
                            <td class="border p-3 text-center remarks"><?php echo trim($data['PASS6']) == '1' ? 'PASS' : ''; ?></td>
                        </tr>
                        <!-- Subject 7 -->
                        <tr class="hover:bg-gray-50">
                            <td class="border p-3 text-lg uppercase"><?php echo trim($data['APPEAR7']); ?></td>
                            <td class="border p-3 text-center font-bold total-marks"><?php echo trim($data['SUB7_TMARKS']); ?></td>
                            <td class="border p-3 text-center font-bold">
                                <input type="number" value="<?php echo trim($data['MARKS7']); ?>" data-subject="MARKS7" class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                            </td>
                            <td class="border p-3 text-center font-bold percentile"><?php echo trim($data['SUB7_PER']); ?></td>
                            <td class="border p-3 text-center font-bold grade"><?php echo trim($data['SUB7_GRADE']); ?></td>
                            <td class="border p-3 text-center remarks"><?php echo trim($data['PASS7']) == '1' ? 'PASS' : ''; ?></td>
                        </tr>
                        <!-- Subject 8 -->
                        <tr class="hover:bg-gray-50">
                            <td class="border p-3 text-lg uppercase"><?php echo trim($data['APPEAR8']); ?></td>
                            <td class="border p-3 text-center font-bold total-marks"><?php echo trim($data['SUB8_TMARKS']); ?></td>
                            <td class="border p-3 text-center font-bold">
                                <input type="number" value="<?php echo trim($data['MARKS8']); ?>" data-subject="MARKS8" class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                            </td>
                            <td class="border p-3 text-center font-bold percentile"><?php echo trim($data['SUB8_PER']); ?></td>
                            <td class="border p-3 text-center font-bold grade"><?php echo trim($data['SUB8_GRADE']); ?></td>
                            <td class="border p-3 text-center remarks"><?php echo trim($data['PASS8']) == '1' ? 'PASS' : ''; ?></td>
                        </tr>
                        <!-- Total -->
                        <tr class="bg-gray-200">
                            <td class="border p-3 text-lg font-bold">Result</td>
                            <td class="border p-3 text-center font-bold">
                                <span id="totalMarks"><?php
                                    $GTOTAL = trim($data['SUB1_TMARKS']) +
                                        trim($data['SUB2_TMARKS']) +
                                        trim($data['SUB3_TMARKS']) +
                                        trim($data['SUB4_TMARKS']) +
                                        trim($data['SUB5_TMARKS']) +
                                        trim($data['SUB6_TMARKS']) +
                                        trim($data['SUB7_TMARKS']) +
                                        trim($data['SUB8_TMARKS']) +
                                        trim($data['SUB9_TMARKS']);
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

            <!-- Notes -->
            <div class="mt-6">
                <?php if (trim($data['PASS']) == 1) { ?>
                    <p class="text-base font-bold">NOTE 1: The marks awarded are the best prediction of the performance & has been awarded under COVID-19 Examination Policy, hence considered as valid and fair.</p>
                    <p class="text-base font-bold">2: Errors and omissions are excepted. For any query send email at <a href="mailto:bise786@gmail.com" class="text-blue-600">bise786@gmail.com</a>.</p>
                <?php } else { ?>
                    <p class="text-base font-bold">NOTE:</p>
                    <p class="text-base font-bold">1: The candidate shall appear in the absent subject(s) along with compulsory subjects in annual examinations 2022.</p>
                    <p class="text-base font-bold">2: The marks awarded are the best prediction of the performance & has been awarded under COVID-19 Examination Policy, hence considered as valid and fair.</p>
                <?php } ?>
            </div>
        </div>
    </div>
</body>
</html>
