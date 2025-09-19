<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8"/>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, td, th {
            border: 1px solid black;
            padding: 5px;
        }
        th {
            text-align: left;
        }
        .input {
            font-size: 18px;
            font-weight: bold;
            font-family: verdana;
            color: red;
        }
        .noBorder {
            border: none !important;
            padding: 0px !important;
        }
        .verdana {
            font-family: verdana;
        }
        .clear {
            clear: both;
        }
        .pk {
            height: 400px;
            position: relative;
        }
        .shadow {
            box-shadow: 0px 0px 5px #999;
        }
        .editable {
            width: 100%;
            font-size: 18px;
            font-family: verdana;
        }
        @media print {
            @page {
                margin-top: 0;
                margin-bottom: 0;
            }
            body {
                padding-top: 72px;
                padding-bottom: 72px;
            }
        }
    </style>
    <script>
        function maxLengthCheck(object) {
            if (object.value.length > object.maxLength)
                object.value = object.value.slice(0, object.maxLength);
        }

        function calculateTotal() {
            let marks = [
                parseInt(document.getElementById('ur_marks').value) || 0,
                parseInt(document.getElementById('eng_marks').value) || 0,
                parseInt(document.getElementById('isl_marks').value) || 0,
                parseInt(document.getElementById('ps_marks').value) || 0,
                parseInt(document.getElementById('maths_marks').value) || 0,
                parseInt(document.getElementById('marks6').value) || 0,
                parseInt(document.getElementById('marks7').value) || 0,
                parseInt(document.getElementById('marks8').value) || 0,
                parseInt(document.getElementById('thq_marks').value) || 0
            ];
            let total = marks.reduce((sum, curr) => sum + curr, 0);
            document.getElementById('total_marks').value = total;
        }

        function disableclick(event) {
            if (event.button == 2) {
                alert("Right Click Disabled");
                return false;
            }
        }
        document.onmousedown = disableclick;
    </script>
