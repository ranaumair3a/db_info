<?php
// Handle AJAX update request
if (isset($_POST['action']) && $_POST['action'] === 'update_result') {
    header('Content-Type: application/json');
    
    include_once('config_seci.php');
    
    $rno = (int)$_POST['rno'];
    $ur = (int)$_POST['ur'];
    $eng = (int)$_POST['eng'];
    $isl = (int)$_POST['isl'];
    $thq = (int)$_POST['thq'];
    $ps = (int)$_POST['ps'];
    $maths = (int)$_POST['maths'];
    $marks6 = (int)$_POST['marks6'];
    $marks7 = (int)$_POST['marks7'];
    $marks8 = (int)$_POST['marks8'];
    
    try {
        $query = "UPDATE NANA2024000_RESULT SET 
                  UR = ?, ENG = ?, ISL = ?, THQ = ?, PS = ?, 
                  MATHS = ?, MARKS6 = ?, MARKS7 = ?, MARKS8 = ? 
                  WHERE RNO = ?";
        
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $query);
        mysqli_stmt_bind_param($stmt, "iiiiiiiiii", $ur, $eng, $isl, $thq, $ps, $maths, $marks6, $marks7, $marks8, $rno);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true, 'message' => 'Result updated successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database update failed']);
        }
        
        mysqli_stmt_close($stmt);
        mysqli_close($con);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}
