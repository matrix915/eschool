<?php

function get_launchpad_course()
{
    $rs256_token = jwt_token::generateTokenForSparkLMS();
    $course_list_url = "https://tech.sparkeducation.com/api/courses/list";

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $course_list_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer $rs256_token",
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);

    $res = json_decode($response, true);

    $result = [];
    if ($res['status'] == 'success') {
        $sparkMap = [];
        foreach ($res['data'] as $key => $value) {
            $spark_id = $value['id'];
            $sparkMap[$value['id']] = $value['name'];
        }


        $mth_courses = mth_provider_course::getSprakCourses();

        $mth_subject_courses = mth_course::getSprakCourses();

        foreach ($mth_courses as $key2 => $mth_course) {
            $provider_launchpad = "";
            if (isset($sparkMap[$mth_course->spark_course_id])) {
                $provider_launchpad = $sparkMap[$mth_course->spark_course_id];
            }
            $semester1 = mth_sparkCourse::getSparkCourseCount($mth_course->provider_course_id, 'provider', 0);
            $semester2 = mth_sparkCourse::getSparkCourseCount($mth_course->provider_course_id, 'provider', 1);
            array_push($result, [
                'sparkID' => $mth_course->spark_course_id,
                'provider' => $mth_course->provider_name,
                'course' => $mth_course->title,
                'launchpadCourse' => $provider_launchpad,
                'semester1' => count($semester1),
                'semester2' => count($semester2),
            ]);
        }

        foreach ($mth_subject_courses as $key3 => $mth_s_course) {
            $subject_launchpad = "";
            if (isset($sparkMap[$mth_s_course->spark_course_id])) {
                $subject_launchpad = $sparkMap[$mth_s_course->spark_course_id];
            }
            $semester1 = mth_sparkCourse::getSparkCourseCount($mth_s_course->course_id, 'subject', 0);
            $semester2 = mth_sparkCourse::getSparkCourseCount($mth_s_course->course_id, 'subject', 1);
            array_push($result, [
                'sparkID' => $mth_s_course->spark_course_id,
                'provider' => "",
                'course' => $mth_s_course->title,
                'launchpadCourse' => $subject_launchpad,
                'semester1' => count($semester1),
                'semester2' => count($semester2),
            ]);
        }
    }

    return ['count' => count($result), 'courses' => $result, 'status' => $res['status']];
}

function get_provider_course($sem_type)
{
    $current_school_year = mth_schoolYear::getCurrent();
    $current_year_id = $current_school_year->getID();
    $semester_courses = mth_schedule_period::getSparkProviderCourses($current_year_id, $sem_type);
    return $semester_courses;
}

function email_test($email)
{
    // email testing..
    $emails = new core_emailservice();
    return $emails->send(
        array($email),
        'Testing',
        'content testing'
    );
}

function spark_get_api($method, $url)
{
    $rs256_token = jwt_token::generateTokenForSparkLMS();

    $curl = curl_init();


    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $rs256_token,
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    $res = json_decode($response, true);

    return $res;
}

function create_email_send($user, $password)
{
    $setting = core_setting::get("launchpadAccount", "User");
    $emailContent = $setting->getValue();
    $emailContent = str_replace("[FIRST_NAME]", $user['first_name'], $emailContent);
    $emailContent = str_replace("[EMAIL]", $user['email'], $emailContent);
    $emailContent = str_replace("[PASSWORD]", $password, $emailContent);

    $parent = mth_parent::getByParentID($user['parent_id']);
    $parent_email = $parent->getEmail();

    $subjectObj = core_setting::get("launchpadAccountSubject", "User");
    $subject = $subjectObj->getValue();

    $email = new core_emailservice();
    $email_res =  $email->send(
        array($parent_email),
        $subject,
        $emailContent
    );
}

function spark_api($method, $url, $field)
{
    $rs256_token = jwt_token::generateTokenForSparkLMS();

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_POSTFIELDS => $field,
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer $rs256_token"
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    $res = json_decode($response, true);
    mth_sparkLog::save($url, json_encode($field), json_encode($res), $res['status']);
    return $res;
}

function get_spark_users($role)
{
    $create_user_url = "https://tech.sparkeducation.com/api/users/list?role=$role";
    $password = jwt_token::generateSparkUserPassword();
    $spark_res = spark_get_api("GET", $create_user_url);
    return $spark_res;
}

function create_spark_user($user)
{
    $create_user_url = "https://tech.sparkeducation.com/api/users/create";
    $password = jwt_token::generateSparkUserPassword();
    $create_spark_user_api = spark_api("POST", $create_user_url, array('user[username]' => $user['email'], 'user[email]' => $user['email'], 'user[firstName]' => $user['first_name'], 'user[lastName]' => $user['last_name'], 'user[birthday]' =>  $user['birthday'], 'user[role]' => 'Student', 'user[status]' => 'active', 'user[password]' => $password, 'user[hardPromptPassReset]' => 1));


    if ($create_spark_user_api['status'] == 'success') {
        create_email_send($user, $password);
    };


    return $create_spark_user_api;
}

function update_spark_user($user, $spark_user_id)
{

    $update_user_url = "https://tech.sparkeducation.com/api/users/update";
    $update_spark_user_api = spark_api("POST", $update_user_url, array('id' => $spark_user_id,  'user[username]' =>  $user['email'], 'user[email]' => $user['email'], 'user[firstName]' => $user['first_name'], 'user[lastName]' => $user['last_name'], 'user[birthday]' =>  $user['birthday'], 'user[role]' => 'Student', 'user[status]' => 'active'));
    return $update_spark_user_api;
}

