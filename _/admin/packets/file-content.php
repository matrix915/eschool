<?php

($file = mth_packet_file::getByID($_GET['file'])) || die();

header('Content-type: ' . $file->getType());
header('Content-Disposition: attachment; filename="' . $file->getName() . '"');
echo $file->getContents();