</head>
<body>
    <center>
        <div style="width:880px; height:auto; margin-left:-172px;">
            <h1 style="font-size:18px; font-family:verdana; font-weight:bold; margin-left:100px;">
                BOARD OF INTERMEDIATE AND SECONDARY EDUCATION DERA GHAZI KHAN
            </h1>
            <h2 style="color:#000066; margin-top:-17px; font-family:verdana; font-size:22px;">
                Online Result For SSC (Part-I) 1<sup>st</sup> Annual 2024
            </h2>
            <div style="width:600px; height:auto; vertical-align:middle; padding-left:30px;">
                <form method="post" name="slip" action="" onsubmit="return checkEmpty();">
                    <table style="padding-top:20px;">
                        <tr>
                            <td colspan="2"><h3 style="padding-left:150px;">Search By Roll No</h3></td>
                        </tr>
                        <tr>
                            <td><span style="font-size:16px; font-weight:bold;">Enter Your Roll No</span></td>
                            <td>
                                <input type="number" maxlength="6" name="rno" value="" 
                                       placeholder="100001-200000 or 300000-360000" 
                                       oninput="maxLengthCheck(this)" maxlength="6"/>
                            </td>
                        </tr>
                        <tr>
                            <td></td>
                            <td><input type="submit" value="Search for Result"/></td>
                        </tr>
                    </table>
                </form>
            </div>
        </div>
    </center>
    <br><br><br><br><br><br><br><br>

    <?php
    include_once('config_seci.php');

    function escape($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
        // Handle update request
        $rno = (int)$_POST['rno'];
        $name = escape($_POST['name']);
        $father = escape($_POST['father']);
        $regno = escape($_POST['regno']);
        $ur_marks = (int)$_POST['ur_marks'];
        $eng_marks = (int)$_POST['eng_marks'];
        $isl_marks = (int)$_POST['isl_marks'];
        $ps_marks = (int)$_POST['ps_marks'];
        $maths_marks = (int)$_POST['maths_marks'];
        $marks6 = (int)$_POST['marks6'];
        $marks7 = (int)$_POST['marks7'];
        $marks8 = (int)$_POST['marks8'];
        $thq_marks = (int)$_POST['thq_marks'];
        $total_marks = (int)$_POST['total_marks'];

        // Recalculate percentiles and grades (example logic, adjust as per your grading system)
        $sub1_per = ($ur_marks / (int)$_POST['sub1_tmarks']) * 100;
        $sub2_per = ($eng_marks / (int)$_POST['sub2_tmarks']) * 100;
        $sub3_per = ($isl_marks / (int)$_POST['sub3_tmarks']) * 100;
        $sub4_per = ($ps_marks / (int)$_POST['sub4_tmarks']) * 100;
        $sub5_per = ($maths_marks / (int)$_POST['sub5_tmarks']) * 100;
        $sub6_per = ($marks6 / (int)$_POST['sub6_tmarks']) * 100;
        $sub7_per = ($marks7 / (int)$_POST['sub7_tmarks']) * 100;
        $sub8_per = ($marks8 / (int)$_POST['sub8_tmarks']) * 100;
        $sub9_per = ($thq_marks / (int)$_POST['sub9_tmarks']) * 100;

        $sub1_grade = $sub1_per >= 80 ? 'A+' : ($sub1_per >= 70 ? 'A' : ($sub1_per >= 60 ? 'B' : 'C'));
        $sub2_grade = $sub2_per >= 80 ? 'A+' : ($sub2_per >= 70 ? 'A' : ($sub2_per >= 60 ? 'B' : 'C'));
        $sub3_grade = $sub3_per >= 80 ? 'A+' : ($sub3_per >= 70 ? 'A' : ($sub3_per >= 60 ? 'B' : 'C'));
        $sub4_grade = $sub4_per >= 80 ? 'A+' : ($sub4_per >= 70 ? 'A' : ($sub4_per >= 60 ? 'B' : 'C'));
        $sub5_grade = $sub5_per >= 80 ? 'A+' : ($sub5_per >= 70 ? 'A' : ($sub5_per >= 60 ? 'B' : 'C'));
        $sub6_grade = $sub6_per >= 80 ? 'A+' : ($sub6_per >= 70 ? 'A' : ($sub6_per >= 60 ? 'B' : 'C'));
        $sub7_grade = $sub7_per >= 80 ? 'A+' : ($sub7_per >= 70 ? 'A' : ($sub7_per >= 60 ? 'B' : 'C'));
        $sub8_grade = $sub8_per >= 80 ? 'A+' : ($sub8_per >= 70 ? 'A' : ($sub8_per >= 60 ? 'B' : 'C'));
        $sub9_grade = $sub9_per >= 80 ? 'A+' : ($sub9_per >= 70 ? 'A' : ($sub9_per >= 60 ? 'B' : 'C'));

        $pass1 = $sub1_per >= 33 ? 1 : 0;
        $pass2 = $sub2_per >= 33 ? 1 : 0;
        $pass3 = $sub3_per >= 33 ? 1 : 0;
        $pass4 = $sub4_per >= 33 ? 1 : 0;
        $pass5 = $sub5_per >= 33 ? 1 : 0;
        $pass6 = $sub6_per >= 33 ? 1 : 0;
        $pass7 = $sub7_per >= 33 ? 1 : 0;
        $pass8 = $sub8_per >= 33 ? 1 : 0;
        $pass9 = $sub9_per >= 33 ? 1 : 0;

        $pass = ($pass1 && $pass2 && $pass3 && $pass4 && $pass5 && $pass6 && $pass7 && $pass8 && $pass9) ? 1 : 0;
        $gazres = $pass ? 'PASS' : 'FAIL';

        $query = "UPDATE NANA2024000_RESULT SET 
                  NAME = ?, FATHER = ?, REGNO = ?, 
                  UR = ?, SUB1_PER = ?, SUB1_GRADE = ?, PASS1 = ?,
                  ENG = ?, SUB2_PER = ?, SUB2_GRADE = ?, PASS2 = ?,
                  ISL = ?, SUB3_PER = ?, SUB3_GRADE = ?, PASS3 = ?,
                  PS = ?, SUB4_PER = ?, SUB4_GRADE = ?, PASS4 = ?,
                  MATHS = ?, SUB5_PER = ?, SUB5_GRADE = ?, PASS5 = ?,
                  MARKS6 = ?, SUB6_PER = ?, SUB6_GRADE = ?, PASS6 = ?,
                  MARKS7 = ?, SUB7_PER = ?, SUB7_GRADE = ?, PASS7 = ?,
                  MARKS8 = ?, SUB8_PER = ?, SUB8_GRADE = ?, PASS8 = ?,
                  THQ = ?, SUB9_PER = ?, SUB9_GRADE = ?, PASS9 = ?,
                  GAZRES = ?, TOTAL = ?
                  WHERE RNO = ?";

        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, $query);
        mysqli_stmt_bind_param($stmt, "ssssdsisdsisdsisdsisdsisdsisdsisdsisdi",
            $name, $father, $regno,
            $ur_marks, $sub1_per, $sub1_grade, $pass1,
            $eng_marks, $sub2_per, $sub2_grade, $pass2,
            $isl_marks, $sub3_per, $sub3_grade, $pass3,
            $ps_marks, $sub4_per, $sub4_grade, $pass4,
            $maths_marks, $sub5_per, $sub5_grade, $pass5,
            $marks6, $sub6_per, $sub6_grade, $pass6,
            $marks7, $sub7_per, $sub7_grade, $pass7,
            $marks8, $sub8_per, $sub8_grade, $pass8,
            $thq_marks, $sub9_per, $sub9_grade, $pass9,
            $gazres, $total_marks, $rno
        );

        if (mysqli_stmt_execute($stmt)) {
            echo "<span class='input'>Record updated successfully!</span>";
        } else {
            echo "<span class='input'>Error updating record: " . mysqli_error($con) . "</span>";
        }
        mysqli_stmt_close($stmt);
    }

    $rno = 0;
    if (isset($_POST['rno'])) {
        $rno = (int)($_POST['rno']);
    } else {
        echo ".";
        exit();
    }

    $rno = escape($rno);
    if (strlen($rno) != 6) {
        echo "<span class='input'>Roll No. must be 6 digits.</span>";
        exit();
    }

    if ($rno < 100000 || ($rno > 200000 && $rno < 300000) || $rno > 360000) {
        echo "<span class='input'>Roll No. range is from 100000 to 200000 or 300000 to 360000.</span>";
        exit();
    }

    $rno = filter_var($rno, FILTER_SANITIZE_NUMBER_INT);
    if (!filter_var($rno, FILTER_VALIDATE_INT)) {
        echo "INVALID ROLL NO.";
        exit;
    }

    $query = "SELECT * FROM NANA2024000_RESULT WHERE RNO = ?";
    $stmt = mysqli_stmt_init($con);
    mysqli_stmt_prepare($stmt, $query);
    mysqli_stmt_bind_param($stmt, "i", $rno);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) < 1) {
        echo "<center><span>Wrong Roll No</span></center>";
        exit;
    }

    $data = mysqli_fetch_array($result);
    $DISTT = trim($data['DISTRICT_N']);
    $INST_CODE = trim($data['STATUS']);
    $OBJECTION = trim($data['OBJECTION']);
    $OBJECTION2 = trim($data['OBJECTION2']);

    if ($OBJECTION == 1 || $OBJECTION2 == 1) {
        echo "<center><span style='font-weight:bold; font-size:18px; font-family:Arial, Helvetica, sans-serif;'>";
        echo "Sr No: " . trim($data['SRNO']) . "<br><br>";
        echo "Roll No: " . trim($data['RNO']) . "<br><br>";
        echo "Name: " . trim($data['NAME']) . "<br><br>";
        echo ($data['GAZRES'] == 'ABSENT') ? "Result: " : "Result Block/Objection: ";
        echo trim($data['GAZRES']);
        echo "</span></center>";
        exit;
    }
    ?>

    <center>
        <div id="MAIN" style="width:1040px; margin:0 auto;">
            <div id="HEADER" style="width:960px; float:left; margin:0 auto;">
                <div id="INN-HEADER">
                    <div id="IMAGE" style="width:100px; height:70px; float:left;">
                        <img width="100px" height="100px" src="images/logo.gif">
                    </div>
                    <div id="CONTENT-HEADER" style="width:860px; height:70px; float:left; padding-top:15px;">
                        <span style="font-family:verdana; font-size:23.5px; font-weight:bold;">
                            Board Of Intermediate & Secondary Education, Dera Ghazi Khan
                        </span><br>
                        <form method="post" action="">
                            <input type="hidden" name="rno" value="<?php echo trim($data['RNO']); ?>">
                            <span style="font-family:verdana; font-size:15px; float:left;">
                                SECONDARY SCHOOL CERTIFICATE (PART-I) 1<sup>st</sup> ANNUAL EXAMINATION 2024
                            </span><br>
                            <span style="float:left; width:300px; font-weight:bold; font-size:17px; padding-bottom:40px;">
                                GROUP: <?php
                                $SGROUP = trim($data['S_GROUP']);
                                echo $SGROUP == 'S' ? 'SCIENCE' : ($SGROUP == 'G' ? 'GENERAL' : 'DEAF & DUMB');
                                ?>
                            </span>
                            <table class="noBorder">
                                <tr>
                                    <td class="noBorder">
                                        <span style="float:left; margin-left:25px; font-size:18px;">Roll No:</span>
                                        <input type="text" name="rno_display" class="editable" value="<?php echo trim($data['RNO']); ?>" readonly>
                                    </td>
                                    <td class="noBorder">
                                        <span style="float:left; margin-left:50px; font-size:18px;">Sr. No:</span>
                                        <input type="text" name="srno" class="editable" value="<?php echo trim($data['SRNO']); ?>">
                                    </td>
                                    <td class="noBorder">
                                        <span style="float:right; margin-left:10px; font-size:18px; font-weight:bold;">Reg No:</span>
                                        <input type="text" name="regno" class="editable" value="<?php echo trim($data['REGNO']); ?>">
                                    </td>
                                </tr>
                            </table>
                    </div>
                </div>
                <br><br>
                <div class="clear"></div>
                <div id="OUTER-HEADER" style="width:960px; float:left; margin-top:50px; margin-bottom:20px;">
                    <div id="L-HEAD" style="float:left; width:840px;">
                        <table class="noBorder">
                            <tr>
                                <td class="noBorder">
                                    <span style="float:left; margin-left:25px; font-size:18px;">Candidate's Name:</span>
                                    <input type="text" name="name" class="editable" value="<?php echo trim($data['NAME']); ?>">
                                </td>
                            </tr>
                            <tr>
                                <td class="noBorder">
                                    <span style="float:left; margin-left:25px; font-size:18px;">Father's Name:</span>
                                    <input type="text" name="father" class="editable" value="<?php echo trim($data['FATHER']); ?>">
                                </td>
                            </tr>
                            <tr>
                                <td class="noBorder">
                                    <span style="float:left; margin-left:25px; font-size:18px;">Institute/District:</span>
                                    <span style="float:left; margin-left:37px; font-family:verdana; font-size:14px;">
                                        <?php echo $INST_CODE == 'R' ? trim($data['INST_NAME']) : $DISTT; ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="clear"></div>

            <div class="pk">
                <div id="CONTENT">
                    <table width="100%" cellpadding="10" cellspacing="0" border="0">
                        <tr bgcolor="#99CCCC">
                            <td class="heading" style="font-size:17px; font-weight:bold; font-family:Arial, Helvetica, sans-serif; line-height:23px;">NAME OF SUBJECT</td>
                            <td class="heading" style="font-size:17px; font-weight:bold; font-family:Arial, Helvetica, sans-serif;">TOTAL <br> MARKS</td>
                            <td class="heading" style="font-size:17px; font-weight:bold; font-family:Arial, Helvetica, sans-serif;">MARKS <br> OBTAINED</td>
                            <td class="heading" style="font-size:17px; font-weight:bold; font-family:Arial, Helvetica, sans-serif;">PERCENTILE <br> SCORE</td>
                            <td class="heading" style="font-size:17px; font-weight:bold; font-family:Arial, Helvetica, sans-serif;">RELATIVE <br> GRADE</td>
                            <td class="heading" style="font-size:17px; font-weight:bold; font-family:Arial, Helvetica, sans-serif;">REMARKS</td>
                        </tr>
                        <!-- Urdu -->
                        <tr class="sp">
                            <td class="shadow" style="font-size:18px; text-transform:uppercase; font-family:Arial, Helvetica, sans-serif;">Urdu</td>
                            <td class="shadow" style="text-align:center; font-weight:bold; font-size:19px;">
                                <input type="number" name="sub1_tmarks" class="editable" value="<?php echo trim($data['SUB1_TMARKS']); ?>" readonly>
                            </td>
                            <td class="shadow" style="text-align:center; font-weight:bold; font-size:18px;">
                                <input type="number" name="ur_marks" id="ur_marks" class="editable" value="<?php echo trim($data['UR']); ?>" oninput="calculateTotal()">
                            </td>
                            <td class="shadow" style="text-align:center; font-weight:bold; font-size:19px;"><?php echo trim($data['SUB1_PER']); ?></td>
                            <td class="shadow" style="text-align:center; font-weight:bold; font-size:19px;"><?php echo trim($data['SUB1_GRADE']); ?></td>
                            <td class="shadow" style="text-align:center; font-size:16px; font-family:Arial, Helvetica, sans-serif;">
                                <?php echo trim($data['PASS1']) == '1' ? 'PASS' : (trim($data['PASS1']) == '0' ? '' : ''); ?>
                            </td>
                        </tr>
                        <!-- English -->
                        <tr class="sp">
                            <td class="shadow" style="font-size:18px; text-transform:uppercase; font-family:Arial, Helvetica, sans-serif;">English</td>
                            <td class="shadow" style="text-align:center; font-weight:bold; font-size:19px;">
                                <input type="number" name="sub2_tmarks" class="editable" value="<?php echo trim($data['SUB2_TMARKS']); ?>" readonly>
                            </td>
                            <td class="shadow" style="text-align:center; font-weight:bold; font-size:18px;">
                                <input type="number" name="eng_marks" id="eng_marks" class="editable" value="<?php echo trim($data['ENG']); ?>" oninput="calculateTotal()">
                            </td>
                            <td class="shadow" style="text-align:center; font-weight:bold; font-size:19px;"><?php echo trim($data['SUB2_PER']); ?></td>
                            <td class="shadow" style="text-align:center; font-weight:bold; font-size:19px;"><?php echo trim($data['SUB2_GRADE']); ?></td>
                            <td class="shadow" style="text-align:center; font-size:16px; font-family:Arial, Helvetica, sans-serif;">
                                <?php echo trim($data['PASS2']) == '1' ? 'PASS' : (trim($data['PASS2']) == '0' ? '' : ''); ?>
                            </td>
                        </tr>
                        <!-- Islamiyat -->
                        <tr class="sp">
                            <td class="shadow" style="font-size:18px; text-transform:uppercase; font-family:Arial, Helvetica, sans-serif;">
                                <?php echo trim($data['APPEAR3']); ?>
                            </td>
                            <td class="shadow" style="text-align:center; font-weight:bold; font-size:19px;">
                                <input type="number" name="sub3_tmarks" class="editable" value="<?php echo trim($data['SUB3_TMARKS']); ?>" readonly>
                            </td>
                            <td class="shadow" style="text-align:center; font-weight:bold; font-size:18px;">
                                <input type="number" name="isl_marks" id="isl_marks" class="editable" value="<?php echo trim($data['ISL']); ?>" oninput="calculateTotal()">
                            </td>
                            <td class="shadow" style="text-align:center; font-weight:bold; font-size:19px;"><?php echo trim($data['SUB3_PER']); ?></td>
                            <td class="shadow" style="text-align:center; font-weight:bold; font-size:19px;"><?php echo trim($data['SUB3_GRADE']); ?></td>
                            <td class="shadow" style="text-align:center; font-size:16px; font-family:Arial, Helvetica, sans-serif;">
                                <?php echo trim($data['PASS3']) == '1' ? 'PASS' : (trim($data['PASS3']) == '0' ? '' : ''); ?>
                            </td>
                        </tr>
                        <!-- Tarjuma-tul-Quran -->
                        <tr class="sp">
                            <td class="shadow" style="font-size:18px; text-transform:uppercase; font-family:Arial, Helvetica, sans-serif;">
                                <?php echo trim($data['APPEAR9']); ?>
                            </td>
                            <td class="shadow" style="text-align:center; font-weight:bold; font-size:19px;">
                                <input type="number" name="sub9_tmarks" class="editable" value="<?php echo trim($data['SUB9_TMARKS']); ?>" readonly>
                            </td>
                            <td class="shadow" style="text-align:center; font-weight:bold; font-size:18px;">
                                <input type="number" name="thq_marks" id="thq_marks" class="editable" value="<?php echo trim($data['THQ']); ?>" oninput="calculateTotal()">
                            </td>
                            <td class="shadow" style="text-align:center; font-weight:bold; font-size:19px;"><?php echo trim($data['SUB9_PER']); ?></td>
                            <td class="shadow" style="text-align:center; font-weight:bold; font-size:19px;"><?php echo trim($data['SUB9_GRADE']); ?></td>
                            <td class="shadow" style="text-align:center; font-size:16px; font-family:Arial, Helvetica, sans-serif;">
                                <?php echo trim($data['PASS9']) == '1' ? 'PASS' : (trim($data['PASS9']) == '0' ? '' : ''); ?>
                            </td>
                        </tr>
                        <!-- Pakistan Studies -->
                        <tr class="sp">
                            <td class="shadow" style="font-size:18px; text-transform:uppercase; font-family:Arial, Helvetica, sans-serif;">Pakistan Studies</td>
                            <td class="shadow" style="text-align:center; font-weight:bold; font-size:19px;">
                                <input type="number" name="sub4_tmarks" class="editable" value="<?php echo trim($data['SUB4_TMARKS']); ?>" readonly>
                            </td>
                            <td class="shadow" style="text-align:center; font-weight:bold; font-size:18px;">
                                <input type="number" name="ps_marks" id="ps_marks" class="editable" value="<?php echo trim($data['PS']); ?>" oninput="calculateTotal()">
                            </td>
                            <td class="shadow" style="text-align:center; font-weight:bold; font-size:19px;"><?php echo trim($data['SUB4_PER']); ?></td>
                            <td class="shadow" style="text-align:center; font-weight:bold; font-size:19px;"><?php echo trim($data['SUB4_GRADE']); ?></td>
                            <td class="shadow" style="text-align:center; font-size:16px; font-family:Arial, Helvetica, sans-serif;">
                                <?php echo trim($data['PASS4']) == '1' ? 'PASS' : (trim($data['PASS4']) == '0' ? '' : ''); ?>
                            </td>
                        </tr>
                        <!-- Mathematics -->
                        <tr>
                            <td class="shadow" style="font-size:18px; text-transform:uppercase; font-family:Arial, Helvetica, sans-serif;">
                                <?php echo trim($data['APPEAR5']); ?>
                            </td>
                            <td class="shadow" style="text-align:center; font-weight:bold; font-size:19px;">
                                <input type="number" name="sub5_tmarks" class="editable" value="<?php echo trim($data['SUB5_TMARKS']); ?>" readonly>
                            </td>
                            <td class="shadow" style="text-align:center; font-weight:bold; font-size:18px;">
                                <input type="number" name="maths_marks" id="maths_marks" class="editable" value="<?php echo trim($data['MATHS']); ?>" oninput="calculateTotal()">
                            </td>
                            <td class="shadow" style="text-align:center; font-weight:bold; font-size:19px;"><?php echo trim($data['SUB5_PER']); ?></td>
                            <td class="shadow" style="text-align:center; font-weight:bold; font-size:19px;"><?php echo trim($data['SUB5_GRADE']); ?></td>
                            <td class="shadow" style="text-align:center; font-size:16px; font-family:Arial, Helvetica, sans-serif;">
                                <?php echo trim($data['PASS5']) == '1' ? 'PASS' : (trim($data['PASS5']) == '0' ? '' : ''); ?>
                            </td>
                        </tr>
                        <!-- Subject 6 -->
                        <tr class="shade">
                            <td class="shadow" style="font-size:18px; text-transform:uppercase; font-family:Arial, Helvetica, sans-serif;">
                                <?php echo trim($data['APPEAR6']); ?>
                            </td>
                            <td class="shadow" style="text-align:center; font-weight:bold; font-size:19px;">
                                <input type="number" name="sub6_tmarks" class="editable" value="<?php echo trim($data['SUB6_TMARKS']); ?>" readonly>
                            </td>
                            <td class="shadow" style="text-align:center; font-weight:bold; font-size:18px;">
                                <input type="number" name="marks6" id="marks6" class="editable" value="<?php echo trim($data['MARKS6']); ?>" oninput="calculateTotal()">
                            </td>
                            <td class="shadow" style="text-align:center; font-weight:bold; font-size:19px;"><?php echo trim($data['SUB6_PER']); ?></td>
                            <td class="shadow" style="text-align:center; font-weight:bold; font-size:19px;"><?php echo trim($data['SUB6_GRADE']); ?></td>
                            <td class="shadow" style="text-align:center; font-size:16px; font-family:Arial, Helvetica, sans-serif;">
                                <?php echo trim($data['PASS6']) == '1' ? 'PASS' : (trim($data['PASS6']) == '0' ? '' : ''); ?>
                            </td>
                        </tr>
                        <!-- Subject 7 -->
                        <tr>
                            <td class="shadow" style="font-size:18px; text-transform:uppercase; font-family:Arial, Helvetica, sans-serif;">
                                <?php echo trim($data['APPEAR7']); ?>
                            </td>
                            <td class="shadow" style="text-align:center; font-weight:bold; font-size:19px;">
                                <input type="number" name="sub7_tmarks" class="editable" value="<?php echo trim($data['SUB7_TMARKS']); ?>" readonly>
                            </td>
                            <td class="shadow" style="text-align:center; font-weight:bold; font-size:18px;">
                                <input type="number" name="marks7" id="marks7" class="editable" value="<?php echo trim($data['MARKS7']); ?>" oninput="calculateTotal()">
                            </td>
                            <td class="shadow" style="text-align:center; font-weight:bold; font-size:19px;"><?php echo trim($data['SUB7_PER']); ?></td>
                            <td class="shadow" style="text-align:center; font-weight:bold; font-size:19px;"><?php echo trim($data['SUB7_GRADE']); ?></td>
                            <td class="shadow" style="text-align:center; font-size:16px; font-family:Arial, Helvetica, sans-serif;">
                                <?php echo trim($data['PASS7']) == '1' ? 'PASS' : (trim($data['PASS7']) == '0' ? '' : ''); ?>
                            </td>
                        </tr>
                        <!-- Subject 8 -->
                        <tr class="shade">
                            <td class="shadow" style="font-size:18px; text-transform:uppercase; font-family:Arial, Helvetica, sans-serif;">
                                <?php echo trim($data['APPEAR8']); ?>
                            </td>
                            <td class="shadow" style="text-align:center; font-weight:bold; font-size:19px;">
                                <input type="number" name="sub8_tmarks" class="editable" value="<?php echo trim($data['SUB8_TMARKS']); ?>" readonly>
                            </td>
                            <td class="shadow" style="text-align:center; font-weight:bold; font-size:18px;">
                                <input type="number" name="marks8" id="marks8" class="editable" value="<?php echo trim($data['MARKS8']); ?>" oninput="calculateTotal()">
                            </td>
                            <td class="shadow" style="text-align:center; font-weight:bold; font-size:19px;"><?php echo trim($data['SUB8_PER']); ?></td>
                            <td class="shadow" style="text-align:center; font-weight:bold; font-size:19px;"><?php echo trim($data['SUB8_GRADE']); ?></td>
                            <td class="shadow" style="text-align:center; font-size:16px; font-family:Arial, Helvetica, sans-serif;">
                                <?php echo trim($data['PASS8']) == '1' ? 'PASS' : (trim($data['PASS8']) == '0' ? '' : ''); ?>
                            </td>
                        </tr>
                        <!-- Total -->
                        <tr class="grey">
                            <td class="heading" style="font-size:18px; font-weight:bold;">Result</td>
                            <td class="heading" style="text-align:center; font-weight:bold; font-size:19px;">
                                <input type="number" name="total_marks" id="total_marks" class="editable" value="<?php 
                                    $GTOTAL = trim($data['SUB1_TMARKS']) + trim($data['SUB2_TMARKS']) + trim($data['SUB3_TMARKS']) + 
                                              trim($data['SUB4_TMARKS']) + trim($data['SUB5_TMARKS']) + trim($data['SUB6_TMARKS']) + 
                                              trim($data['SUB7_TMARKS']) + trim($data['SUB8_TMARKS']) + trim($data['SUB9_TMARKS']);
                                    echo $GTOTAL;
                                ?>" readonly>
                            </td>
                            <td class="heading" style="font-weight:bold; font-size:18px;"><?php echo trim($data['GAZRES']); ?></td>
                            <td class="heading"></td>
                            <td class="heading"></td>
                            <td class="heading"></td>
                        </tr>
                    </table>
                    <br><br><br>
                    <input type="submit" name="update" value="Update Record">
                    </form>
                    <br><br>
                    <span style="font-family:Arial, Helvetica, sans-serif; font-size:17px; font-weight:bold;">
                        NOTE: This result is a notice only. Errors and omissions are excepted. For any query, send email to bise786@gmail.com.
                    </span>
                </div>
            </div>
        </div>
    </center>
</body>
</html>