function delete_spark_user($spark_user_id)
{

    $delete_user_url = "https://tech.sparkeducation.com/api/users/delete";
    $spark_response = spark_api("POST", $delete_user_url, array('id' => $spark_user_id));
    return $spark_response;
}

function create_course_enroll($spark_user_id, $spark_course_id)
{

    $create_course_enroll = "https://tech.sparkeducation.com/api/users/enrollment/create";
    $spark_response = spark_api("POST", $create_course_enroll, "userId=$spark_user_id&courseIds=$spark_course_id");
    return $spark_response;
}

function remove_course_enroll($spark_user_id, $spark_course_id)
{

    $spark_api = "https://tech.sparkeducation.com/api/users/enrollment/delete";
    $spark_response = spark_api("POST", $spark_api, "userId=$spark_user_id&courseIds=$spark_course_id");
    return $spark_response;
}

function complete_course($spark_course_id, $spark_user_ids)
{
    $spark_api = "https://tech.sparkeducation.com/api/courses/complete";
    // $spark_api = "https://tech.sparkeducation.com/api/courses/unenroll";
    $spark_response = spark_api("POST", $spark_api, "courseId=$spark_course_id&userIds=$spark_user_ids");
    return $spark_response;
}

function unenroll_course($spark_course_id, $spark_user_ids)
{
    $spark_api = "https://tech.sparkeducation.com/api/courses/unenroll";
    $spark_response = spark_api("POST", $spark_api, "courseId=$spark_course_id&userIds=$spark_user_ids");
    return $spark_response;
}

function get_spark_course_string($user_courses, $sparkMap)
{
    $course_array = [];
    foreach ($user_courses as $key => $value) {
        if (array_key_exists($value['spark_course_id'], $sparkMap)) {  // only add valid spark course id
            array_push($course_array, $value['spark_course_id']);
        }
    }


    $unied_array = array_unique($course_array);

    return implode(",", $unied_array);
}

function save_spark_course_temporary($mth_data, $spark_data, $spark_user_id, $semester)
{
    $spark_data_array = [];
    foreach ($spark_data as $key => $spark_course) {
        $spark_data_array[$spark_course['courseId']] = [
            "courseId" => $spark_course['courseId'],
            "name" => $spark_course['name'],
            "enrolledOn" => $spark_course['enrolledOn']
        ];
    }

    $current_school_year = mth_schoolYear::getCurrent();
    $current_year_id = $current_school_year->getID();

    $insert_query = "";
    foreach ($mth_data as $key => $mth_course) {
        if (array_key_exists($mth_course['spark_course_id'], $spark_data_array)) {
            $insert_query .= "(";

            $spark_course = $spark_data_array[$mth_course['spark_course_id']];
            $spark_course_id = $spark_course['courseId'];
            $enrolled_on = $spark_course['enrolledOn'];
            $spark_course_name = $spark_course['name'];

            $course_type = $mth_course['course_type'];
            $mth_course_id = $mth_course['mth_course_id'];
            $period = $mth_course['period'];

            $insert_query = $insert_query . "'$spark_course_id', '$spark_user_id', '$spark_course_name', '$enrolled_on', '$semester', '$current_year_id', '$course_type', '$mth_course_id', '$period'";
            $insert_query .= "),";
        }
    }
    
    if ($insert_query) {
        $insert_query = rtrim($insert_query, ",");
        mth_sparkCourse::bulkSave($insert_query);
    }
}

function remove_spark_course_temporary($remove_course_list, $spark_user_id, $semester)
{
    $current_school_year = mth_schoolYear::getCurrent();
    $current_year_id = $current_school_year->getID();

    $remove_query = "spark_user_id = $spark_user_id AND semester=$semester AND spark_course_id IN ($remove_course_list) AND school_year_id = $current_year_id";

    $res = mth_sparkCourse::bulkDelete($remove_query);
    return $res;
}

