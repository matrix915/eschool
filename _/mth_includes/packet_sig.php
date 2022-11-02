<?php
include $_SERVER['DOCUMENT_ROOT'] . '/_/app/inc.php';

$file = mth_packet_file::getByID($_GET['file']);
if ($file->getKind() != mth_packet_file::KIND_SIG
    || $file->getHash() != $_GET['hash']
) {
    die();
}

if (class_exists('Imagick')) {
    $image = new Imagick();
    $image->readImageBlob($file->getContents());
    $image->setImageFormat("png24");
    $image->resizeImage(2000, 1000, imagick::FILTER_LANCZOS, 1, true);
    echo $image;
} else {
    echo $file->getContents();
}