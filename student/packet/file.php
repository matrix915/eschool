<?php
/* @var $packet mth_packet */

$file = mth_packet_file::getByID($_GET['file']);
if (!$file || $file->getPacketID() != $packet->getID()) {
    die();
}

header('Content-type: ' . $file->getType());
header('Content-Disposition: attachment; filename="' . $file->getName() . '"');
echo $file->getContents();