?>
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
        function maxLengthCheck(object) {
            if (object.value.length > object.maxLength) {
                object.value = object.value.slice(0, object.maxLength);
            }
        }

        // Disable right-click
        document.onmousedown = disableclick;
        status = "Right Click Disabled";
        function disableclick(event) {
            if (event.button == 2) {
                alert(status);
                return false;
            }
        }

        // Function to calculate grade based on percentage
        function calculateGrade(percentage) {
            if (percentage >= 80) return 'A+';
            else if (percentage >= 70) return 'A';
            else if (percentage >= 60) return 'B';
            else if (percentage >= 50) return 'C';
            else if (percentage >= 40) return 'D';
            else if (percentage >= 33) return 'E';
            else return 'F';
        }

        // Function to calculate percentile (simplified version)
        function calculatePercentile(obtainedMarks, totalMarks) {
            const percentage = (obtainedMarks / totalMarks) * 100;
            return Math.round(percentage * 100) / 100; // Round to 2 decimal places
        }

        // Function to update calculations for a subject
        function updateSubjectCalculations(marksInput, totalMarks, percentileCell, gradeCell) {
            const obtainedMarks = parseInt(marksInput.value) || 0;
            const total = parseInt(totalMarks);
            
            // Calculate and update percentile
            const percentage = (obtainedMarks / total) * 100;
            percentileCell.textContent = percentage.toFixed(2);
            
            // Calculate and update grade
            const grade = calculateGrade(percentage);
            gradeCell.textContent = grade;
        }

        // Function to update total marks
        function updateTotalMarks() {
            const marksInputs = document.querySelectorAll('.marks-input');
            let totalObtained = 0;
            
            marksInputs.forEach(input => {
                totalObtained += parseInt(input.value) || 0;
            });
            
            document.getElementById('totalObtained').textContent = totalObtained;
        }

        // Function to update all calculations when marks change
        function updateAllCalculations() {
            const marksInputs = document.querySelectorAll('.marks-input');
            
            marksInputs.forEach(input => {
                const row = input.closest('tr');
                const totalMarksCell = row.querySelector('.total-marks');
                const percentileCell = row.querySelector('.percentile-score');
                const gradeCell = row.querySelector('.relative-grade');
                
                if (totalMarksCell && percentileCell && gradeCell) {
                    updateSubjectCalculations(input, totalMarksCell.textContent, percentileCell, gradeCell);
                }
            });
            
            updateTotalMarks();
        }

        // JavaScript for editing and updating fields
        function toggleEdit() {
            const inputs = document.querySelectorAll('.editable');
            const editButton = document.getElementById('editButton');
            const updateButton = document.getElementById('updateButton');
            
            inputs.forEach(input => {
                input.disabled = !input.disabled;
                if (!input.disabled) {
                    input.classList.remove('bg-gray-50');
                    input.classList.add('bg-white');
                } else {
                    input.classList.remove('bg-white');
                    input.classList.add('bg-gray-50');
                }
            });
            
            if (editButton.textContent === 'Edit') {
                editButton.textContent = 'Cancel';
                updateButton.classList.remove('hidden');
                
                // Add event listeners for real-time updates
                const marksInputs = document.querySelectorAll('.marks-input');
                marksInputs.forEach(input => {
                    input.addEventListener('input', function() {
                        updateAllCalculations();
                    });
                });
                
            } else {
                editButton.textContent = 'Edit';
                updateButton.classList.add('hidden');
                
                // Remove event listeners
                const marksInputs = document.querySelectorAll('.marks-input');
                marksInputs.forEach(input => {
                    input.removeEventListener('input', updateAllCalculations);
                });
            }
        }

        function updateResult() {
            const rno = document.querySelector('input[name="current_rno"]').value;
            const marksInputs = document.querySelectorAll('.marks-input');
            
            // Collect all marks
            const marksData = {
                action: 'update_result',
                rno: rno,
                ur: marksInputs[0].value,
                eng: marksInputs[1].value,
                isl: marksInputs[2].value,
                thq: marksInputs[3].value,
                ps: marksInputs[4].value,
                maths: marksInputs[5].value,
                marks6: marksInputs[6].value,
                marks7: marksInputs[7].value,
                marks8: marksInputs[8].value
            };
            
            // Send AJAX request
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            alert(response.message);
                            toggleEdit(); // Disable inputs after successful update
                        } else {
                            alert('Error: ' + response.message);
                        }
                    } catch (e) {
                        alert('Update successful!');
                        toggleEdit();
                    }
                }
            };
            
            // Convert data to URL encoded format
            const urlEncodedData = Object.keys(marksData).map(key => 
                encodeURIComponent(key) + '=' + encodeURIComponent(marksData[key])
            ).join('&');
            
            xhr.send(urlEncodedData);
        }

        function checkEmpty() {
            const rno = document.forms["slip"]["rno"].value;
            if (rno === "") {
                alert("Please enter Roll Number");
                return false;
            }
            return true;
        }
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
                           oninput="maxLengthCheck(this)" class="border rounded-lg p-2 w-full focus:outline-none focus:ring-2 focus:ring-blue-500"/>
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
            $DOB3 = $_POST['DOB'] ?? '';
            $DOB2 = date_create($DOB3);
            $DOB = date_format($DOB2, 'Y-m-d');
        } else {
            echo ".";
            exit();
        }
        $rno = escape($rno);
        if (strlen($rno) < 6 || strlen($rno) > 6) {
            echo "<div class='text-red-600 font-bold text-center'>Roll No. consist of 6 digits only.</div>";
            exit();
        }

        if ($rno < 100000 || $rno > 360000) {
            echo "<div class='text-red-600 font-bold text-center'>Roll No. range is from 100000 to 200000 OR 300000 to 360000 only.</div>";
            exit();
        }

        $rno = filter_var($rno, FILTER_SANITIZE_NUMBER_INT);
        if (filter_var($rno, FILTER_VALIDATE_INT)) {
        } else {
            echo "<div class='text-red-600 font-bold text-center'>INVALID ROLL NO.</div>";
            exit;
        }

        $data = array();
        if (!filter_var($rno, FILTER_VALIDATE_INT) === false || !filter_var($rno, FILTER_VALIDATE_INT) === 0) {
            $query = "select * from NANA2024000_RESULT where RNO=?;";
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

            <!-- Hidden input to store current roll number for AJAX -->
            <input type="hidden" name="current_rno" value="<?php echo trim($data['RNO']); ?>">

            <!-- Result Section -->
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
                                    <input type="number" value="<?php echo trim($data['UR']); ?>" class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold percentile-score"><?php echo ($data['SUB1_PER']); ?></td>
                                <td class="border p-3 text-center font-bold relative-grade"><?php echo trim($data['SUB1_GRADE']); ?></td>
                                <td class="border p-3 text-center"><?php echo trim($data['PASS1']) == '1' ? 'PASS' : (trim($data['PASS1']) == '0' ? '' : ''); ?></td>
                            </tr>
                            <!-- English -->
                            <tr class="hover:bg-gray-50">
                                <td class="border p-3 text-lg uppercase">English</td>
                                <td class="border p-3 text-center font-bold total-marks"><?php echo trim($data['SUB2_TMARKS']); ?></td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" value="<?php echo trim($data['ENG']); ?>" class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold percentile-score"><?php echo ($data['SUB2_PER']); ?></td>
                                <td class="border p-3 text-center font-bold relative-grade"><?php echo trim($data['SUB2_GRADE']); ?></td>
                                <td class="border p-3 text-center"><?php echo trim($data['PASS2']) == '1' ? 'PASS' : (trim($data['PASS2']) == '0' ? '' : ''); ?></td>
                            </tr>
                            <!-- Subject 3 -->
                            <tr class="hover:bg-gray-50">
                                <td class="border p-3 text-lg uppercase"><?php echo trim($data['APPEAR3']); ?></td>
                                <td class="border p-3 text-center font-bold total-marks"><?php echo trim($data['SUB3_TMARKS']); ?></td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" value="<?php echo trim($data['ISL']); ?>" class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold percentile-score"><?php echo trim($data['SUB3_PER']); ?></td>
                                <td class="border p-3 text-center font-bold relative-grade"><?php echo trim($data['SUB3_GRADE']); ?></td>
                                <td class="border p-3 text-center"><?php echo trim($data['PASS3']) == '1' ? 'PASS' : (trim($data['PASS3']) == '0' ? '' : ''); ?></td>
                            </tr>
                            <!-- Subject 9 -->
                            <tr class="hover:bg-gray-50">
                                <td class="border p-3 text-lg uppercase"><?php echo trim($data['APPEAR9']); ?></td>
                                <td class="border p-3 text-center font-bold total-marks"><?php echo trim($data['SUB9_TMARKS']); ?></td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" value="<?php echo trim($data['THQ']); ?>" class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold percentile-score"><?php echo trim($data['SUB9_PER']); ?></td>
                                <td class="border p-3 text-center font-bold relative-grade"><?php echo trim($data['SUB9_GRADE']); ?></td>
                                <td class="border p-3 text-center"><?php echo trim($data['PASS9']) == '1' ? 'PASS' : (trim($data['PASS9']) == '0' ? '' : ''); ?></td>
                            </tr>
                            <!-- Pakistan Studies -->
                            <tr class="hover:bg-gray-50">
                                <td class="border p-3 text-lg uppercase">Pakistan Studies</td>
                                <td class="border p-3 text-center font-bold total-marks"><?php echo trim($data['SUB4_TMARKS']); ?></td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" value="<?php echo trim($data['PS']); ?>" class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold percentile-score"><?php echo trim($data['SUB4_PER']); ?></td>
                                <td class="border p-3 text-center font-bold relative-grade"><?php echo trim($data['SUB4_GRADE']); ?></td>
                                <td class="border p-3 text-center"><?php echo trim($data['PASS4']) == '1' ? 'PASS' : (trim($data['PASS4']) == '0' ? '' : ''); ?></td>
                            </tr>
                            <!-- Subject 5 -->
                            <tr class="hover:bg-gray-50">
                                <td class="border p-3 text-lg uppercase"><?php echo trim($data['APPEAR5']); ?></td>
                                <td class="border p-3 text-center font-bold total-marks"><?php echo trim($data['SUB5_TMARKS']); ?></td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" value="<?php echo trim($data['MATHS']); ?>" class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold percentile-score"><?php echo trim($data['SUB5_PER']); ?></td>
                                <td class="border p-3 text-center font-bold relative-grade"><?php echo trim($data['SUB5_GRADE']); ?></td>
                                <td class="border p-3 text-center"><?php echo trim($data['PASS5']) == '1' ? 'PASS' : (trim($data['PASS5']) == '0' ? '' : ''); ?></td>
                            </tr>
                            <!-- Subject 6 -->
                            <tr class="hover:bg-gray-50">
                                <td class="border p-3 text-lg uppercase"><?php echo trim($data['APPEAR6']); ?></td>
                                <td class="border p-3 text-center font-bold total-marks"><?php echo trim($data['SUB6_TMARKS']); ?></td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" value="<?php echo trim($data['MARKS6']); ?>" class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold percentile-score"><?php echo trim($data['SUB6_PER']); ?></td>
                                <td class="border p-3 text-center font-bold relative-grade"><?php echo trim($data['SUB6_GRADE']); ?></td>
                                <td class="border p-3 text-center"><?php echo trim($data['PASS6']) == '1' ? 'PASS' : (trim($data['PASS6']) == '0' ? '' : ''); ?></td>
                            </tr>
                            <!-- Subject 7 -->
                            <tr class="hover:bg-gray-50">
                                <td class="border p-3 text-lg uppercase"><?php echo trim($data['APPEAR7']); ?></td>
                                <td class="border p-3 text-center font-bold total-marks"><?php echo trim($data['SUB7_TMARKS']); ?></td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" value="<?php echo trim($data['MARKS7']); ?>" class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold percentile-score"><?php echo trim($data['SUB7_PER']); ?></td>
                                <td class="border p-3 text-center font-bold relative-grade"><?php echo trim($data['SUB7_GRADE']); ?></td>
                                <td class="border p-3 text-center"><?php echo trim($data['PASS7']) == '1' ? 'PASS' : (trim($data['PASS7']) == '0' ? '' : ''); ?></td>
                            </tr>
                            <!-- Subject 8 -->
                            <tr class="hover:bg-gray-50">
                                <td class="border p-3 text-lg uppercase"><?php echo trim($data['APPEAR8']); ?></td>
                                <td class="border p-3 text-center font-bold total-marks"><?php echo trim($data['SUB8_TMARKS']); ?></td>
                                <td class="border p-3 text-center font-bold">
                                    <input type="number" value="<?php echo trim($data['MARKS8']); ?>" class="marks-input editable border rounded-lg p-1 w-20 text-center bg-gray-50" disabled>
                                </td>
                                <td class="border p-3 text-center font-bold percentile-score"><?php echo trim($data['SUB8_PER']); ?></td>
                                <td class="border p-3 text-center font-bold relative-grade"><?php echo trim($data['SUB8_GRADE']); ?></td>
                                <td class="border p-3 text-center"><?php echo trim($data['PASS8']) == '1' ? 'PASS' : (trim($data['PASS8']) == '0' ? '' : ''); ?></td>
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
                                <td class="border p-3 text-center font-bold">
                                    <span id="totalObtained"><?php 
                                        $OBTAINED_TOTAL = trim($data['UR']) +
                                            trim($data['ENG']) +
                                            trim($data['ISL']) +
                                            trim($data['THQ']) +
                                            trim($data['PS']) +
                                            trim($data['MATHS']) +
                                            trim($data['MARKS6']) +
                                            trim($data['MARKS7']) +
                                            trim($data['MARKS8']);
                                        echo $OBTAINED_TOTAL;
                                        ?></span>
                                </td>
                                <td class="border p-3 text-center font-bold"><?php echo trim($data['GAZRES']); ?></td>
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
        <?php } else { ?>
            <div class="text-red-600 font-bold text-center">Invalid input</div>
        <?php } ?>
    </div>
</body>
</html>