function mid_remove_spark_course_temporary($remove_course_list, $spark_user_id, $semester) // remove sem0 course in sem1 sync
{
    $current_school_year = mth_schoolYear::getCurrent();
    $current_year_id = $current_school_year->getID();

    $remove_query = "spark_user_id = $spark_user_id AND semester=$semester AND spark_course_id IN ($remove_course_list) AND school_year_id = $current_year_id";

    $res = mth_sparkCourse::bulkMidDelete($remove_query);
    return $res;
}
// add and remove update courses for given spark user
function check_spark_course($spark_user_id, $course_list, $semester, $sparkMap)
{
    $temporary_spark_courses = mth_sparkCourse::getCourseByUserID($spark_user_id, $semester);

    $new_spark_course_by_period = [];

    $new_course_map = [];
    foreach ($course_list as $key => $new_course) {
        $new_spark_id = $new_course['spark_course_id'];
        $new_period = $new_course['period'];
        $mth_course_id = $new_course['mth_course_id'];
        $new_course_map["$new_spark_id-$new_period-$mth_course_id"] = $new_course;
        $new_spark_course_by_period["$new_period"] = $new_spark_id;
    }



    $old_course_map = [];
    foreach ($temporary_spark_courses as $key => $old_course) {
        $old_course_map["$old_course->spark_course_id-$old_course->period-$old_course->mth_course_id"] = $old_course;
    }

    $remove_course_list = [];
    $add_course_list = [];


    foreach ($old_course_map as $key => $old_value) {
        if (!array_key_exists($key, $new_course_map)) {
            $target_period = $old_value->period;
            $target_new_spark_id = $new_spark_course_by_period[$target_period];
            if (!$target_new_spark_id || array_key_exists($target_new_spark_id, $sparkMap)) {
                if (array_key_exists($old_value->spark_course_id, $sparkMap)) {
                    array_push($remove_course_list, $old_value->spark_course_id);
                }
            }
        }
    }

    foreach ($new_course_map as $key => $new_value) {
        if (!array_key_exists($key, $old_course_map)) {
            array_push($add_course_list, $new_value);
        }
    }



    // remove deleted spark course
    if (count($remove_course_list) > 0) {

        $remove_course_list = implode(",", $remove_course_list);

        $enroll_course = remove_course_enroll($spark_user_id, $remove_course_list);

        if ($enroll_course['status'] == 'success') {
            remove_spark_course_temporary($remove_course_list, $spark_user_id, $semester);
        }
    }


    // add new spark course
    if (count($add_course_list) > 0) {

        $spark_enroll_ids = get_spark_course_string($add_course_list, $sparkMap);
        if ($spark_enroll_ids) {
            $enroll_course = create_course_enroll($spark_user_id, $spark_enroll_ids);
            if ($enroll_course['status'] == 'success') {

                save_spark_course_temporary($add_course_list, $enroll_course['data'], $spark_user_id, $semester);
            }
        }
    }
}

function remove_spark_course_by_user($spark_user_id, $semester, $sparkMap)
{

    $temporary_spark_courses = mth_sparkCourse::getCourseByUserID($spark_user_id, $semester);

    $remove_course_list = [];
    foreach ($temporary_spark_courses as $key => $temporary_course) {
        if (array_key_exists($temporary_course->spark_course_id, $sparkMap)) {
            array_push($remove_course_list, $temporary_course->spark_course_id);
        }
    }

    $remove_course_list = array_unique($remove_course_list);

    if (count($remove_course_list) > 0) {
        $remove_course_list = implode(",", $remove_course_list);
        $enroll_course = remove_course_enroll($spark_user_id, $remove_course_list);

        if ($enroll_course['status'] == 'success') {
            remove_spark_course_temporary($remove_course_list, $spark_user_id, $semester);
        }
    }

    // delete user
    if ($semester == 0) {
        $del_res = delete_spark_user($spark_user_id);
        if ($del_res['status'] ==  "success") {
            mth_sparkUser::deleteByIds($spark_user_id);
        }
    } else {
        // if semester1 course isn't exist, we have to check semester0 for deleting current user
        $first_spark_courses = mth_sparkCourse::getCourseByUserID($spark_user_id, 0);
        if (count($first_spark_courses) == 0) {
            $del_res = delete_spark_user($spark_user_id);
            if ($del_res['status'] ==  "success") {
                mth_sparkUser::deleteByIds($spark_user_id);
            }
        }
    }
}

function store_user_to_spark($enroll_users, $user_spark_course, $semester, $sparkMap)
{
    $exist_user_list = []; // existing user hashmap : person_id => spark_id
    $exist_spark_users = mth_sparkUser::find_all();  // get un deleted spark temporary users
    foreach ($exist_spark_users as $key => $exist_user) {
        if ($exist_user->person_id && !array_key_exists($exist_user->person_id, $exist_user_list)) {
            $exist_user_list[$exist_user->person_id] = [
                'person_id' => $exist_user->person_id,
                'email' => $exist_user->mth_email,
                'spark_user_id' => $exist_user->spark_user_id
            ];
        }
    }

    foreach ($enroll_users as $key => $user) {
        if (!array_key_exists($user['person_id'], $exist_user_list)) { // new user
            if (array_key_exists($user['person_id'], $user_spark_course)) { // create enroll course in launchpad
                $spark_enroll_ids = get_spark_course_string($user_spark_course[$user['person_id']], $sparkMap);
                if ($spark_enroll_ids) {

                    $spark_res = create_spark_user($user); // enroll user in spark service
                    if ($spark_res['status'] == 'success') {
                        $spark_user_id = $spark_res['data']['id'];
                        // } else {
                        //     $spark_user = mth_sparkUser::getByEmail($user['email']);
                        //     $spark_user_id = $spark_user ? $spark_user->spark_user_id : '';
                        if ($spark_user_id) {
                            $store_new_user = mth_sparkUser::save($spark_user_id, $user['person_id'], $user['first_name'], $user['last_name'], $user['email']); // save enrolled user in temporary table


                            $enroll_course = create_course_enroll($spark_user_id, $spark_enroll_ids); // create enrolled course in spark service
                            if ($enroll_course['status'] == 'success') {
                                save_spark_course_temporary($user_spark_course[$user['person_id']], $enroll_course['data'], $spark_user_id, $semester); // save enrolled spark coruse in temporary table
                            } else {
                                var_dump("create spark user :$spark_user_id, but failed to enroll the course: $spark_enroll_ids");
                                var_dump($enroll_course);
                            }
                        }
                    }
                }
            }
        } else {
            $existing_spark_user = $exist_user_list[$user['person_id']];
            if ($existing_spark_user['email'] != $user['email']) {  // check if email has changed
                $update_spark_res = update_spark_user($user, $existing_spark_user['spark_user_id']);  // update changed email in spark service
                if ($update_spark_res['status'] == 'success') {
                    $store_new_user = mth_sparkUser::update($existing_spark_user['spark_user_id'], $user['person_id'], $user['first_name'], $user['last_name'], $user['email']); // update in temporary table
                }
            }
        }
    }


    // check spark course for temporary users
    foreach ($exist_user_list as $key => $temporary_user) {
        // check if student's schedule opens or not
        $student = mth_student::getByPersonID($temporary_user['person_id']);
        if (!$student) {
            remove_spark_course_by_user($temporary_user['spark_user_id'], $semester, $sparkMap); // if the student was deleted
        } else {
            $schedule = mth_schedule::getScheduleByStudentId($student->getID());
            if ($schedule && ($schedule->status == mth_schedule::STATUS_ACCEPTED || $schedule->status == mth_schedule::STATUS_DELETED)) {
                if (!array_key_exists($temporary_user['person_id'],  $user_spark_course)) { // exist in temporary table, but doesn't exist in latest query
                    remove_spark_course_by_user($temporary_user['spark_user_id'], $semester, $sparkMap); // removed this spark users, we have to remove all spark courses for these users
                } else {
                    $latest_course_list = $user_spark_course[$temporary_user['person_id']];
                    check_spark_course($temporary_user['spark_user_id'], $latest_course_list, $semester, $sparkMap);
                }
            }
        }
    }
}


