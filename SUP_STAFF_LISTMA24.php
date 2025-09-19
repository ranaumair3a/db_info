<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>Online Result For SSC (Part-I) 1st Annual 2024</title>
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
            //background-image:url("images/logo6.png");
            //background-repeat:no-repeat;
            background-position:center;
            pointer-events:none;
            z-index:99999;
            opacity:0.9;
            position:relative;
            -webkit-print-color-adjust:exact;
            height:400px;
        }
    </style>
    <script>
        function maxLengthCheck(object) {
            if (object.value.length > object.maxLength)
                object.value = object.value.slice(0, object.maxLength)
        }
    </script>
    <script language="javascript">
        document.onmousedown=disableclick;
        status="Right Click Disabled";
        function disableclick(event) {
            if(event.button==2) {
                alert(status);
                return false;    
            }
        }
    </script>
</head>
<body class="bg-gray-100 font-sans">
    <div class="container mx-auto p-4 max-w-4xl">
        <h1 class="text-xl font-bold text-center font-verdana">BOARD OF INTERMEDIATE AND SECONDARY EDUCATION DERA GHAZI KHAN</h1>
        <h2 class="text-2xl text-blue-900 text-center font-verdana mt-[-10px]">Online Result For SSC (Part-I) 1<sup>st</sup> Annual 2024</h2>
        <div class="mt-8 flex justify-center">
            <form method="post" name="slip" action="" onsubmit="return checkEmpty();" class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-lg font-bold text-center mb-4">Search By Roll No</h3>
                <div class="flex flex-col space-y-4">
                    <label class="font-bold text-base">Enter Your Roll No</label>
                    <input type="number" maxlength="6" name="rno" value="" placeholder="100001-200000 or 300000-360000" oninput="maxLengthCheck(this)" class="border border-gray-300 p-2 rounded" />
                    <input type="submit" value="Search for Result" class="bg-blue-500 text-white p-2 rounded cursor-pointer" />
                </div>
            </form>
        </div>
    </div>

<?php

// Instanciation of inherited class

include_once('config_seci.php');

