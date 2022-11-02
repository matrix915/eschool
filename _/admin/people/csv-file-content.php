<?php

header('Content-type: text/csv');
header('Content-Disposition: attachment; filename="' . req_get::txt('file') . '.csv"');

echo file_get_contents(ROOT . core_path::getPath('csv') . '/' . req_get::txt('file'));