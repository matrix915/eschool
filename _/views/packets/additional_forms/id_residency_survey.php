<?php
/**
 * Created by PhpStorm.
 * User: abe
 * Date: 3/2/17
 * Time: 4:17 PM
 */

/**
 * @var string $student_first
 * @var string $student_middle
 * @var string $student_last
 * @var string $date
 * @var string $gender
 * @var string $birth_date
 * @var string $grade
 * @var string $signature_png_base64
 * @var int $living_location
 * @var int $lives_with
 *
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>IHLA Residency Survey</title>
    <style>
        body, html{
            font-family: 'Arial', sans-serif;
            font-size: 12pt;
            margin: 0;
            width: 8.5in;
            height: 11in;
            line-height: 1.4;
        }
        div{
            position: absolute;
            z-index: 1;
        }
        #form_overlay{
            top 0;
            left: 0;
            z-index: -1;
            width:11in;
        }
        #form_overlay img{
            position: absolute;
            width: 6.71in;
            left: .919in;
            top: .91in;
        }
        #student_first,
        #student_middle,
        #student_last,
        {
            top: 3.05in;
        }
        #todays_date,
        #gender_male,
        #gender_female,
        #birth_date{
            top: 3.64in;
        }
        #grade{
            top: 3.99in;
        }
        #sig_date{
            top: 9.42in;
        }
        #student_last{
            left: 2.51in;
        }
        #student_first{
            left: 4.74in;
        }
        #student_middle,
        #birth_date,
        #grade,
        #sig_date{
            left: 6.61in;
        }
        #todays_date{
            left: 2in;
        }
        #gender_male{
            left:4.49in;
        }
        #gender_female{
            left:4.98in;
        }
        .living_location, .lives_with{
            left: 1.03in;
            color: red;
            font-size: 16pt;
            line-height: 1.2;
            font-weight: bold;
        }
        #living_location-1{
            top: 5.48in;
        }
        #living_location-2{
            top: 5.74in;
        }
        #living_location-3{
            top: 6.04in;
        }
        #living_location-4{
            top:6.3in;
        }
        #living_location-5{
            top:6.59in;
        }
        #lives_with-1{
            top:7.58in;
        }
        #lives_with-2{
            top:7.85in;
        }
        #lives_with-3{
            top:8.14in;
        }
        #lives_with-4{
            top:8.41in;
        }
        #lives_with-5{
            top:8.69in
        }
        #lives_with-6{
            top:8.97in;
        }
        #sig{
            top: 8.9in;
            left: 2.99in;
            width: 2.69in;
            height: .6in;
        }
        #sig img{
            display: block;
            max-height: .6in;
        }

    </style>
</head>
<body>

<div id="form_overlay">
    <img src="<?= $_SERVER['DOCUMENT_ROOT'] ?>/_/views/packets/additional_forms/images/IHLA_Residency_Survey.gif">
</div>
<div id="content">
    <div id="student_first"><?=$student_first?></div>
    <div id="student_middle"><?=$student_middle?></div>
    <div id="student_last"><?=$student_last?></div>
    <div id="todays_date"><?=$date?></div>
    <div id="gender_male"><?=strtolower($gender)=='male'?'x':''?></div>
    <div id="gender_female"><?=strtolower($gender)=='female'?'x':''?></div>
    <div id="birth_date"><?=$birth_date?></div>
    <div id="grade"><?=$grade?></div>
    <?php
    foreach(\mth\packet\LivingLocationEnum::getLabels() as $value => $label){
        ?><div id="living_location-<?=$value?>"
            class="living_location"><?=$living_location==$value?'O':''?></div><?php
    }
    foreach(\mth\packet\LivesWithEnum::getLabels() as $value => $label){
        ?><div id="lives_with-<?=$value?>"
            class="lives_with"><?=$lives_with==$value?'O':''?></div><?php
    }
    ?>
    <div id="sig">&nbsp;<img src="data:image/png;base64,<?=$signature_png_base64?>"></div>
    <div id="sig_date"><?=$date?></div>
</div>

</body>
</html>
