<?php
include $_SERVER['DOCUMENT_ROOT'] . '/_/app/inc.php';

if (isset($_GET['uploadFile'])) {
    if ($_FILES['fileUpload']['error'] == UPLOAD_ERR_OK && !empty($_POST)) {

        $trustedFileTypes = array("jpg", "jpeg", "gif", "png", "svg", "txt", "pdf", "odp", "ods", "odt", "rtf", "doc", "docx", "xls", "xlsx", "ppt", "pptx", "ogv", "mp4", "webm", "ogg", "mp3", "wav");
        $fileNameArr = explode('.', $_FILES['fileUpload']['name']);
        $fileType = trim(strtolower(end($fileNameArr)));

        if (in_array($fileType, $trustedFileTypes)) {

            $fileName = preg_replace(
                '/[^a-zA-Z0-9\-_\.]/',
                '_',
                ($_POST['fileName'] ? $_POST['fileName'] . '.' . $fileType : $_FILES['fileUpload']['name']));

            if (empty($_POST['overwrite'])) {
                if (($h = opendir(core_config::getUploadDir()))) {
                    while (($checkName = readdir($h)) !== false)
                        $checkNames[] = strtolower($checkName);
                    $c = 0;
                    do {
                        $c++;
                        $altName = str_replace('.' . $fileType, '-' . $c . '.' . $fileType, $fileName);
                    } while (in_array(strtolower($altName), $checkNames));
                    $fileName = $altName;
                }
            } else {
                if (is_file(core_config::getUploadDir() . '/' . $fileName)) {
                    unlink(core_config::getUploadDir() . '/' . $fileName);
                    core_notify::addMessage('File overwritten, the wrong thumb might be showing for file ' . $fileName);
                }
            }

            if (!move_uploaded_file($_FILES['fileUpload']['tmp_name'], core_config::getUploadDir() . '/' . $fileName))
                core_notify::addError('Unable to save file. Check upload folder permission.');
            else
                core_notify::addMessage('File uploaded and saved as: ' . $fileName);

        } else {
            core_notify::addError('Untrusted file type.');
        }
    } else {
        core_notify::addError('Unable to upload file. Check file size and upload limits on your server.');
    }

    header('location: ?CKEditorFuncNum=' . $_GET['CKEditorFuncNum']);
    exit();
}

if (isset($_GET['deleteFile'])) {
    if (strpos($_SERVER['DOCUMENT_ROOT'] . $_GET['deleteFile'], core_config::getUploadDir()) === 0
        && is_file($_SERVER['DOCUMENT_ROOT'] . $_GET['deleteFile'])
    ) {
        unlink($_SERVER['DOCUMENT_ROOT'] . $_GET['deleteFile']);
        core_notify::addMessage('File Deleted');
    }
    header('location: ?CKEditorFuncNum=' . $_GET['CKEditorFuncNum']);
    exit();
}

$imageTypes = array('jpg', 'jpeg', 'png', 'gif'); //can be processed by timthumb
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>File Manager</title>
    <style type="text/css">
        body {
            padding: 0;
            margin: 0;
            background: #ddd url('/_/includes/img/admin-bg.jpg') fixed;
            font-family: verdana, sans-serif;
            font-size: 14px;
        }

        #fileBrowser {
            position: absolute;
            top: 0;
            left: 0;
            right: 300px;
            bottom: 0;
            padding: 20px 0 0 20px;
            background: rgba(256, 256, 256, .3);
            border: 6px solid rgba(256, 256, 256, .5);
            overflow: auto;
        }

        #fileBrowser a {
            display: block;
            width: 136px;
            overflow: hidden;
            margin: 0 20px 20px 0;
            position: relative;
            float: left;
            color: #666;
            cursor: pointer;
            border: solid 1px #eee;
            border-color: rgba(256, 256, 256, .1);
        }

        #fileBrowser a:hover, #fileBrowser a.selected {
            color: #36b;
            background: rgba(256, 256, 256, .3);
        }

        #fileBrowser a.selected {
            border-color: #36b;
        }

        #fileBrowser a .thumb {
            display: block;
            margin: 4px 4px 0;
            width: 128px;
            height: 128px;
            background: transparent url(/_/includes/img/icon-file.png) center center no-repeat;
        }

        #fileBrowser a .fileName {
            display: block;
            overflow: hidden;
            height: 18px;
            line-height: 18px;
            font-size: 12px;
            text-align: center;
        }

        #fileBrowser a .fileType {
            position: absolute;
            font-weight: bold;
            opacity: .4;
            top: 60px;
            display: block;
            width: 100%;
            text-align: center;
            font-size: 24px;
        }

        #fileOptions {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            width: 300px;
            background: rgba(256, 256, 256, .6);
            overflow: auto;
        }

        #fileOptions > div {
            padding: 1px 20px;
        }

        #timThumbOptions, #fileUploader {
            padding: 1px 20px;
            margin: 20px -20px 0;
            background: rgba(256, 256, 256, .6);
        }

        #cover {
            position: fixed;
            top: 0;
            left: 0;
            background: #fff url(/_/includes/img/loading.gif) center center no-repeat;
            width: 100%;
            height: 100%;
        }

        .core-notify-erros {
            border: solid 2px #c00;
            background: #fcc;
            margin: 0 20px 20px 0;
        }

        .core-notify-messages {
            border: solid 2px #0c0;
            background: #cfc;
            margin: 0 20px 20px 0;
        }

        input[type="button"], input[type="submit"] {
            background: #eee;
            border: solid 1px #999;
            color: #666;
            padding: 3px 9px;
            border-radius: 5px;
            cursor: pointer;
        }

        input[type="button"]:hover, input[type="submit"]:hover {
            background: #fff;
            border-color: #666;
            color: #000;
        }

        #useFileButton {
            border: solid 3px #36b;
            background-color: #eee;
            font-weight: bold;
            color: #36b;
            padding: 6px 18px;
            border-radius: 8px;
        }

        #useFileButton:hover {
            color: #039;
            border-color: #039;
            background-color: #fff;
        }
    </style>
    <script type="text/javascript" src="/_/includes/jquery/jquery-1.11.1.min.js"></script>
    <script type="text/javascript">
        function selectFile(a, filepath, showTimeThumbOptions) {
            selectFile.filePath = filepath;
            $(a).addClass('selected').siblings('a').removeClass('selected');
            $('#fileOptionButtons').show();
            if (showTimeThumbOptions)
                $('#timThumbOptions').fadeIn();
            else
                $('#timThumbOptions').fadeOut();
        }
        function deleteFile() {
            if (confirm('Are you sure you want to delete this file! It cannot be undone.')) {
                window.location = '?deleteFile=' + encodeURI(selectFile.filePath)
                    + '&CKEditorFuncNum=<?=$_GET['CKEditorFuncNum']?>';
            }
        }
        function useFile(useTimeThumb) {
            var file = '';
            if (useTimeThumb) {
                file = '/_/includes/timthumb.php?src=' + selectFile.filePath
                    + '&w=' + $('#ttW').val()
                    + '&h=' + $('#ttH').val()
                    + '&a=' + $('#ttA').val();
            } else {
                file = selectFile.filePath;
            }
            window.opener.CKEDITOR.tools.callFunction(<?=$_GET['CKEditorFuncNum']?>, file);
            self.close()
        }
    </script>