function escape($string) {
return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

$rno=0;
if (isset($_POST['rno']) ){
$rno = (int)($_POST['rno']);
$DOB3 =$_POST['DOB'];
$DOB2 = date_create($DOB3);
$DOB = date_format($DOB2, 'Y-m-d');
}else{
//die('Invalid Input');
echo".";
exit();
}
$rno = escape($rno);
if (strlen($rno) < 6 OR strlen($rno) > 6) {
echo "<span class='input'>Roll No. consist of 6 digits only.</span>";
exit();
} 

if ($rno < 100000 OR $rno > 360000) {
echo "<span class='input'>Roll No. range is from 100000 to 200000   OR  300000 to 360000 only.</span>";
exit();
} 


$rno = filter_var($rno, FILTER_SANITIZE_NUMBER_INT);
if (filter_var($rno, FILTER_VALIDATE_INT)) {
} else {
echo "INVALID ROLL NO.";
exit;
}

$data = array();
if (!filter_var($rno, FILTER_VALIDATE_INT) === false ||!filter_var($rno, FILTER_VALIDATE_INT) === 0) {
$query =("select * from NANA2024000_RESULT where RNO=?; ");


$stmt=mysqli_stmt_init($con);
mysqli_stmt_prepare($stmt,$query);
mysqli_stmt_bind_param($stmt,"i", $rno);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);



//$result = mysqli_query($con,$query);
$num_rows= mysqli_num_rows($result);

if($num_rows<1){
    ?>
<center>
<span style="text-align: center;">
<?PHP
echo "Wrong Roll No";
exit;
}
?>
</span>
</center>
<?php
//$i = 0;
while ($row = mysqli_fetch_array($result)) {
$data = $row;
}

$DISTT=trim($data['DISTRICT_N']);
/*
if($DISTT=='LY'){
$DISTT='LAYYAH';
}elseif($DISTT=='MG'){
$DISTT='MUZAFFARGARH';
}elseif($DISTT=='RP'){
$DISTT='RAJAN PUR';
}else{
$DISTT='DERA GHAZI KHAN';

}
*/
$INST_CODE=trim($data['STATUS']);


$OBJECTION=trim($data['OBJECTION']);
$OBJECTION2=trim($data['OBJECTION2']);

if($OBJECTION==1 OR $OBJECTION2==1){
$GAZRES=($data['GAZRES']);
?>
<center>
<span style="font-weight:bold; font-size:18px;font-family: Arial, Helvetica, sans-serif; text-align: center;width: 400px;">


<?php 

ECHO "Sr No:"; echo trim($data['SRNO']); 
echo "<br>";echo "<br>";
ECHO "Roll No:"; echo trim($data['RNO']); 
echo "<br>";echo "<br>";
ECHO "Name:"; echo trim($data['NAME']); 

echo "<br>";echo "<br>";
if($data['GAZRES']=='ABSENT'){
echo  "Result:";
} else {
echo  "Result Block/Objection :   ";

}
//echo "    ";
//  echo $GAZRES;echo "<br>";echo "<br>";
echo trim($data['GAZRES']);
exit;

}


?>
</span>    
</center>

<center>
<div id="MAIN" class="container mx-auto max-w-5xl">
<div id="HEADER" class="w-full flex justify-center">
<div id="INN-HEADER" class="flex items-start space-x-4">

<div id="IMAGE" class="w-24 h-24"> 
<img width="100px" height="100px" src="images/logo.gif" class="w-full h-full">
</div>
<div id="CONTENT-HEADER" class="flex-1 pt-4">
<span class="font-verdana text-2xl font-bold block"> Board Of Intermediate & Secondary Education, Dera Ghazi Khan </span>
<div class="flex justify-between items-center">
    <div>
        <span class="text-lg font-bold">Roll No:</span>  <span class="font-bold text-xl ml-1"><?php echo trim($data['RNO']); ?></span>
        <span class="font-verdana text-lg ml-12">PROVISIONAL RESULT INTIMATION </span>
    </div>
    <span class="text-lg">Sr. No:</span><span class="font-bold text-lg"><?php echo trim($data['SRNO']); ?></span>
</div>
<span class="block text-right text-lg font-bold">Reg No:<?php echo trim($data['REGNO']); ?></span>

<span class="font-verdana text-base block"> SECONDARY SCHOOL CERTIFICATE (PART-I) 1<sup>st</sup> ANNUAL EXAMINATION 2024 </span>


<span class="w-72 text-base font-bold block pb-10">GROUP:<?PHP $SGROUP=trim($data['S_GROUP']);
if($SGROUP=='S'){
$SGROUP='SCIENCE';
}elseif($SGROUP=='G'){
$SGROUP='GENERAL';
}elseif($SGROUP=='D'){
$SGROUP='DEAF & DUMB';
}
echo $SGROUP;
?></span>
<!--<span style="float:right;margin-right:250px;font-size:18px;font-weight: bold;">Date Of Birth:-->
<?php
$date = $data['DOB'];
$date2 = date_create($date);
$date3 = date_format($date2, "d-m-Y");
//echo TRIM($data['DOB']);
//echo $date;

?>
<!--</span>-->


</div>

</div>

<br><br>
<div class="clear"></div>

<br><br>
<div id="OUTER-HEADER" class="w-full flex mt-12 mb-5">
<div id="L-HEAD" class="flex-1">
<table class="noBorder">
<tr>
<td class="noBorder"><span class="ml-6 text-lg">Candidate's Name:</span>
<span class="ml-6 font-verdana text-lg"><?php echo trim($data['NAME']); ?></span>
</td>

</tr>
<tr>
<td class="noBorder"><span class="ml-6 text-lg">Father's Name:</span>

<span class="ml-12 font-verdana text-lg"><?php echo trim($data['FATHER']); ?></span>
</td>
</tr>
<tr>
</table>
<!--    <table class="noBorder" >
<tr>-->
<div id="address" class="w-full">
<span class="ml-6 text-lg">Institute/District:</span>

<span class="ml-9 font-verdana text-sm">
<?php if($INST_CODE=='R'){
ECHO trim($data['INST_NAME']);
}ELSE{ echo $DISTT ;} ?>
</span>

</div>

<!--</tr>
</table>-->

</div> 
<!--<div id="R-HEAD" style="float:left;width:100">
<span style="margin-left:20px;float:left;border:2px solid black"> <img  width="110px" height="120px" src="../NA2019/<?php //echo trim($data['RNO']); ?>.JPG"></span> 
</div>-->

</div>
</div>  

<div style="clear:both"></div>
<?PHP


if($OBJECTION!=1){



?>
<div class="pk">

<div id='CONTENT'>


<!-- <div style="width:100px; height:130px; float:left; "> <img width="100px" height="130px" src="ia14p1/<?php //   echo $data['RNO'] ;  ?>.jpg"> </div>
<div style="clear:both"></div>-->
<table class="w-full border-collapse border border-gray-400 bg-blue-200">
<tr class="bg-blue-300">
<td  class="p-2 text-base font-bold uppercase">NAME OF SUBJECT </td>
<td  class="p-2 text-base font-bold text-center">TOTAL <BR> MARKS </td>
<td  class="p-2 text-base font-bold text-center">MARKS <BR> OBTAINED </td>
<td  class="p-2 text-base font-bold text-center">PERCENTILE <BR>SCORE </td>
<td  class="p-2 text-base font-bold text-center">RELATIVE <BR>GRADE </td>
<td  class="p-2 text-base font-bold text-center">REMARKS </td>

</tr>
<tr class="border border-gray-400">
<td  class="p-2 text-lg uppercase" >Urdu</td>
<td  class="p-2 text-center font-bold text-lg" id="sub1_total"><?PHP echo trim($data['SUB1_TMARKS']); ?></td>
<td  class="p-2 text-center font-bold text-lg" ><input type="number" class="w-20 border p-1 marks" data-sub="1" data-total="<?PHP echo trim($data['SUB1_TMARKS']); ?>" value="<?php echo trim($data['UR']); ?>"></td>
<td  class="p-2 text-center font-bold text-lg" id="sub1_per"><?PHP echo ($data['SUB1_PER']); ?></td>
<td  class="p-2 text-center font-bold text-lg" id="sub1_grade"><?PHP echo trim($data['SUB1_GRADE']); ?></td>
<td  class="p-2 text-center text-base" id="sub1_remarks"><?PHP  
if (trim($data['PASS1']) == '1'){
echo "PASS";
}elseif(trim($data['PASS1']) == '0'){
// echo "LESS THAN 33%";
}else{
echo "";
}
?></td>

</tr>
<tr class="border border-gray-400">
<td  class="p-2 text-lg uppercase">English</td>
<td  class="p-2 text-center font-bold text-lg" id="sub2_total"><?PHP echo trim($data['SUB2_TMARKS']); ?></td>
<td  class="p-2 text-center font-bold text-lg" ><input type="number" class="w-20 border p-1 marks" data-sub="2" data-total="<?PHP echo trim($data['SUB2_TMARKS']); ?>" value="<?php echo trim($data['ENG']); ?>"></td>
<td  class="p-2 text-center font-bold text-lg" id="sub2_per"><?PHP echo ($data['SUB2_PER']); ?></td>
<td  class="p-2 text-center font-bold text-lg" id="sub2_grade"><?PHP echo trim($data['SUB2_GRADE']); ?></td>
<td  class="p-2 text-center text-base" id="sub2_remarks"><?PHP  
if (trim($data['PASS2']) == '1'){
echo "PASS";
}elseif(trim($data['PASS2']) == '0'){
// echo "LESS THAN 33%";
}else{
echo "";
}
?></td>
</tr>
<tr class="border border-gray-400">
<td  class="p-2 text-lg uppercase" ><?php echo trim($data['APPEAR3']); ?></td>
<td  class="p-2 text-center font-bold text-lg" id="sub3_total"><?php echo trim($data['SUB3_TMARKS']); ?></td>
<td  class="p-2 text-center font-bold text-lg" ><input type="number" class="w-20 border p-1 marks" data-sub="3" data-total="<?php echo trim($data['SUB3_TMARKS']); ?>" value="<?php echo trim($data['ISL']); ?>"></td>
<td  class="p-2 text-center font-bold text-lg" id="sub3_per"><?PHP echo trim($data['SUB3_PER']); ?></td>
<td  class="p-2 text-center font-bold text-lg" id="sub3_grade"><?PHP echo trim($data['SUB3_GRADE']); ?></td>
<td  class="p-2 text-center text-base" id="sub3_remarks"><?PHP  
if (trim($data['PASS3']) == '1'){
echo "PASS";
}elseif(trim($data['PASS3']) == '0'){
// echo "LESS THAN 33%";
}else{
echo "";
}
?></td>
</tr>


<tr class="border border-gray-400">
<td  class="p-2 text-lg uppercase" ><?php echo trim($data['APPEAR9']); ?></td>
<td  class="p-2 text-center font-bold text-lg" id="sub9_total"><?php echo trim($data['SUB9_TMARKS']); ?></td>
<td  class="p-2 text-center font-bold text-lg" ><input type="number" class="w-20 border p-1 marks" data-sub="9" data-total="<?php echo trim($data['SUB9_TMARKS']); ?>" value="<?php echo trim($data['THQ']); ?>"></td>
<td  class="p-2 text-center font-bold text-lg" id="sub9_per"><?PHP echo trim($data['SUB9_PER']); ?></td>
<td  class="p-2 text-center font-bold text-lg" id="sub9_grade"><?PHP echo trim($data['SUB9_GRADE']); ?></td>
<td  class="p-2 text-center text-base" id="sub9_remarks"><?PHP  
if (trim($data['PASS9']) == '1'){
echo "PASS";
}elseif(trim($data['PASS9']) == '0'){
// echo "LESS THAN 33%";
}else{
echo "";
}
?></td>
</tr>



<tr class="border border-gray-400">
<td  class="p-2 text-lg uppercase">Pakistan Studies</td>
<td  class="p-2 text-center font-bold text-lg" id="sub4_total"><?PHP echo trim($data['SUB4_TMARKS'])?></td>
<td  class="p-2 text-center font-bold text-lg" ><input type="number" class="w-20 border p-1 marks" data-sub="4" data-total="<?PHP echo trim($data['SUB4_TMARKS']); ?>" value="<?php echo trim($data['PS']); ?>"></td>
<td  class="p-2 text-center font-bold text-lg" id="sub4_per"><?PHP echo trim($data['SUB4_PER']); ?></td>
<td  class="p-2 text-center font-bold text-lg" id="sub4_grade"><?PHP echo trim($data['SUB4_GRADE']); ?></td>
<td  class="p-2 text-center text-base" id="sub4_remarks"><?PHP  
if (trim($data['PASS4']) == '1'){
echo "PASS";
}elseif(trim($data['PASS4']) == '0'){
// echo "LESS THAN 33%";
}else{
echo "";
}
?></td>
</tr>
<tr class="border border-gray-400">
<td  class="p-2 text-lg uppercase"><?php echo trim($data['APPEAR5']); ?></td>
<td  class="p-2 text-center font-bold text-lg" id="sub5_total"><?php echo trim($data['SUB5_TMARKS'])?></td>
<td  class="p-2 text-center font-bold text-lg" ><input type="number" class="w-20 border p-1 marks" data-sub="5" data-total="<?php echo trim($data['SUB5_TMARKS']); ?>" value="<?php echo trim($data['MATHS']); ?>"></td>
<td  class="p-2 text-center font-bold text-lg" id="sub5_per"><?PHP echo trim($data['SUB5_PER']); ?></td>
<td  class="p-2 text-center font-bold text-lg" id="sub5_grade"><?PHP echo trim($data['SUB5_GRADE']); ?></td>
<td  class="p-2 text-center text-base" id="sub5_remarks"><?PHP  
if (trim($data['PASS5']) == '1'){
echo "PASS";
}elseif(trim($data['PASS5']) == '0'){
// echo "LESS THAN 33%";
}else{
echo "";
}
?></td>
</tr>
<tr class="border border-gray-400 bg-gray-100">
<td  class="p-2 text-lg uppercase"><?php echo trim($data['APPEAR6']); ?></td>
<td  class="p-2 text-center font-bold text-lg" id="sub6_total"><?php echo trim($data['SUB6_TMARKS']); ?></td>
<td  class="p-2 text-center font-bold text-lg" ><input type="number" class="w-20 border p-1 marks" data-sub="6" data-total="<?php echo trim($data['SUB6_TMARKS']); ?>" value="<?php echo trim($data['MARKS6']); ?>"></td>
<td  class="p-2 text-center font-bold text-lg" id="sub6_per"><?PHP echo trim($data['SUB6_PER']); ?></td>
<td  class="p-2 text-center font-bold text-lg" id="sub6_grade"><?PHP echo trim($data['SUB6_GRADE']); ?></td>
<td  class="p-2 text-center text-base" id="sub6_remarks"><?PHP  
if (trim($data['PASS6']) == '1'){
echo "PASS";
}elseif(trim($data['PASS6']) == '0'){
// echo "LESS THAN 33%";
}else{
echo "";
}
?></td>
</tr>
<tr class="border border-gray-400">
<td  class="p-2 text-lg uppercase"><?php echo trim($data['APPEAR7']); ?></td>
<td  class="p-2 text-center font-bold text-lg" id="sub7_total"><?php echo trim($data['SUB7_TMARKS']); ?></td>
<td  class="p-2 text-center font-bold text-lg" ><input type="number" class="w-20 border p-1 marks" data-sub="7" data-total="<?php echo trim($data['SUB7_TMARKS']); ?>" value="<?php echo trim($data['MARKS7']); ?>"></td>
<td  class="p-2 text-center font-bold text-lg" id="sub7_per"><?PHP echo trim($data['SUB7_PER']); ?></td>
<td  class="p-2 text-center font-bold text-lg" id="sub7_grade"><?PHP echo trim($data['SUB7_GRADE']); ?></td>
<td  class="p-2 text-center text-base" id="sub7_remarks"><?PHP  
if (trim($data['PASS7']) == '1'){
echo "PASS";
}elseif(trim($data['PASS7']) == '0'){
// echo "LESS THAN 33%";
}else{
echo "";
}
?></td>
</tr>
<tr class="border border-gray-400 bg-gray-100">
<td  class="p-2 text-lg uppercase"><?php echo trim($data['APPEAR8']); ?></td>
<td  class="p-2 text-center font-bold text-lg" id="sub8_total"><?php echo trim($data['SUB8_TMARKS']); ?></td>
<td  class="p-2 text-center font-bold text-lg" ><input type="number" class="w-20 border p-1 marks" data-sub="8" data-total="<?php echo trim($data['SUB8_TMARKS']); ?>" value="<?php echo trim($data['MARKS8']); ?>"></td>
<td  class="p-2 text-center font-bold text-lg" id="sub8_per"><?PHP echo trim($data['SUB8_PER']); ?></td>
<td  class="p-2 text-center font-bold text-lg" id="sub8_grade"><?PHP echo trim($data['SUB8_GRADE']); ?></td>
<td  class="p-2 text-center text-base" id="sub8_remarks"><?PHP  
if (trim($data['PASS8']) == '1'){
echo "PASS";
}elseif(trim($data['PASS8']) == '0'){
// echo "LESS THAN 33%";
}else{
echo "";
}
?></td>
</tr>
<tr class="bg-gray-300 border border-gray-400">
<td class="p-2 text-lg font-bold">Result </td>
<td class="p-2 text-center font-bold text-lg" id="grand_total"><?PHP 
$GTOTAL=trim($data['SUB1_TMARKS']) + 
trim($data['SUB2_TMARKS']) +
trim($data['SUB3_TMARKS']) +
trim($data['SUB4_TMARKS']) +
trim($data['SUB5_TMARKS']) +
trim($data['SUB6_TMARKS']) +
trim($data['SUB7_TMARKS']) +
trim($data['SUB8_TMARKS']) +
trim($data['SUB9_TMARKS']);
ECHO $GTOTAL;
?> </td>
<td class="p-2 font-bold text-lg text-center" id="total_obtained"><?php echo trim($data['GAZRES']); ?></td>
<td class="p-2"></td>
<td class="p-2"></td>
<td class="p-2"></td>

</tr>

</table>
<BR><BR><BR>
<?PHP 
if(trim($data['PASS'])==1){
?>
<!--<span style="font-family: Arial, Helvetica, sans-serif;font-size: 17px;font-weight:bold;">NOTE 1."The marks awarded are the best prediction of the performance & has been awarded under COVID-19 Examination Policy, hence considered as valid and fair ".</span> -->

<br><br>
<span style="font-family: Arial, Helvetica, sans-serif;font-size: 17px;font-weight:bold;">1 . This result is a notice only. Errors and omissions are excepted. This computer generated result has no legal status. For any query send email at bise786@gmail.com .</span> 

<?php	
}else{
?>
<BR><BR><BR>
<span   style="font-family: Arial, Helvetica, sans-serif;float:left;margin-left: 20px;font-size:17px;font-weight:bold"> NOTE:- </span>
<span style="font-family: Arial, Helvetica, sans-serif;font-size: 17px;font-weight:bold;">1 . This result is a notice only. Errors and omissions are excepted. This computer generated result has no legal status. For any query send email at bise786@gmail.com .</span> 

<!--<span   style="font-family: Arial, Helvetica, sans-serif;float:left;margin-left: 20px;font-size: 18px;font-weight:bold">1 .The candidate shall appear in the absent subject(s) along with  compulsory subjects  in annual examinations 2023.</span>-->
<br><br>
<!--<span style="font-family: Arial, Helvetica, sans-serif;font-size: 17px;font-weight:bold;">2."The marks awarded are the best prediction of the performance & has been awarded under COVID-19 Examination Policy, hence considered as valid and fair ".</span>-->

<?php	
}

?>


<br><br>

<!--<span  class="verdana" style="float:left;margin-left: 20px;font-size:17px;font-weight:bold"> NOTE:- </span>
<span  class="verdana" style="float:left;margin-left: 20px;font-size: 16px;"> (i) This provisional result intimation is issued as a notice only. Errors and omissions are expected.</span>-->
<br>
<!--<span  class="verdana" style="float:left;margin-left: 55px;font-size: 16px;"> and omissions are expected</span>
<br>
<span  class="verdana" style="float:left;margin-left: 100px;font-size: 16px;"> (ii) If the result intimation is lost, an interim result intimation can be </span>
<br>
<span  class="verdana" style="float:left;font-size: 17px;font-weight: bold;"> Result declared on: 19-08-2019.</span>

<span  class="verdana" style="float:left;margin-left: 125px;font-size: 16px;"> obtained by the candiate on payment of prescribed fee.</span>-->

<!--<br><br>
<span   style="float:left;font-family: Arial, Helvetica, sans-serif;font-size: 18px;font-weight: bold;margin-left: 265px;"> Web based report need no signature</span>-->

</div>
</div>
<?php  }else{  ?>
<div class="pk">

<div id='CONTENT'>


<!-- <div style="width:100px; height:130px; float:left; "> <img width="100px" height="130px" src="ia14p1/<?php //   echo $data['RNO'] ;  ?>.jpg"> </div>
<div style="clear:both"></div>-->
<table class="w-full border-collapse border border-gray-400 bg-blue-200">
<tr class="bg-blue-300">
<td  class="p-2 text-base font-bold uppercase">NAME OF SUBJECT </td>
<td  class="p-2 text-base font-bold text-center">TOTAL <BR> MARKS </td>
<td  class="p-2 text-base font-bold text-center">MARKS <BR> OBTAINED </td>
<td  class="p-2 text-base font-bold text-center">PERCENTILE <BR>SCORE </td>
<td  class="p-2 text-base font-bold text-center">RELATIVE <BR>GRADE </td>
<td  class="p-2 text-base font-bold text-center">REMARKS </td>

</tr>
<tr class="border border-gray-400">
<td  class="p-2 text-lg uppercase" >Urdu</td>
<td  class="p-2 text-center font-bold text-lg" ><?PHP echo trim($data['SUB1_TMARKS']); ?></td>
<td  class="p-2 text-center font-bold text-lg" ></td>
<td  class="p-2 text-center font-bold text-lg" ></td>
<td  class="p-2 text-center font-bold text-lg" ></td>
<td  class="p-2 text-center text-base" > <?PHP  

?></td>

</tr>
<tr class="border border-gray-400">
<td  class="p-2 text-lg uppercase">English</td>
<td  class="p-2 text-center font-bold text-lg" ><?PHP echo trim($data['SUB2_TMARKS']); ?></td>
<td  class="p-2 text-center font-bold text-lg" ></td>
<td  class="p-2 text-center font-bold text-lg" ></td>
<td  class="p-2 text-center font-bold text-lg" ></td>
<td  class="p-2 text-center text-base" ><?PHP  

?></td>
</tr>
<tr class="border border-gray-400">
<td  class="p-2 text-lg uppercase" ><?php echo trim($data['APPEAR3']); ?></td>
<td  class="p-2 text-center font-bold text-lg" ><?php echo trim($data['SUB3_TMARKS']); ?></td>
<td  class="p-2 text-center font-bold text-lg" ></td>
<td  class="p-2 text-center font-bold text-lg" ></td>
<td  class="p-2 text-center font-bold text-lg" ></td>
<td  class="p-2 text-center text-base" ><?PHP  

?></td>
</tr>
<tr class="border border-gray-400">
<td  class="p-2 text-lg uppercase">Pakistan Studies</td>
<td  class="p-2 text-center font-bold text-lg" ><?PHP echo trim($data['SUB4_TMARKS'])?></td>
<td  class="p-2 text-center font-bold text-lg" ></td>
<td  class="p-2 text-center font-bold text-lg" ></td>
<td  class="p-2 text-center font-bold text-lg" ></td>
<td  class="p-2 text-center text-base" ><?PHP  

?></td>
</tr>
<tr class="border border-gray-400">
<td  class="p-2 text-lg uppercase"><?php echo trim($data['APPEAR5']); ?></td>
<td  class="p-2 text-center font-bold text-lg" ><?php echo trim($data['SUB5_TMARKS'])?></td>
<td  class="p-2 text-center font-bold text-lg" ></td>
<td  class="p-2 text-center font-bold text-lg" ></td>
<td  class="p-2 text-center font-bold text-lg" ></td>
<td  class="p-2 text-center text-base" ><?PHP  

?></td>
</tr>
<tr class="border border-gray-400 bg-gray-100">
<td  class="p-2 text-lg uppercase"><?php echo trim($data['APPEAR6']); ?></td>
<td  class="p-2 text-center font-bold text-lg" ><?php echo trim($data['SUB6_TMARKS']); ?></td>
<td  class="p-2 text-center font-bold text-lg" ></td>
<td  class="p-2 text-center font-bold text-lg" ></td>
<td  class="p-2 text-center font-bold text-lg" ></td>
<td  class="p-2 text-center text-base" ><?PHP  

?></td>
</tr>
<tr class="border border-gray-400">
<td  class="p-2 text-lg uppercase"><?php echo trim($data['APPEAR7']); ?></td>
<td  class="p-2 text-center font-bold text-lg" ><?php echo trim($data['SUB7_TMARKS']); ?></td>
<td  class="p-2 text-center font-bold text-lg" ></td>
<td  class="p-2 text-center font-bold text-lg" ></td>
<td  class="p-2 text-center font-bold text-lg" ></td>
<td  class="p-2 text-center text-base" ><?PHP  

?></td>
</tr>
<tr class="border border-gray-400 bg-gray-100">
<td  class="p-2 text-lg uppercase"><?php echo trim($data['APPEAR8']); ?></td>
<td  class="p-2 text-center font-bold text-lg" ><?php echo trim($data['SUB8_TMARKS']); ?></td>
<td  class="p-2 text-center font-bold text-lg" ></td>
<td  class="p-2 text-center font-bold text-lg" ></td>
<td  class="p-2 text-center font-bold text-lg" ></td>
<td  class="p-2 text-center text-base" ><?PHP  

?></td>
</tr>
<tr class="bg-gray-300 border border-gray-400">
<td class="p-2 text-lg font-bold">G.Total</td>
<td class="p-2 text-center font-bold text-lg" ><?PHP 
$GTOTAL=trim($data['SUB1_TMARKS']) + 
trim($data['SUB2_TMARKS']) +
trim($data['SUB3_TMARKS']) +
trim($data['SUB4_TMARKS']) +
trim($data['SUB5_TMARKS']) +
trim($data['SUB6_TMARKS']) +
trim($data['SUB7_TMARKS']) +
trim($data['SUB8_TMARKS']) ;
ECHO $GTOTAL;
?> </td>
<td class="p-2 font-bold text-lg text-center" ><?php echo trim($data['GAZRES']); ?></td>
<td class="p-2"></td>
<td class="p-2"></td>
<td class="p-2"></td>

</tr>

</table>
<BR><BR><BR><BR>
<?PHP 
if(trim($data['PASS'])==1){
?>
<span style="font-family: Arial, Helvetica, sans-serif;font-size: 17px;font-weight:bold;">NOTE 1."The marks awarded are the best prediction of the performance & has been awarded under COVID-19 Examination Policy, hence considered as valid and fair ".</span> 

<br><br>
<span style="font-family: Arial, Helvetica, sans-serif;font-size: 17px;font-weight:bold;">2 . Errors and omissions are excepted.  For any query send email at bise786@gmail.com.</span> 

<?php	
}else{
?>
<BR><BR><BR><BR>
<span   style="font-family: Arial, Helvetica, sans-serif;float:left;margin-left: 20px;font-size:17px;font-weight:bold"> NOTE:- </span>
<span   style="font-family: Arial, Helvetica, sans-serif;float:left;margin-left: 20px;font-size: 18px;font-weight:bold">1 .The candidate shall appear in the absent subject(s) along with  compulsory subjects  in annual examinations 2022.</span>
<br><br>
<span style="font-family: Arial, Helvetica, sans-serif;font-size: 17px;font-weight:bold;">2."The marks awarded are the best prediction of the performance & has been awarded under COVID-19 Examination Policy, hence considered as valid and fair ".</span> 

<?php	
}
//ECHO EAN13(167,21,$data['RNO'],12);    // bar code 

?>


<br><br>
<!--
<span  class="verdana" style="float:left;margin-left: 20px;font-size:17px;font-weight:bold"> NOTE:- </span>
<span  class="verdana" style="float:left;margin-left: 20px;font-size: 16px;"> (i) This provisional result intimation is issued as a notice only. Errors and omissions are expected.</span>
<br>-->
<!--<span  class="verdana" style="float:left;margin-left: 55px;font-size: 16px;"> and omissions are expected</span>
<br>
<span  class="verdana" style="float:left;margin-left: 100px;font-size: 16px;"> (ii) If the result intimation is lost, an interim result intimation can be </span>
<br>
<span  class="verdana" style="float:left;font-size: 17px;font-weight: bold;"> Result declared on: 19-08-2019.</span>

<span  class="verdana" style="float:left;margin-left: 125px;font-size: 16px;"> obtained by the candiate on payment of prescribed fee.</span>-->

<!--<br><br>
<span   style="float:left;font-family: Arial, Helvetica, sans-serif;font-size: 18px;font-weight: bold;margin-left: 265px;"> Web based report need no signature</span>-->

</div>
</div>
<?php   }?>


<br><br>
<!--<span><img   src="W.D SAHAB_123.jpg">-->
<!--<br><br><br><br>
<img  width="150"  height="100"   src="images/CE_IMG1.png" style="float:left; margin-left:830px;margin-top: 30px;">
<br>
<span   style="float:left;font-family: Arial, Helvetica, sans-serif;font-size: 18px;font-weight: bold;margin-left: 690px;"> CONTROLLER OF EXAMINATIONS</span>-->
<br><br><br><br><br>
<!--
<form action="Na2021-2.php"  method="post" >
<input type="hidden" name="RNO"  value="<?php  echo trim($data['RNO']); ?>">
<input type="submit" value="Apply for rechecking">
</form>  -->
</center>

<!--header logo and boad name-->
</div>
<br><br><br>



<?php

}else{
echo "Invalid input";
}
?>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const marksInputs = document.querySelectorAll('.marks');
            const subs = [1,2,3,9,4,5,6,7,8]; // Order of subjects as in table

            function getGrade(per) {
                if (per >= 80) return 'A+';
                if (per >= 70) return 'A';
                if (per >= 60) return 'B';
                if (per >= 50) return 'C';
                if (per >= 40) return 'D';
                if (per >= 33) return 'E';
                return 'F';
            }

            function updateRow(sub) {
                const input = document.querySelector(`.marks[data-sub="${sub}"]`);
                const total = parseInt(input.dataset.total) || 0;
                let marks = parseInt(input.value) || 0;
                if (marks > total) marks = total; // Cap at max
                input.value = marks;

                const per = total > 0 ? ((marks / total) * 100).toFixed(2) : 0;
                document.getElementById(`sub${sub}_per`).textContent = per;

                const grade = getGrade(per);
                document.getElementById(`sub${sub}_grade`).textContent = grade;

                const remarks = per >= 33 ? 'PASS' : 'LESS THAN 33%';
                document.getElementById(`sub${sub}_remarks`).textContent = remarks;
            }

            function updateTotal() {
                let totalObtained = 0;
                subs.forEach(sub => {
                    const input = document.querySelector(`.marks[data-sub="${sub}"]`);
                    totalObtained += parseInt(input.value) || 0;
                });
                document.getElementById('total_obtained').textContent = totalObtained;
            }

            marksInputs.forEach(input => {
                input.addEventListener('input', () => {
                    const sub = input.dataset.sub;
                    updateRow(sub);
                    updateTotal();
                });
            });
        });
    </script>
</body>
</html>
