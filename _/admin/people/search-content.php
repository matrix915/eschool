<?php
$results = array();
while ($person = mth_person::search(req_get::txt('term'))) {
    $type = $person->getType();
    if($type == false){
        continue;
    }
    $thisItem = new stdClass();
    $thisItem->id = $person->getType() . ':' . $person->getID();
    $thisItem->label = req_sanitize::txt_decode($person->getName()) . ' (' . $type . ')';
    $results[] = $thisItem;
}

header('Content-type: application/json');
echo json_encode($results);
