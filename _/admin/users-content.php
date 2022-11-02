<?php

if (!empty($_GET['ajax'])) {


    switch ($_GET['ajax']) {
        case 'getUsers':
            header('Content-type: application/json');
            $users = core_user::getUsers(0, true);
            $userArr = array();
            foreach ($users as $user) {
                /* @var $user core_user */

                $userArr[$user->getID()] = array(
                    'email' => $user->getEmail(),
                    'level' => $user->getLevel(),
                    'name' => $user->getPersonName(),
                    'last_login' => $user->getLastLogin('n/j/Y'),
                    'e' => $user->canEmulate()
                );
            }
            echo json_encode($userArr);
            break;
        case 'assistantType':
            echo '<div class="form-group"><label>Assitant Type</label><select name="assistant_type" class="form-control">' . mth_assistant::getTypes(true) . '</select></div>';
            break;
        case 'assistantValue':
            echo '<div class="form-group"><label>Assitant Value</label>' . mth_assistant::getTypeValues($_GET['type'], true) . '</div>';
            break;
        case 'emulateLevel':
            $success = 0;
            if ($_euser = core_user::getUserById(req_get::int('user'))) {
                if ($_euser->setEmulatePermission(req_get::int('permission'))) {
                    $success = 1;;
                }
            }
            echo $success;
            break;
    }
    exit();
}

if (req_get::bool('e')) {
    $emulator = core_user::getCurrentUser();
    $new_active_user = core_user::getUserById(req_get::int('e'));
    core_user::setEmulator($emulator);

    // Set up masquerade user for Yeti
    if($new_active_user) {
        core_user::setCurrentUser($new_active_user);
        $token = jwt_token::createTokenForUser($new_active_user);
        jwt_token::addMasqueradeCookie($token);
    }

    $home_url = $new_active_user->getHomeUrl();
    core_loader::redirect($home_url);
}

if (req_get::bool('createUser')) {
    if (core_user::findUser(req_post::txt('new_user_email'))) {
        core_notify::addError('There is already a user with that email address!');
        header('location: ./users');
        exit();
    }
    $user = core_user::newUser(
        req_post::txt('new_user_email'),
        req_post::txt('new_user_first'),
        req_post::txt('new_user_last'),
        req_post::int('new_user_level')
    );


    if ($user) {
        $person_id = mth_person::new($user->getID(), strtolower(req_post::txt('new_user_email')), req_post::txt('new_user_first'), req_post::txt('new_user_last'));

        if (req_post::int('new_user_level') == mth_user::L_TEACHER_ASSISTANT) {
            foreach (req_post::int_array('assistant_value') as $value) {
                mth_assistant::newAsssistant($user->getID(), $value, req_post::int('assistant_type'));
            }
        }

        if (req_post::int('new_user_level') == mth_user::L_PARENT) {
            mth_parent::newParent($person_id);
        }

        if ($user->sendPasswordResetEmail()) {
            core_notify::addMessage('User invitation has been sent');
        } else {
            core_notify::addError('Unable to send user invitation! Check the error log.');
        }
    } else {
        core_notify::addError('Unable to create user account!');
    }
    header('location: ./users');
    exit();
}

if (!empty($_GET['deleteUser'])) {
    if (($user = core_user::getUserById($_GET['deleteUser']))) {
        if ($person = mth_person::getByUserId($user->getID())) {
            $person->delete();
        }
        if ($user->delete())
            core_notify::addMessage('User account deleted.');
        else
            core_notify::addError('Unable to delete user account.');
    } else {
        core_notify::addError('Unable to find user account');
    }
    header('location: ./users');
    exit();
}

if (!empty($_GET['changeLevel'])) {
    if (($user = core_user::getUserById($_GET['changeLevel']))) {
        if ($user->setLevel($_GET['level']))
            core_notify::addMessage('User level updated.');
        else
            core_notify::addError('Unable to change user level.');
    } else {
        core_notify::addError('Unable to find user account');
    }
    header('location: ./users');
    exit();
}

core_loader::includeBootstrapDataTables('css');

