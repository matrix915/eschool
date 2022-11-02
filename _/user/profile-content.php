<?php
// use mth\packet;
/* @var $parent mth_parent */
/* @var $student mth_student */
/* @var $packet mth_packet */
/* @var $packetURI */
/* @var $packetStep */
/* @var $packetRunValidation */

core_user::getUserLevel() || core_secure::loadLogin();

$user = core_user::getCurrentUser();

$person = $user->isStudent() ? mth_student::getByUserID($user->getID()) : $user->getPerson();
$parent = $user->isStudent() ? $person->getParent() : $person;

// $packet = mth_packet::getStudentPacket($user);

if (!empty($_GET['deletePhone'])) {

    if (
        ($phone = mth_phone::getPhone($_GET['deletePhone']))
        && $phone->getPersonID() == $parent->getPersonID()
    ) {
        if ($phone->delete()) {
            core_notify::addMessage('Phone deleted');
        } else {
            core_notify::addError('Unable to delete phone');
        }
    } else {
        core_notify::addError('Unable to find phone');
    }
    header('location:/_/user/profile');
    exit();
}

if (!empty($_GET['avatar'])) {
    if ($result = $user->uploadAvatar($_FILES['name'])) {
        echo $result;
        $user->saveAvatar($result);
        core_user::setCurrentUser($user);
    }
    exit();
}

if (!empty($_GET['formID'])) {
    core_loader::formSubmitable('editProfile-' . $_GET['formID']) || die();

    if (!$user->checkPassword($_POST['currentPassword'])) {
        core_notify::addError('The password you entered for your current password was not correct. No information saved.');
        header('location:./profile');
        exit();
    }

    if ($user) {
        if (!$user->isStudent()) {
            $emailChange = strtolower($_POST['email']) != $user->getEmail();
            if ($emailChange && core_user::findUser($_POST['email'])) {
                $emailChange = false;
                core_notify::addError('The email ' . req_post::txt('email') . ' is associated with a different account and cannot be used.');
            } elseif ($emailChange && mth_person::getPeople(['email' => req_post::txt('email')])) {
                $emailChange = false;
                core_notify::addError('The email ' . req_post::txt('email') . ' is associated with a different person and cannot be used.');
            }
            if ($parent->setEmail($_POST['email']) && $emailChange) {
                if ($parent->errorUpdatingCanvasLoginEmail() && core_setting::get('AccountAuthorizationConfigID', 'Canvas')) {
                    core_notify::addError('Unable to update your canvas account. Please call us to have us update your canvas account or you will be unable to login to canvas.');
                } else {
                    $ses = new mth\aws\ses();
                    if (!$ses->verifyEmail($_POST['email'], mth_emailverifier::getTypeId(mth_emailverifier::TYPE_CHANGEEMAIL))) {
                        core_notify::addError('Unable to send verification to ' . req_post::txt('email'));
                    } else {
                        mth_emailverifier::insert($_POST['email'], $user->getID(), mth_emailverifier::TYPE_CHANGEEMAIL);
                        core_notify::addWarning('Your email was changed, We will be sending an email verification you shortly');
                    }
                }
            } elseif ($emailChange) {
                core_notify::addError('Unable to change your email. Please try again or contact us for support.');
            }

            foreach ($_POST['phone'] as $phoneForm) {
                if (!empty($phoneForm['id'])) {
                    $phone = mth_phone::getPhone($phoneForm['id']);
                } elseif (!empty($phoneForm['number'])) {
                    $phone = mth_phone::create($parent);
                } else {
                    continue;
                }
                $phone->saveForm($phoneForm);
            }

            if (isset($_POST['address'])) {
                mth_address::saveAddressForm($_POST['address']);
            }

            if (!$person->setName(null, null, null, $_POST['first_name'], $_POST['last_name'])) {
                core_notify::addError('Unable to change personal information.');
            }

            if (!empty($_POST['school_district'])) {
                $person->getAddress()->setSchoolDistrictOfR($_POST['school_district']);
            }

        }

        core_notify::addMessage('Changes saved');
    }

    if (!empty($_POST['newPassword'])) {
        if ($user->changePassword($_POST['newPassword'])) {
            core_notify::addMessage('Your password has been changed, you will need to use your new password to login from now on.');
        } else {
            core_notify::addError('Unable to change your password. Please try again later, or contact us if you need assistance');
        }
    }
    header('location:./profile');
    exit();
}

core_loadeR::addCssRef('cropper', core_config::getThemeURI() . '/vendor/cropper/cropper.min.css');
core_loader::includejQueryValidate();
cms_page::setPageTitle('Edit Profile');
core_loader::printHeader('student');

