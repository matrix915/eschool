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
 * @var string $school
 * @var string $birth_date
 * @var string $grade
 * @var string $language
 * @var string $language_home
 * @var string $language_home_child
 * @var string $language_friends
 * @var string $language_home_preferred
 * @var bool $work_move
 * @var string $date
 * @var string $signature_png_base64
 *
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>IHLA Residency Survey</title>
    <meta charset="UTF-8">
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
            width: 6.88in;
            left: .810in;
            top: .930in;
        }
        #student_last,
        #student_first,
        #student_middle{
            bottom: 6.994in;
        }
        #student_last{
            left:2.291in;
        }
        #student_first{
            left:3.783in;
        }
        #school,
        #birth_date,
        #grade{
            bottom: 6.691in;
        }
        #school{
            left:1.359in;
        }
        #birth_date{
            left:3.222in;
        }
        #grade,
        #student_middle{
            left:4.973in;
         }
        #language{
            bottom:5.306in;
        }
        #language_home{
            bottom:4.858in;
        }
        #language_home_child{
            bottom: 4.420in;
        }
        #language_friends{
            bottom:3.870in;
        }
        #language_home_preferred{
            bottom:3.252in;
        }
        .language-en{
            left:3.492in;
            font-size: 24px;
        }
        .language-es{
            left:4.614in;
            font-size: 24px;
        }
        .language-other{
            left: 5.411in;
            width: 1.931in;
            text-align: center;
        }
        #work_move{
            bottom: 2.617in;
            left: .97in;
        }
        #sig_date{
            bottom: 1.709in;
            left: 1.314in;
        }

        #sig{
            bottom: 1.603in;
            left: 2.740in;
            width: 2.368in;
            height: .5in;
        }
        #sig img{
            display: block;
            max-height: .5in;
        }

    </style>
</head>
<body>

<div id="form_overlay">
    <img src="<?= $_SERVER['DOCUMENT_ROOT'] ?>/_/views/packets/additional_forms/images/IHLA_Language_Servey.gif">
</div>
<div id="content">
    <div id="student_first"><?=$student_first?></div>
    <div id="student_middle"><?=$student_middle?></div>
    <div id="student_last"><?=$student_last?></div>
    <div id="school"><?=$school?></div>
    <div id="birth_date"><?=$birth_date?></div>
    <div id="grade"><?=$grade?></div>
    <?php
    $img = '<img src="'.$_SERVER['DOCUMENT_ROOT'].'/_/views/packets/additional_forms/images/check.png">';
    $languageBlock = function ($value,$id) use ($img){
        $l_value = strtolower($value);
        if($l_value=='english'){
            $class = 'language-en';
            $value = $img;
        }elseif($l_value=='spanish'){
            $class = 'language-es';
            $value = $img;
        }else{
            $class = 'language-other';
        }
        ?>
        <div id="<?=$id?>" class="<?=$class?>"><?=$value?></div>
        <?php
    };
    $languageBlock($language,'language');
    $languageBlock($language_home,'language_home');
    $languageBlock($language_home_child,'language_home_child');
    $languageBlock($language_friends,'language_friends');
    $languageBlock($language_home_preferred,'language_home_preferred');
    ?>
    <div id="work_move"><?=$work_move?$img:''?></div>
    <div id="sig">&nbsp;<img src="data:image/png;base64,<?=$signature_png_base64?>"></div>
    <div id="sig_date"><?=$date?></div>
</div>

</body>
</html>