cms_page::setPageTitle('User Admin');
cms_page::setPageContent('');
core_loader::printHeader('admin');
?>
    <style type="text/css">
        #createUserForm {
            /* border: solid 1px #ddd;
            padding: 1px 30px 20px;
            margin: 10px 0 20px 0;
            width: 300px;
            background-color: rgba(255, 255, 255, .5); */
        }

        span.hide {
            display: none;
        }
    </style>


    <button type="button" onclick="$('#createUserForm').show()" class="btn btn-primary btn-round">
        New User
    </button>
    <hr>

    <form action="?createUser=1" method="post" id="createUserForm" style="display: none;">
        <div class="card">
            <div class="card-block">
                <p>
                    This user will receive an email giving them a link to create a password.
                </p>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="new_user_email" class="form-control">
                </div>
                <div class="form-group">
                    <label>First Name:</label>
                    <input type="text" name="new_user_first" class="form-control">
                </div>
                <div class="form-group">
                    <label>Last Name:</label>
                    <input type="text" name="new_user_last" class="form-control">
                </div>
                <div class="form-group">
                    <label>Level:</label>
                    <select name="new_user_level" class="form-control">
                        <?php foreach (core_setting::userLevelNames() as $level => $name) : ?>
                            <option value="<?= $level ?>"><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="assistant_type"></div>
                <div id="assistant_value"></div>
            </div>
            <div class="card-footer">
                <button class="btn btn-round btn-primary" type="submit">Create User</button>
                <button class="btn btn-round btn-secondary" onclick="$('#createUserForm').hide()" type="button">Cancel</button>
            </div>
        </div>
    </form>


    <table id="core_user_table" class="table responsive">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Level</th>
                <th>Last Login</th>
                <th>Actions</th>
                <th>Can Emulate</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>

    <?php
    core_loader::includeBootstrapDataTables('js');
    core_loader::printFooter('admin');
    ?>
    <script type="text/javascript">
        var assitant_value = <?= mth_user::L_TEACHER_ASSISTANT ?>;
        $(function() {
            $('#core_user_table').dataTable({
                'aoColumnDefs': [{
                    "bSortable": false,
                    "aTargets": [5]
                }],
                "bStateSave": true
            });
            global_waiting();
            $.ajax({
                url: '?ajax=getUsers',
                success: function(data) {
                    for (var uID in data) {
                        $('#core_user_table').dataTable().fnAddData([
                            uID,
                            data[uID].name,
                            data[uID].email,
                            printLevelSelect(uID, data[uID].level),
                            data[uID].last_login,
                            (uID != 1 ? '<a onclick="deleteUser(' + uID + ')">Delete</a>' : '')
                            <?php if (core_user::canMasquerade()) : ?> + ' <a href="?e=' + uID + '" onclick="return confirm(\'This should only be used for testing purposes.\')">Emulate</a>'
                            <?php endif; ?>,
                            printEmulateCB(uID, data[uID].level, data[uID].e)
                        ], false);
                    }
                    $('#core_user_table').dataTable().fnDraw();
                    global_waiting_hide();
                }
            });

            $('#createUserForm').on('change', '[name="new_user_level"]', function() {
                if ($(this).val() == assitant_value) {
                    $.get('?ajax=assistantType', function(data) {
                        $('#assistant_type').html(data);
                        $('[name="assistant_type"]').trigger('change');
                    });
                }
            }).on('change', '[name="assistant_type"]', function() {
                var type = $(this).val();
                $.get('?ajax=assistantValue&type=' + type, function(data) {
                    $('#assistant_value').html(data);
                });
            });


        });

        function deleteUser(userId) {
            deleteUser.userId = userId;

            swal({
                    title: "",
                    text: "Are you sure you want to delete this user?",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonClass: "btn-warning",
                    confirmButtonText: "Yes",
                    cancelButtonText: "Cancel",
                    closeOnConfirm: true,
                    closeOnCancel: true
                },
                function() {
                    window.location = '?deleteUser=' + deleteUser.userId;
                });
        }

        function printEmulateCB(userID, userLevel, permission) {
            if (userLevel == <?= mth_user::L_ADMIN; ?>) {
                return '<input type="checkbox" value="' + userID + '" ' + (permission ? 'CHECKED' : '') + ' onchange="changeEmulatePermission(' + userID + ',this.checked)"/>';
            }
            return 'NA';
        }

        function printLevelSelect(userID, selectedLevel) {
            return '<span class="hide">' + (selectedLevel / 100) + '</span>' +
                '<select onchange="changeUserLevel(' + userID + ',this.value)">' +
                <?php foreach (core_setting::userLevelNames() as $level => $name) : ?> '<option value="<?= $level ?>" ' + (selectedLevel === <?= $level ?> ? 'selected' : '') + '><?= $name ?></option>' +
                <?php endforeach; ?> '</select>';
        }

        function changeUserLevel(userID, level) {
            if (level >= 10) {
                changeUserLevel.userID = userID;
                changeUserLevel.level = level;

                swal({
                        title: "",
                        text: "Are you sure you want to make this user into an Administrator?",
                        type: "warning",
                        showCancelButton: true,
                        confirmButtonClass: "btn-warning",
                        confirmButtonText: "Yes",
                        cancelButtonText: "Cancel",
                        closeOnConfirm: true,
                        closeOnCancel: true
                    },
                    function() {
                        window.location = '?changeLevel=' + changeUserLevel.userID + '&level=' + changeUserLevel.level;
                    });

            } else {
                window.location = '?changeLevel=' + userID + '&level=' + level;
            }
        }

        function changeEmulatePermission(userID, permission) {
            global_waiting();
            $.ajax({
                url: '?ajax=emulateLevel&user=' + userID + '&permission=' + (permission ? 1 : 0),
                type: 'GET',
                success: function(response) {
                    if (response == 1) {
                        toastr.success('Permission Changed.');
                    } else {
                        toastr.error('Unable to give user emulate permission.');
                    }
                    global_waiting_hide();
                },
                error: function() {
                    toastr.error('Unable to give user emulate permission.');
                    global_waiting_hide();
                }
            });
        }
    </script>