function remove_incorrect_enrolled_course($sparkMap)
{
    $current_school_year = mth_schoolYear::getCurrent();
    $current_year_id = $current_school_year->getID();

    $enrolled_spark_course = mth_sparkCourse::get_enrolled_courses();

    $wrong_spark_ids = [];

    foreach ($enrolled_spark_course as $key => $value) {
        if (!array_key_exists($value->spark_course_id, $sparkMap)) {
            array_push($wrong_spark_ids, $value->spark_course_id);
        }
    }

    $remove_course_list = implode(", ", $wrong_spark_ids);
    $remove_query = "spark_course_id IN ($remove_course_list) AND school_year_id = $current_year_id";

    mth_sparkCourse::bulkDelete($remove_query);
}

// enroll student and course first semester
// enroll student who has spark course in semester1
function register_user($user_course_list, $semester, $sparkMap)
{


    // get wrong spark ids
    $wrong_spark_ids = [];

    $mth_courses = mth_provider_course::getSprakCourses();

    $mth_subject_courses = mth_course::getSprakCourses();

    foreach ($mth_courses as $key2 => $mth_course) {
        if (!array_key_exists($mth_course->spark_course_id, $sparkMap)) {
            array_push($wrong_spark_ids, $mth_course->spark_course_id);
        }
    }

    foreach ($mth_subject_courses as $key3 => $mth_s_course) {
        if (!array_key_exists($mth_s_course->spark_course_id, $sparkMap)) {
            array_push($wrong_spark_ids, $mth_s_course->spark_course_id);
        }
    }

    remove_incorrect_enrolled_course($sparkMap);


    $enroll_users = []; // new courses =>person_id : enroll data
    $user_spark_course = []; // new course

    $old_temporary = [];
    if ($semester == 1) {
        $spark_temporary_course1 = mth_sparkCourse::findBySemester(0);
        foreach ($spark_temporary_course1 as $key => $value) {
            if (!array_key_exists($value->person_id, $old_temporary)) {
                $old_temporary[$value->person_id] = [$value->spark_course_id];
            } else {
                // array_push($user_spark_course[$value->person_id], $value->spark_course_id);
                if (!in_array($value->spark_course_id, $old_temporary[$value->person_id])) {
                    array_push($old_temporary[$value->person_id], $value->spark_course_id);
                }
            }
        }
    }


    foreach ($user_course_list as $key => $value) {

        $spark_course_id = $value->provider_spark_course ? $value->provider_spark_course :  $value->subject_spark_course;
        $course_type = $value->provider_spark_course ? "provider" : "subject";
        $mth_course_id = $value->provider_spark_course ? $value->provider_course_id :  $value->course_id;


        if ($value->person_id && !array_key_exists($value->person_id, $enroll_users)) {
            $enroll_users[$value->person_id] = [
                'person_id' => $value->person_id,
                'parent_id' => $value->parent_id,
                'first_name' => $value->preferred_first_name ? $value->preferred_first_name : $value->first_name,
                'last_name' => $value->preferred_last_name ? $value->preferred_last_name : $value->last_name,
                'middle_name' => $value->middle_name,
                'email' => $value->email,
                'birthday' => $value->date_of_birth,
            ];
        }

        if ($spark_course_id) {
            if ($semester == 1) { // ignore semester0 course
                if (array_key_exists($value->person_id, $old_temporary)) { // check if old temporary list has this person spark list
                    $old_spark_list = $old_temporary[$value->person_id];

                    if (!in_array($spark_course_id, $old_spark_list)) { // check if this person old spark list has this spark course
                        if (!array_key_exists($value->person_id, $user_spark_course)) {
                            $user_spark_course[$value->person_id] = [[
                                'spark_course_id' => $spark_course_id,
                                'course_type' => $course_type,
                                'mth_course_id' => $mth_course_id,
                                'period' => $value->period,
                            ]];
                        } else {
                            // array_push($user_spark_course[$value->person_id], $value->spark_course_id);
                            if (!in_array($spark_course_id, $user_spark_course[$value->person_id])) {
                                // array_push($user_spark_course[$value->person_id], [
                                //     'spark_course_id' => $spark_course_id,
                                //     'course_type' => $course_type,
                                //     'mth_course_id' => $mth_course_id,
                                //     'period' => $value->period,
                                // ]);

                                $existing_course = $user_spark_course[$value->person_id];
                                $is_flag = 0;
                                foreach ($existing_course as $key => $exist_course) {
                                    if ($exist_course['course_type'] == $course_type && $exist_course['period'] == $value->period) {
                                        $is_flag = 1;
                                    }
                                }
                                if ($is_flag == 0) {
                                    array_push($user_spark_course[$value->person_id], [
                                        'spark_course_id' => $spark_course_id,
                                        'course_type' => $course_type,
                                        'mth_course_id' => $mth_course_id,
                                        'period' => $value->period,
                                    ]);
                                }
                            }
                        }
                    }
                } else {
                    if (!array_key_exists($value->person_id, $user_spark_course)) {
                        $user_spark_course[$value->person_id] = [[
                            'spark_course_id' => $spark_course_id,
                            'course_type' => $course_type,
                            'mth_course_id' => $mth_course_id,
                            'period' => $value->period,
                        ]];
                    } else {
                        // array_push($user_spark_course[$value->person_id], $value->spark_course_id);

                        $existing_course = $user_spark_course[$value->person_id];
                        $is_flag = 0;
                        foreach ($existing_course as $key => $exist_course) {
                            if ($exist_course['course_type'] == $course_type && $exist_course['period'] == $value->period) {
                                $is_flag = 1;
                            }
                        }
                        if ($is_flag == 0) {
                            array_push($user_spark_course[$value->person_id], [
                                'spark_course_id' => $spark_course_id,
                                'course_type' => $course_type,
                                'mth_course_id' => $mth_course_id,
                                'period' => $value->period,
                            ]);
                        }
                    }
                }
            } else {
                if (!array_key_exists($value->person_id, $user_spark_course)) {
                    $user_spark_course[$value->person_id] = [[
                        'spark_course_id' => $spark_course_id,
                        'course_type' => $course_type,
                        'mth_course_id' => $mth_course_id,
                        'period' => $value->period,
                    ]];
                } else {
                    // array_push($user_spark_course[$value->person_id], $value->spark_course_id);
                    if (!in_array($spark_course_id, $user_spark_course[$value->person_id])) {
                        array_push($user_spark_course[$value->person_id], [
                            'spark_course_id' => $spark_course_id,
                            'course_type' => $course_type,
                            'mth_course_id' => $mth_course_id,
                            'period' => $value->period,
                        ]);
                    }
                }
            }
        }
    }


    store_user_to_spark($enroll_users, $user_spark_course, $semester, $sparkMap);


    return $wrong_spark_ids;
}


