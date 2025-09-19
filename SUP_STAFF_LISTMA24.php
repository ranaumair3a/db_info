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
            position: relative;
            height: auto;
        }
        .shadow {
            box-shadow: 0px 0px 5px #999;
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
</head>
<body>
    <center>
        <div style="width: 880px; height: auto; margin-left: -172px;">
            <h1 style="font-size: 18px; font-family: verdana; font-weight: bold; margin-left: 100px;">
                BOARD OF INTERMEDIATE AND SECONDARY EDUCATION DERA GHAZI KHAN
            </h1>
            <h2 style="color: #000066; margin-top: -17px; font-family: verdana; font-size: 22px;">
                Online Result For SSC (Part-I) 1<sup>st</sup> Annual 2024
            </h2>
            <div style="width: 600px; height: auto; vertical-align: middle; padding-left: 30px;">
                <form method="post" name="slip" action="" onsubmit="return checkEmpty();">
                    <table style="padding-top: 20px;">
                        <tr>
                            <td colspan="2"><h3 style="padding-left: 150px;">Search By Roll No</h3></td>
                        </tr>
                        <tr>
                            <td><span style="font-size: 16px; font-weight: bold;">Enter Your Roll No</span></td>
                            <td>
                                <input type="number" maxlength="6" name="rno" value="" placeholder="100001-200000 or 300000-360000" oninput="maxLengthCheck(this)" />
                            </td>
                        </tr>
                        <tr>
                            <td></td>
                            <td><input type="submit" value="Search for Result" /></td>
                        </tr>
                    </table>
                </form>
            </div>
        </div>
    </center>

    <script>
        function maxLengthCheck(object) {
            if (object.value.length > object.maxLength) {
                object.value = object.value.slice(0, object.maxLength);
            }
        }

        // Function to calculate and update total marks dynamically
        function updateTotal() {
            let total = 0;
            const marksInputs = [
                'ur_marks', 'eng_marks', 'isl_marks', 'thq_marks', 'ps_marks',
                'maths_marks', 'marks6', 'marks7', 'marks8'
            ];
            marksInputs.forEach(id => {
                const input = document.getElementById(id);
                if (input && input.value) {
                    total += parseInt(input.value) || 0;
                }
            });
            document.getElementById('gtotal').value = total;
        }
    </script>

    <?php
    include_once('config_seci.php');

    function escape($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    // Handle form submission for updates
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
        $rno = (int)$_POST['rno'];
        $name = escape($_POST['name']);
        $father = escape($_POST['father']);
        $ur_marks = (int)$_POST['ur_marks'];
        $eng_marks = (int)$_POST['eng_marks'];
        $isl_marks = (int)$_POST['isl_marks'];
        $thq_marks = (int)$_POST['thq_marks'];
        $ps_marks = (int)$_POST['ps_marks'];
        $maths_marks = (int)$_POST['maths_marks'];
        $marks6 = (int)$_POST['marks6'];
        $marks7 = (int)$_POST['marks7'];
        $marks8 = (int)$_POST['marks8'];
        $gtotal = (int)$_POST['gtotal'];

        // Update query
        $update_query = "UPDATE NANA2024000_RESULT SET 
            NAME = ?, FATHER = ?, UR = ?, ENG = ?, ISL = ?, THQ = ?, PS = ?, MATHS = ?, MARKS6 = ?, MARKS7 = ?, MARKS8 = ?, GTOTAL = ?
            WHERE RNO = ?";
        $stmt = mysqli_stmt_init($con);
        if (mysqli_stmt_prepare($stmt, $update_query)) {
            mysqli_stmt_bind_param($stmt, "ssssssssssssi", $name, $father, $ur_marks, $eng_marks, $isl_marks, $thq_marks, $ps_marks, $maths_marks, $marks6, $marks7, $marks8, $gtotal, $rno);
            if (mysqli_stmt_execute($stmt)) {
                echo "<span style='color: green; font-weight: bold;'>Record updated successfully!</span>";
            } else {
                echo "<span style='color: red; font-weight: bold;'>Error updating record: " . mysqli_error($con) . "</span>";
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Handle search by roll number
    $rno = 0;
    if (isset($_POST['rno'])) {
        $rno = (int)$_POST['rno'];
    } else {
        echo ".";
        exit();
    }

    $rno = escape($rno);
    if (strlen($rno) != 6) {
        echo "<span class='input'>Roll No. must be 6 digits.</span>";
        exit();
    }

    if ($rno < 100000 || $rno > 360000) {
        echo "<span class='input'>Roll No. range is from 100000 to 200000 or 300000 to 360000 only.</span>";
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
        echo "<center><span style='text-align: center;'>Wrong Roll No</span></center>";
        exit;
    }

    $data = mysqli_fetch_array($result);
    $DISTT = trim($data['DISTRICT_N']);
    $INST_CODE = trim($data['STATUS']);
    $OBJECTION = trim($data['OBJECTION']);
    $OBJECTION2 = trim($data['OBJECTION2']);

    if ($OBJECTION == 1 || $OBJECTION2 == 1) {
        echo "<center><span style='font-weight: bold; font-size: 18px; font-family: Arial, Helvetica, sans-serif; text-align: center; width: 400px;'>";
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
        <div id="MAIN" style="width: 1040px; margin: 0 auto;">
            <div id="HEADER" style="width: 960px; float: left; margin: 0 auto;">
                <div id="INN-HEADER">
                    <div id="IMAGE" style="width: 100px; height: 70px; float: left;">
                        <img width="100px" height="100px" src="images/logo.gif">
                    </div>
                    <div id="CONTENT-HEADER" style="width: 860px; height: 70px; float: left; padding-top: 15px;">
                        <span style="font-family: verdana; font-size: 23.5px; font-weight: bold;">
                            Board Of Intermediate & Secondary Education, Dera Ghazi Khan
                        </span><br>
                        <span style="font-family: verdana; font-size: 15px; float: left;">
                            SECONDARY SCHOOL CERTIFICATE (PART-I) 1<sup>st</sup> ANNUAL EXAMINATION 2024
                        </span>
                    </div>
                </div>
                <div class="clear"></div>
                <div id="OUTER-HEADER" style="width: 960px; float: left; margin-top: 50px; margin-bottom: 20px;">
                    <div id="L-HEAD" style="float: left; width: 840px;">
                        <form method="post" action="">
                            <input type="hidden" name="rno" value="<?php echo trim($data['RNO']); ?>">
                            <table class="noBorder">
                                <tr>
                                    <td class="noBorder">
                                        <span style="float: left; margin-left: 25px; font-size: 18px;">Candidate's Name:</span>
                                        <input type="text" name="name" value="<?php echo trim($data['NAME']); ?>" style="font-family: verdana; font-size: 18px;">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="noBorder">
                                        <span style="float: left; margin-left: 25px; font-size: 18px;">Father's Name:</span>
                                        <input type="text" name="father" value="<?php echo trim($data['FATHER']); ?>" style="font-family: verdana; font-size: 18px;">
                                    </td>
                                </tr>
                            </table>
                            <div id="address" style="width: 840px;">
                                <span style="float: left; margin-left: 25px; font-size: 18px;">Institute/District:</span>
                                <span style="float: left; margin-left: 37px; font-family: verdana; font-size: 14px;">
                                    <?php echo ($INST_CODE == 'R') ? trim($data['INST_NAME']) : $DISTT; ?>
                                </span>
                            </div>
                            <br><br>
                            <div class="pk">
                                <div id="CONTENT">
                                    <table width="100%" cellpadding="10" cellspacing="0" border="0">
                                        <tr bgcolor="#99CCCC">
                                            <td class="heading" style="font-size: 17px; font-weight: bold; font-family: Arial, Helvetica, sans-serif;">NAME OF SUBJECT</td>
                                            <td class="heading" style="font-size: 17px; font-weight: bold; font-family: Arial, Helvetica, sans-serif;">TOTAL MARKS</td>
                                            <td class="heading" style="font-size: 17px; font-weight: bold; font-family: Arial, Helvetica, sans-serif;">MARKS OBTAINED</td>
                                            <td class="heading" style="font-size: 17px; font-weight: bold; font-family: Arial, Helvetica, sans-serif;">PERCENTILE SCORE</td>
                                            <td class="heading" style="font-size: 17px; font-weight: bold; font-family: Arial, Helvetica, sans-serif;">RELATIVE GRADE</td>
                                            <td class="heading" style="font-size: 17px; font-weight: bold; font-family: Arial, Helvetica, sans-serif;">REMARKS</td>
                                        </tr>
                                        <tr class="sp">
                                            <td class="shadow" style="font-size: 18px; text-transform: uppercase; font-family: Arial, Helvetica, sans-serif;">Urdu</td>
                                            <td class="shadow" style="text-align: center; font-weight: bold; font-size: 19px;"><?php echo trim($data['SUB1_TMARKS']); ?></td>
                                            <td class="shadow" style="text-align: center; font-weight: bold; font-size: 18px;">
                                                <input type="number" id="ur_marks" name="ur_marks" value="<?php echo trim($data['UR']); ?>" oninput="updateTotal()">
                                            </td>
                                            <td class="shadow" style="text-align: center; font-weight: bold; font-size: 19px;"><?php echo trim($data['SUB1_PER']); ?></td>
                                            <td class="shadow" style="text-align: center; font-weight: bold; font-size: 19px;"><?php echo trim($data['SUB1_GRADE']); ?></td>
                                            <td class="shadow" style="text-align: center; font-size: 16px; font-family: Arial, Helvetica, sans-serif;">
                                                <?php echo (trim($data['PASS1']) == '1') ? "PASS" : (trim($data['PASS1']) == '0' ? "" : ""); ?>
                                            </td>
                                        </tr>
                                        <!-- Repeat similar structure for other subjects -->
                                        <tr class="sp">
                                            <td class="shadow" style="font-size: 18px; text-transform: uppercase; font-family: Arial, Helvetica, sans-serif;">English</td>
                                            <td class="shadow" style="text-align: center; font-weight: bold; font-size: 19px;"><?php echo trim($data['SUB2_TMARKS']); ?></td>
                                            <td class="shadow" style="text-align: center; font-weight: bold; font-size: 18px;">
                                                <input type="number" id="eng_marks" name="eng_marks" value="<?php echo trim($data['ENG']); ?>" oninput="updateTotal()">
                                            </td>
                                            <td class="shadow" style="text-align: center; font-weight: bold; font-size: 19px;"><?php echo trim($data['SUB2_PER']); ?></td>
                                            <td class="shadow" style="text-align: center; font-weight: bold; font-size: 19px;"><?php echo trim($data['SUB2_GRADE']); ?></td>
                                            <td class="shadow" style="text-align: center; font-size: 16px; font-family: Arial, Helvetica, sans-serif;">
                                                <?php echo (trim($data['PASS2']) == '1') ? "PASS" : (trim($data['PASS2']) == '0' ? "" : ""); ?>
                                            </td>
                                        </tr>
                                        <!-- Add similar rows for other subjects (ISL, THQ, PS, MATHS, MARKS6, MARKS7, MARKS8) -->
                                        <tr class="grey">
                                            <td class="heading" style="font-size: 18px; font-weight: bold;">G.Total</td>
                                            <td class="heading" style="text-align: center; font-weight: bold; font-size: 19px;">
                                                <?php
                                                $GTOTAL = trim($data['SUB1_TMARKS']) + trim($data['SUB2_TMARKS']) + trim($data['SUB3_TMARKS']) +
                                                          trim($data['SUB4_TMARKS']) + trim($data['SUB5_TMARKS']) + trim($data['SUB6_TMARKS']) +
                                                          trim($data['SUB7_TMARKS']) + trim($data['SUB8_TMARKS']) + trim($data['SUB9_TMARKS']);
                                                echo $GTOTAL;
                                                ?>
                                            </td>
                                            <td class="heading" style="text-align: center; font-weight: bold; font-size: 18px;">
                                                <input type="number" id="gtotal" name="gtotal" value="<?php echo $GTOTAL; ?>" readonly>
                                            </td>
                                            <td class="heading"></td>
                                            <td class="heading"></td>
                                            <td class="heading"></td>
                                        </tr>
                                    </table>
                                    <br><br>
                                    <input type="submit" name="update" value="Save Changes" style="font-size: 16px; padding: 10px;">
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </center>
</body>
</html>