function account_details($person, $user)
{
    ?>
<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0">Account Settings</h4>
    </div>
    <div class="card-block">
        <?php if ($user->isStudent()): ?>
        <label>Email:</label>
        <b><?=$person->getEmail()?></b>
        <?php else: ?>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" id="email" class="form-control"
                value="<?=$user->isAdmin() || $user->isSubAdmin() ? $user->getEmail() : $person->getEmail()?>"
                required />
            <br>
            <div class="alert  alert-alt alert-info">If you change your email address you will need to use the new one
                to login.</div>
        </div>
        <?php endif;?>

        <fieldset class="p-20" style="border:1px solid #ccc;">
            <div class="alert dark alert-alt alert-warning">
                Use these fields only if you want to change your password
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="newPassword" id="newPassword" class="form-control" />
                <p><small>Your password should be at least 6 characters long and include uppercase, lowercase, numbers
                        and other characters (!@#$%^&*)</small></p>
            </div>

            <div class="form-group">
                <label>Re-enter New Password</label>
                <input type="password" name="reNewPassword" id="reNewPassword" class="form-control" />
            </div>
        </fieldset>
    </div>
    <div class="card-footer">
        <button type="button" class="btn btn-primary btn-round save-btn" data-toggle="modal"
            data-target="#passwordVerify">Save</button>
    </div>
</div>
<?php
}
?>

<div class="page">
    <?=core_loader::printBreadCrumb('window');?>
    <div class="page-content container-fluid">
        <input style="position:absolute;left: -1000px;" name="name" id="avatar" type="file" accept='image/*' />
        <form action="?formID=<?=time()?>" method="post" id="profileEditForm">
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-0">Personal Details</h4>
                        </div>
                        <div class="card-block">
                            <div class="row">
                                <div class="col-md-4 text-center">
                                    <div class="edit-profile-picture mb-20">
                                        <div class="avatar-cont rounded-circle img-thumbnail" id="avatar-container"
                                            style="margin:0 auto;width:150px;height:150px;background-image:url(<?=$user && $user->getAvatar() ? $user->getAvatar() : (core_config::getThemeURI() . '/assets/portraits/default.png')?>)">
                                        </div>
                                        <!-- <img src="<?=core_config::getThemeURI()?>/assets/portraits/default.png" class="img-thumbnail rounded-circle"> -->
                                        <br><br>
                                        <div class="upload-status"></div>
                                        <button class="btn btn-round" type="button" onclick=change_photo()><i
                                                class="fa fa-camera"></i> Change Photo</button>

                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label>Preferred First Name</label>
                                        <input type="text" name="first_name" id="first_name" required
                                            class="form-control" value="<?=$person->getPreferredFirstName()?>"
                                            <?=$user->isStudent() ? 'disabled' : ''?>>
                                    </div>
                                    <div class="form-group">
                                        <label>Preferred Last Name</label>
                                        <input type="text" name="last_name" id="last_name" required class="form-control"
                                            value="<?=$person->getPreferredLastName()?>"
                                            <?=$user->isStudent() ? 'disabled' : ''?>>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="button" class="btn btn-primary btn-round save-btn" data-toggle="modal"
                                data-target="#passwordVerify">Save</button>
                        </div>
                    </div>

                    <?php
                        if (!$user->isStudent()) {
                            account_details($person, $user);
                        }?>

                </div>

                <div class="col-md-6">
                    <?php if (!$user->isStudent()): ?>
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-0">Contact Details</h4>
                        </div>
                        <div class="card-block">
                            <p>
                            <h4>Phone</h4>
                            <?php
                                $lastNum = 0;
                                include $_SERVER['DOCUMENT_ROOT'] . '/_/mth_forms/phone.php';
                                echo '<table class="table">';
                                foreach ($parent->getPhoneNumbers() as $num => $phone) {
                                    echo '<tr class="sub-item"><td>' . $phone . '</td>
                                                                    <td class="text-right"><a onclick="$(this).closest(\'tr\').hide(); $(\'#mth_phone-phone-' . $num . '\').fadeIn();"><i class="fa fa-edit"></i> edit</a> </td>
                                                                    <td><a onclick="deletePhone(' . $phone->getID() . ')"><i class="fa fa-trash"></i> delete</a></td>
                                                                    </tr>';
                                    printPhoneFields('phone[' . $num . ']', false, $phone);
                                    $lastNum = $num;
                                }
                                echo ' </table>';
                                $num = $lastNum + 1;
                                echo '<a onclick="$(this).hide(); $(\'#mth_phone-phone-' . $num . '\').fadeIn();" class="new-item-link btn btn-icon btn-pink btn-round btn-sm"><i class="icon md-plus" aria-hidden="true"></i> New</a>';
                                printPhoneFields('phone[' . $num . ']');
                                ?>

                            </p>
                            <?php if (!$user->isTeachers()): ?>
                            <h4 class="mt-40">Address</h4>
                            <?php include $_SERVER['DOCUMENT_ROOT'] . '/_/mth_forms/address.php';?>
                            <?php printAddressFields('address', true)?>
                            <?php endif;?>

                            
                            <?php 
                                // $parent->getAddress()->getState()
                                // $user->getID() 
                                // $parent->getID() 
                                // $parent->getPersonID()
                                // $person->getEmail() 
                                // $person->getSchoolDistrict()
                                ?>
                            
                        </div>
                        <div class="card-footer">
                            <button type="button" class="btn btn-primary btn-round save-btn" data-toggle="modal"
                                data-target="#passwordVerify">Save</button>
                        </div>
                    </div>
                    <?php else: ?>
                    <?php account_details($person, $user);?>
                    <?php endif;?>
                </div>
            </div>

            <!-- Verify Password Modal -->
            <div class="modal fade" id="passwordVerify" tabindex="-1" role="dialog"
                aria-labelledby="passwordVerifyTitle" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="passwordVerifyTitle">Current Password</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">

                            <div class="form-group">
                                <input type="password" class="form-control" id="currentPassword" name="currentPassword"
                                    required />
                            </div>
                            <div class="alert alert-alt alert-info" role="alert">
                                You must enter your current password before you can save any changes.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-round"
                                data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-round">Save</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Verify password Modal -->

            <!-- Image Crop Modal -->
            <div class="modal fade" id="cropImage" tabindex="-1" role="dialog" aria-labelledby="cropImageTitle"
                aria-hidden="true" data-backdrop="static" data-keyboard="false">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="cropImageTitle">Crop Image</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="width:100%;">
                                <img class="img-fluid" id="image" src="">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-round crop-cancel-modal"
                                data-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary btn-round" id="savecrop"
                                data-loading-text="Saving..">Save</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Image crop modal -->

        </form>
    </div>
</div>


<?php
core_loader::includejQueryUI();
core_loader::addJsRef('fileUploaderTransport', '/_/mth_includes/jQuery-File-Upload-10.31.0/js/jquery.iframe-transport.js');
core_loader::addJsRef('fileUploader', '/_/mth_includes/jQuery-File-Upload-10.31.0/js/jquery.fileupload.js');
core_loadeR::addJsRef('cropperjs', core_config::getThemeURI() . '/vendor/cropper/cropper.min.js');
core_loader::printFooter('student');
?>
<script type="text/javascript">
cropper = null;

$(function() {
    $save_btn = $('.save-btn');
    $save_btn.hide();
    $('input,select,password').change(function() {
        $save_btn.fadeIn();
    });
    $('.mth_phone').hide();
    $('#profileEditForm').validate({
        rules: {
            reNewPassword: {
                equalTo: '#newPassword'
            }
        }
    });


    $('#passwordVerify').on('shown.bs.modal', function() {
        $('#currentPassword').trigger('focus');
    });

    $.validator.addClassRules("mth_phone_number", {
        required: true,
        phoneUS: 2
    });



    $("#avatar").change(function() {
        var input = this;

        if (input.files && input.files[0]) {
            var reader = new FileReader();
            if (input.files[0].size > 1e+7) {
                swal('', 'Image is too large. Please upload image no greater than 10MB', 'error');
                return false;
            }

            reader.readAsDataURL(input.files[0]);

            reader.onload = function(e) {
                var img = new Image;
                img.src = e.target.result;
                img.onload = function() {

                    if (img.width < 150 || img.height < 150) {
                        swal('', 'Image selected is too small', 'error');
                    } else {
                        show_crop_modal(img.src);
                    }
                };
            }


        }

    });

    $('#savecrop').click(function() {
        var $this = $(this);
        $this.attr('disabled', 'disabled').text('Saving..');
        $('#crop-cancel-modal').hide();

        var image = cropper.getCroppedCanvas().toBlob(function(blob) {
            var formData = new FormData();

            formData.append('name', blob);

            // Use `jQuery.ajax` method
            $.ajax('?avatar=1', {
                method: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function() {
                    var url = cropper.getCroppedCanvas().toDataURL();
                    $('#avatar-container').css('background-image', 'url(' + url +
                        ')');
                    $save_btn.fadeOut();
                    $('#cropImage').modal('hide');

                },
                error: function() {
                    console.log('Upload error');
                }
            });
        });
    });
});

function show_crop_modal(src) {
    $('#cropImage').modal('show').on('shown.bs.modal', function() {
        var image = document.getElementById('image');
        image.setAttribute('src', src);
        if (cropper != null) {
            cropper.replace(src);
        } else {
            cropper = new Cropper(image, {
                aspectRatio: 1 / 1,
                zoomable: false,
                strict: false,
                crop: function(event) {

                }
            });
        }

    }).on('hidden.bs.modal', function() {

        $('#savecrop').removeAttr('disabled').text('Save');
        $('#crop-cancel-modal').show();
    });
}

function deleteAddress(addressID) {
    deleteAddress.addressID = addressID;
    global_confirm('Are you sure you want to delete this address? This action cannot be undone!',
        function() {
            location = '?deleteAddress=' + deleteAddress.addressID;
        });
}

function deletePhone(phoneID) {
    deletePhone.phoneID = phoneID;

    swal({
            title: "Are you sure?",
            text: "You want to delete this phone? This action cannot be undone!",
            type: "warning",
            showCancelButton: !0,
            confirmButtonClass: "btn-warning",
            confirmButtonText: "Yes",
            cancelButtonText: "Cancel",
            closeOnConfirm: !1,
            closeOnCancel: !1
        },
        function() {
            location = '?deletePhone=' + deletePhone.phoneID;
        });

}

function change_photo() {
    $('#avatar').val('').trigger('click');
}
</script>