function remove_first_enroll_course()
{

    $spark_users = mth_sparkUser::find_all();
    $spark_users_map = [];
    foreach ($spark_users as $key => $user) {
        $spark_users_map[$user->person_id] = $user->spark_user_id;
    }

    $first_spark_courses = mth_sparkCourse::findBySemester(0);

    $old_spark_map = [];

    foreach ($first_spark_courses as $key => $course) {
        $old_spark_map[$course->person_id . "-" . $course->period] = $course->course_type."-".$course->spark_course_id;
    }


    $semester1_course = mth_schedule_period::getSparkCourses(1); // 2nd semester all courses

    $new_spark_map = [];
    foreach ($semester1_course as $key => $course) {
        $spark_course_id = $course->provider_spark_course ? $course->provider_spark_course : $course->subject_spark_course;

        if (!$spark_course_id) {
            $new_spark_map[$course->person_id . "-" . $course->period] = 'provider-null'; // we have to unenroll this courses
            $new_spark_map[$course->person_id . "-" . $course->period] = 'subject-null'; // we have to unenroll this courses
        } else {
            $course_type = $course->provider_spark_course ? "provider" : "subject";
            $new_spark_map[$course->person_id . "-" . $course->period] = $course_type."-".$spark_course_id;
        }
    }

    $remove_spark_course = [];

    foreach ($old_spark_map as $key => $old_course) {
        if (array_key_exists($key, $new_spark_map)) { // if updated 2nd semester in already enrolled 1st semester
            $new_course = $new_spark_map[$key];
            if ($old_course != $new_course) {
                $person_id_array = explode("-", $key);
                $spark_user_id = $spark_users_map[$person_id_array[0]];

                $old_spark_course_id = explode("-", $old_course)[1];

                if (!array_key_exists($spark_user_id, $remove_spark_course)) {
                    $remove_spark_course[$spark_user_id] = [$old_spark_course_id];
                } else {
                    array_push($remove_spark_course[$spark_user_id], $old_spark_course_id);
                }
            }
        }
    }

    // unenroll action
    foreach ($remove_spark_course as $key => $remove_course) {
        $remove_course_list = implode(",", $remove_course);

        $enroll_course = remove_course_enroll($key, $remove_course_list);

        if ($enroll_course['status'] == 'success') {
            mid_remove_spark_course_temporary($remove_course_list, $key, 0);
        }
    }
}