</head>
<body>
<div id="fileBrowser">
    <?php core_notify::printNotices() ?>
    <?php
    if (!is_dir(core_config::getUploadDir())) {
        mkdir(core_config::getUploadDir());
    }
    if (is_dir(core_config::getUploadDir())) {
        if (($h = opendir(core_config::getUploadDir()))) {
            while (($file = readdir($h)) !== false) {
                if ($file[0] == '.')
                    continue;

                $filePath = core_config::getUploadDir(TRUE) . '/' . $file;

                if (is_dir($_SERVER['DOCUMENT_ROOT'] . $filePath))
                    continue;

                $fileNameArr = explode('.', $file);
                $fileType = trim(strtolower(end($fileNameArr)));

                if (in_array($fileType, $imageTypes)) {
                    $serveTimThumb = true;
                    $iconPath = '/_/includes/timthumb.php?src=' . $filePath . '&w=128&h=128&zc=3';
                    $fileType = '';
                } else {
                    $serveTimThumb = false;
                    $iconPath = '/_/includes/img/icon-file.png';
                    $fileType = '.' . $fileType;
                }
                ?>
                <a onclick="selectFile(this,'<?= $filePath ?>',<?= $serveTimThumb ? 'true' : 'false' ?>)">
                    <span class="thumb" style="background-image: url(<?= $iconPath ?>)">&nbsp;</span>
                    <span class="fileType"><?= $fileType ?></span>
                    <span class="fileName"><?= $file ?></span>
                </a>
                <?php
            }
        }
    } else {
        error_log('Upload directory (' . core_config::getUploadDir() . ') not found or unreadable'); ?>
        <p>Upload directory not found!</p>
        <?php
    }
    ?>
</div>
<div id="fileOptions">
    <div>
        <h3>File Options</h3>
        <div id="fileOptionButtons" style="display: none;">
            <input type="button" value="Use File" onclick="useFile();" id="useFileButton">
            <input type="button" value="Delete File" onclick="deleteFile();">
        </div>
        <div id="timThumbOptions" style="display: none;">
            <h4>TimThumb (<a href="https://code.google.com/p/timthumb/wiki/HowTo" target="_blank">?</a>)</h4>
            <p>width:<br><input type="text" id="ttW" value="100" size="4"></p>
            <p>height:<br><input type="text" id="ttH" value="" size="4"></p>
            <p>alignment:<br>
                <select id="ttA">
                    <option value="c">Center</option>
                    <option value="t">Top</option>
                    <option value="r">Right</option>
                    <option value="b">Bottom</option>
                    <option value="l">Left</option>
                    <option value="tl">Top-Left</option>
                    <option value="tr">Top-Right</option>
                    <option value="bl">Bottom-Left</option>
                    <option value="br">Bottom-Right</option>
                </select>
            </p>
            <p><input type="button" value="Use TimThumb Image" onclick="useFile(true);"></p>
        </div>
        <div id="fileUploader">
            <form action="?uploadFile=1&CKEditorFuncNum=<?= $_GET['CKEditorFuncNum'] ?>"
                  method="post" enctype="multipart/form-data" onsubmit="return $('#cover').show();">
                <h4>Upload File</h4>
                <p><input type="file" name="fileUpload"></p>
                <p>Save As:<br><input type="text" name="fileName"></p>
                <p><input type="checkbox" value="1" name="overwrite"> Overwrite Existing</p>
                <p><input type="submit" value="Upload"></p>
            </form>
        </div>
    </div>
</div>
<div id="cover" style="display: none;"></div>
</body>
</html>
