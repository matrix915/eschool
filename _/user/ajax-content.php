<?php
if (req_get::bool('getgrade')) {
    $response = ['error'=>1,'data'=>'Error','id'=>req_get::int('enrollment')];
    if ($enrollment = mth_canvas_enrollment::getByEnrollmentID(req_get::int('enrollment'))) {
        $response = array_merge(
            $response,
            [
                'error' => 0,
                'data' => [
                    'grade' => $enrollment->grade(true),
                    'comments' => $enrollment->comments()
                ]
            ]
        );
    }
    echo json_encode($response);
    exit();
}