function register_user_second($second_list, $semester, $sparkMap)
{

    // first course was spark one. but that was changed to non-spark one. so we have to unenroll first spark course from that student.
    // also, if semester1 spark course was changed to another spark one, we have to unenroll first spark course, too.
    remove_first_enroll_course();


    // enroll second semester ( same first one only ignore duplicate courses)
    register_user($second_list, $semester, $sparkMap);
}

function end_year()
{
    // complete all courses(semester1, semester2) 
    $all_spark_courses = mth_sparkCourse::find_incompleted_courses();
    $course_list = [];
    foreach ($all_spark_courses as $key => $value) {
        if (!array_key_exists($value->spark_course_id, $course_list)) {
            $course_list[$value->spark_course_id] = [$value->spark_user_id];
        } else {
            array_push($course_list[$value->spark_course_id], $value->spark_user_id);
        }
    }

    foreach ($course_list as $key => $value) {
        $spark_user_ids = implode(",", $value);
        $res = complete_course($key, $spark_user_ids);

        if ($res['status'] == 'success') {
            mth_sparkCourse::mark_complete($key, $spark_user_ids);
        }
    }

    // This is only for testing.
    // Please don't try it in production environment without developer team's agree

    // delete (archive) all spark student
    $delete_users = [];

    // $all_students = get_spark_users("Student"); // spark panel users

    // foreach ($all_students['data'] as $key => $student) {
    //     $del_id = $student['id'];
    //     $res = delete_spark_user($del_id);
    //     if ($res['status'] ==  "success") {
    //         array_push($delete_users, $del_id);
    //     }
    // }

    // if (count($delete_users) > 0) {
    //     $ids = implode(",", $delete_users);
    //     mth_sparkUser::deleteByIds($ids);
    // }

    $all_students = mth_sparkUser::find_all(); // mth spark users
    foreach ($all_students as $key => $student) {
        $res = delete_spark_user($student->spark_user_id);

        if ($res['status'] ==  "success") {
            array_push($delete_users, $student->spark_user_id);
        }
    }
    if (count($delete_users) > 0) {
        $ids = implode(",", $delete_users);
        mth_sparkUser::deleteByIds($ids);
    }
}

function get_dashboard_data($first_sem_start, $second_sem_start, $sem_end, $sparkMap)
{

    $today = date('Y-m-d');
    $accept_status = mth_schedule::STATUS_ACCEPTED;

    if (strtotime($today) >= strtotime($sem_end)) {
        // execute end event
        return [
            'first_pending' => 0,
            'first_enrolled' => 0,
            'first_total' => 0,

            'second_pending' => 0,
            'second_enrolled' => 0,
            'second_total' => 0,

            'pending_removed' => 0,
            'enrolled_removed' => 0,
            'total_removed' => 0,

            'pending_student' => 0,
            'enrolled_student' => 0,
            'total_student' => 0,
        ];
    } elseif (strtotime($today) >= strtotime($second_sem_start)) {
        // execute second semester event
        // execute first semester event

        $pending_student = [];
        $enrolled_students = mth_sparkUser::find_all();
        $old_student_map = [];
        foreach ($enrolled_students as $key => $user) {
            $old_student_map[$user->person_id] = $user;
        }

        $new_first_spark_users = get_provider_course(1);

        $new_first_map = [];
        foreach ($new_first_spark_users as $key => $new_first_course) {
            $spark_course_id = $new_first_course->subject_spark_course ? $new_first_course->subject_spark_course : $new_first_course->provider_spark_course;
            $course_type = $new_first_course->subject_spark_course ? 'subject' : 'provider';
            $mth_course = $new_first_course->subject_spark_course ? $new_first_course->course_id : $new_first_course->provider_course_id;
            $new_first_map["$new_first_course->person_id-$spark_course_id-$new_first_course->period-$course_type-$mth_course"] = $new_first_course;
        }


        $enrolled_first_courses = mth_sparkCourse::findHistorySemester(0);

        $first_enrolled_count = count($enrolled_first_courses);

        // second semester ----------------------------

        $pending_removed = [];
        $enrolled_removed = [];

        $new_second_spark_users = get_provider_course(2);

        $new_second_map = [];

        foreach ($new_second_spark_users as $key => $new_second_course) {
            $spark_course_id = $new_second_course->subject_spark_course ? $new_second_course->subject_spark_course : $new_second_course->provider_spark_course;
            $course_type = $new_second_course->subject_spark_course ? 'subject' : 'provider';
            $mth_course = $new_second_course->subject_spark_course ? $new_second_course->course_id : $new_second_course->provider_course_id;
            $new_second_map["$new_second_course->person_id-$spark_course_id-$new_second_course->period-$course_type-$mth_course"] = $spark_course_id;
        }

        $enrolled_second_courses = mth_sparkCourse::findAllSemester(1); // get 2nd semester list
        $second_enroll_map = [];
        $enrolled_all_courses = mth_sparkCourse::findAllSemester(-1); // get all enrolled list
        $all_enroll_map = [];
        foreach ($enrolled_all_courses as $key => $value) {
            $all_enroll_map["$value->person_id-$value->spark_course_id-$value->period-$value->course_type-$value->mth_course_id"] = $value;
        }

        foreach ($enrolled_second_courses as $key => $value) {
            $second_enroll_map["$value->person_id-$value->spark_course_id-$value->period-$value->course_type-$value->mth_course_id"] = $value;
        }

        $second_pending_array = [];
        $second_enrolled_array = [];

        foreach ($new_second_map as $key => $new_course2) {
            if (!array_key_exists($key, $all_enroll_map)) { // pending first mth course

                if (array_key_exists($new_course2, $sparkMap)) { // if spark course is available course id
                    array_push($second_pending_array, "$key");
                    $person_id = explode("-", $key)[0];
                    array_push($pending_student, $person_id);
                } else { // here is wrong course or removed course by the spark team
                    $ob = explode("-", $key);
                    $person_id = $ob[0];
                    $period = $ob[2];
                    $course_type = $ob[3];
                    $mth_course_id = $ob[4];
                    $is_enrolled = mth_sparkCourse::getByInfo($person_id, $period, $course_type, $mth_course_id, 1);
                    if (!$is_enrolled) {
                        array_push($second_pending_array, "$key");
                        $person_id = explode("-", $key)[0];
                        array_push($pending_student, $person_id);
                    }
                }
            } else { // enrolled first mth course
                array_push($second_enrolled_array, "$key");
            }
        }

        foreach ($second_enroll_map as $key => $enrolled_course2) {
            $new_course = mth_schedule_period::checkSparkCourse($enrolled_course2->person_id, $enrolled_course2->period, 1);
            if (!$new_course) {
                array_push($pending_removed, "$key");
            } else {
                $new_spark_course_id = $enrolled_course2->course_type == "subject" ? $new_course->subject_spark_course : $new_course->provider_spark_course;
                if ($enrolled_course2->spark_course_id != $new_spark_course_id && $new_course->status == $accept_status && (array_key_exists($new_spark_course_id, $sparkMap) || !$new_spark_course_id)) {
                    array_push($pending_removed, "$key");
                }
            }
        }


        $enrolled_second_courses = mth_sparkCourse::findAllSemester(1);


        $second_pending_count = count(array_unique($second_pending_array));
        $second_enrolled_count = count($enrolled_second_courses);
        // $second_enrolled_count = count(array_unique($second_enrolled_array));


        $pending_student = array_unique($pending_student);

        $pending_student_count = 0;

        foreach ($pending_student as $key => $user_id) {
            if (!array_key_exists($user_id, $old_student_map)) {
                $pending_student_count++;
            }
        }

        $removed_courses = mth_sparkCourse::removed_course();

        return [
            'first_pending' => 0,
            'first_enrolled' => $first_enrolled_count,
            'first_total' => 0 + $first_enrolled_count,

            'second_pending' => $second_pending_count,
            'second_enrolled' => $second_enrolled_count,
            'second_total' => $second_pending_count + $second_enrolled_count,

            'pending_removed' => count(array_unique($pending_removed)),
            'enrolled_removed' => count($removed_courses),
            'total_removed' => count($removed_courses) + count(array_unique($pending_removed)),

            'pending_student' => $pending_student_count,
            'enrolled_student' => count($enrolled_students),
            'total_student' => count($enrolled_students) + $pending_student_count,
        ];
    } elseif (strtotime($today) >= strtotime($first_sem_start)) {
        // execute first semester event

        $pending_student = [];
        $enrolled_students = mth_sparkUser::find_all();
        $old_student_map = [];
        foreach ($enrolled_students as $key => $user) {
            $old_student_map[$user->person_id] = $user;
        }

        $new_first_spark_users = get_provider_course(1);

        $new_first_map = [];
        foreach ($new_first_spark_users as $key => $new_first_course) {
            $spark_course_id = $new_first_course->subject_spark_course ? $new_first_course->subject_spark_course : $new_first_course->provider_spark_course;
            $course_type = $new_first_course->subject_spark_course ? 'subject' : 'provider';
            $mth_course = $new_first_course->subject_spark_course ? $new_first_course->course_id : $new_first_course->provider_course_id;
            $new_first_map["$new_first_course->person_id-$spark_course_id-$new_first_course->period-$course_type-$mth_course"] = $spark_course_id;
        }


        $enrolled_first_courses = mth_sparkCourse::findAllSemester(0);
        $first_enroll_map = [];

        foreach ($enrolled_first_courses as $key => $value) {
            $first_enroll_map["$value->person_id-$value->spark_course_id-$value->period-$value->course_type-$value->mth_course_id"] = $value;
        }

        $first_pending_array = [];
        $first_enrolled_array = [];


        foreach ($new_first_map as $key => $new_course1) {
            if (!array_key_exists($key, $first_enroll_map)) { // pending first mth course
                if (array_key_exists($new_course1, $sparkMap)) { // if spark course is available course id
                    array_push($first_pending_array, "$key");
                    $person_id = explode("-", $key)[0];
                    array_push($pending_student, $person_id);
                } else { // here is wrong course or removed course by the spark team
                    $ob = explode("-", $key);
                    $person_id = $ob[0];
                    $period = $ob[2];
                    $course_type = $ob[3];
                    $mth_course_id = $ob[4];
                    $is_enrolled = mth_sparkCourse::getByInfo($person_id, $period, $course_type, $mth_course_id, 0);
                    if (!$is_enrolled) {
                        array_push($first_pending_array, "$key");
                        $person_id = explode("-", $key)[0];
                        array_push($pending_student, $person_id);
                    }
                }
            }
        }



        $pending_removed = [];
        $enrolled_removed = [];



        foreach ($first_enroll_map as $key => $enrolled_course1) {
            $new_course = mth_schedule_period::checkSparkCourse($enrolled_course1->person_id, $enrolled_course1->period, 0);
            if (!$new_course) {
                array_push($pending_removed, "$key");
            } else {
                $new_spark_course_id = $enrolled_course1->course_type == "subject" ? $new_course->subject_spark_course : $new_course->provider_spark_course;
                if ($enrolled_course1->spark_course_id != $new_spark_course_id && $new_course->status == $accept_status && (array_key_exists($new_spark_course_id, $sparkMap) || !$new_spark_course_id)) {
                    array_push($pending_removed, "$key");
                }
            }

            // if (!array_key_exists($key, $new_first_map)) { // pending first mth course
            //     array_push($pending_removed, "$key");
            // }
        }



        $first_pending_count = count(array_unique($first_pending_array));
        $first_enrolled_count = count($first_enroll_map);


        // second semester ----------------------------

        $new_second_spark_users = get_provider_course(2);

        $new_second_map = [];

        foreach ($new_second_spark_users as $key => $new_second_course) {
            $spark_course_id = $new_second_course->subject_spark_course ? $new_second_course->subject_spark_course : $new_second_course->provider_spark_course;
            $course_type = $new_second_course->subject_spark_course ? 'subject' : 'provider';
            $mth_course = $new_second_course->subject_spark_course ? $new_second_course->course_id : $new_second_course->provider_course_id;
            $new_second_map["$new_second_course->person_id-$spark_course_id-$new_second_course->period-$course_type-$mth_course"] = $spark_course_id;
        }

        $enrolled_second_courses = mth_sparkCourse::findAllSemester(1);
        $second_enroll_map = [];
        foreach ($enrolled_second_courses as $key => $value) {
            $second_enroll_map["$value->person_id-$value->spark_course_id-$value->period-$value->course_type-$value->mth_course_id"] = $value;
        }

        $second_pending_array = [];
        $second_enrolled_array = [];

        $enrolled_all_courses = mth_sparkCourse::findAllSemester(-1); // get all enrolled list
        $all_enroll_map = [];
        foreach ($enrolled_all_courses as $key => $value) {
            $all_enroll_map["$value->person_id-$value->spark_course_id-$value->period-$value->course_type-$value->mth_course_id"] = $value;
        }

        foreach ($new_second_map as $key => $new_course2) {
            if (!array_key_exists($key, $all_enroll_map)) { // pending first mth course

                if (array_key_exists($new_course2, $sparkMap)) { // if spark course is available course id
                    array_push($second_pending_array, "$key");
                    $person_id = explode("-", $key)[0];
                    array_push($pending_student, $person_id);
                } else { // here is wrong course or removed course by the spark team
                    $ob = explode("-", $key);
                    $person_id = $ob[0];
                    $period = $ob[2];
                    $course_type = $ob[3];
                    $mth_course_id = $ob[4];
                    $is_enrolled = mth_sparkCourse::getByInfo($person_id, $period, $course_type, $mth_course_id, 1);
                    if (!$is_enrolled) {
                        array_push($second_pending_array, "$key");
                        $person_id = explode("-", $key)[0];
                        array_push($pending_student, $person_id);
                    }
                }
            } else { // enrolled first mth course
                array_push($second_enrolled_array, "$key");
            }
        }

        foreach ($second_enroll_map as $key => $enrolled_course2) {
            $new_course = mth_schedule_period::checkSparkCourse($enrolled_course2->person_id, $enrolled_course2->period, 1);
            if (!$new_course) {
                array_push($pending_removed, "$key");
            } else {
                $new_spark_course_id = $enrolled_course2->course_type == "subject" ? $new_course->subject_spark_course : $new_course->provider_spark_course;
                if ($enrolled_course2->spark_course_id != $new_spark_course_id && $new_course->status == $accept_status && (array_key_exists($new_spark_course_id, $sparkMap) || !$new_spark_course_id)) {
                    array_push($pending_removed, "$key");
                }
            }
        }

        // foreach ($second_enroll_map as $key => $enroll_course2) {
        //     if (!array_key_exists($key, $new_second_map)) {
        //         array_push($pending_removed, $key);
        //     }
        // }

        $enrolled_second_courses = mth_sparkCourse::findAllSemester(1);


        $second_enrolled_count = count($enrolled_second_courses);


        $second_pending_count = count(array_unique($second_pending_array));
        // $second_enrolled_count = count(array_unique($second_enrolled_array));


        $pending_student = array_unique($pending_student);

        $pending_student_count = 0;

        foreach ($pending_student as $key => $user_id) {
            if (!array_key_exists($user_id, $old_student_map)) {
                $pending_student_count++;
            }
        }

        $removed_courses = mth_sparkCourse::removed_course();



        return [
            'first_pending' => $first_pending_count,
            'first_enrolled' => $first_enrolled_count,
            'first_total' => $first_pending_count + $first_enrolled_count,

            'second_pending' => $second_pending_count,
            'second_enrolled' => $second_enrolled_count,
            'second_total' => $second_pending_count + $second_enrolled_count,

            'pending_removed' => count(array_unique($pending_removed)),
            'enrolled_removed' => count($removed_courses),
            'total_removed' => count($removed_courses) + count(array_unique($pending_removed)),

            'pending_student' => $pending_student_count,
            'enrolled_student' => count($enrolled_students),
            'total_student' => count($enrolled_students) + $pending_student_count,
        ];
    }
}
