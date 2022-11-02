<?php
if (!core_user::getUserID()) {
    core_loader::redirect('/');
}
$parent = mth_parent::getByUser();

if (mth_purchasedCourse::hasPurchasedCourse($parent)) {
    core_loader::redirect('/reg');
}
$user = null;
if(!($user = mth_canvas_user::get($parent))){
    core_notify::addError('Error occur in getting your canvas user info. Please contact administrator');
}

cms_page::setPageTitle('Parent Home');
cms_page::setPageContent('<p>This site will allow you to manage your students, their applications, enrollments, and schedules.</p>');

core_loader::printHeader('student');

?>
<div class="page parent-profile-page">
    <div class="page-content container-fluid">
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <a href="/_/user/profile">
                    <div class="cover-photo">

                    </div>
                    </a>
                    <div class="card-block wall-person-info">
                    <a class="avatar bg-white img-bordered person-avatar avatar-cont" href="/_/user/profile" style="background-image:url(<?= $user && $user->avatar_url()?$user->avatar_url():(core_config::getThemeURI().'/assets/portraits/default.png') ?>)">
                    </a>
                    <h2 class="person-name">
                        <a href="/_/user/profile"><?= $parent->getName() ?></a>
                    </h2>
                    <div class="card-text">
                        <a class="blue-grey-400"><span><?= $parent->getEmail() ? $parent->getEmail() : '&nbsp;' ?></span></a>

                    </div>
                    <div class="profile-action">
                        <a class="btn btn-default btn-round  mr-10" href="/_/user/profile">
                            Edit Profile
                        </a>
                        <a class="btn btn-primary btn-round  mr-10" href="?logout=1">
                            Logout
                        </a>
                    </div>
                    </div>

                </div>
                <a  class="btn btn-primary btn-round btn-block mb-20" href="#">
                    Parent Link
                </a>

            <div class="card card-shadow">
                <div class="card-block">
                <a  class="btn btn-default btn-round btn-block waves-effect waves-light waves-round" href="/student/new">
                            <i class="icon md-account-add float-left" aria-hidden="true"></i>Add New Student
                </a>
                </div>
            </div>
            <?php foreach ($parent->getStudents() as $student) : /* @var $student mth_student */ ?>
            <?php
                $notifications = mth_student_notifications::getStudentNotifications($student); 
                $student_canvas = mth_canvas_user::get($student);
            ?>
            <div class="panel container-collapse">
                <div class="panel-heading " data-toggle="collapse" aria-hidden="true" href="#<?= $student->getSlug() ?>" aria-controls="<?= $student->getSlug() ?>">
                <div class="pr-20 pl-20 pt-15 pb-10 float-left">
                    <a href="#" class="avatar avatar-lg avatar-cont" style="height:50px;background-image:url(<?= $student_canvas && $student_canvas->avatar_url()?$student_canvas->avatar_url():(core_config::getThemeURI().'/assets/portraits/default.png') ?>)">
                            <?php
                            if (count($notifications) == 1 && in_array(mth_student_notifications::NONE, $notifications)) {
                                $notifications = [];
                            }
                            ?>
                            <?php if (count($notifications) > 0) : ?>
                                <span class="badge badge-pill badge-danger up m-0"><?= count($notifications) ?></span>
                            <?php endif; ?>
                    </a>
                </div>
                <h3 class="panel-title">

                <?= $student ?><br><small><?= $student->getGradeLevel(true) ?></small>
                </h3>

                <div class="panel-actions panel-actions-keep">
                    <i class="panel-action icon md-chevron-right icon-collapse profile-child-control" ></i>
                </div>
                </div>
                <div class="panel-body collapse info-collapse" id="<?= $student->getSlug() ?>">

                <p data-info-type="email" class="mb-10 text-nowrap">
                        <?php if ($student->getEmail()) : ?>
                        <span class="text-break">
                            <a><?= $student->getEmail() ?></a>
                        <span>
                        <a class="ml-10" href="#" data-toggle="modal" data-target="#changeEmail" data-slug="<?= $student->getSlug() ?>" data-email="<?= $student->getEmail() ?>"><i class="icon md-edit"></i> <small>(Edit Email)</small></a>
                        <?php endif; ?>
                    </p>
                    <?php if ( ($school = $student->getSOEname(mth_schoolYear::getPrevious()))) : ?>
                    <p  class="mb-10 text-nowrap">
                        <i class="icon md-graduation-cap mr-10"></i>
                        <span class="text-break"><?= mth_schoolYear::getPrevious() ?> School of Enrollment: <?= $school ?>
                        <span>
                    </p>
                    <?php endif; ?>
                    <?php if ( ($school = $student->getSOEname(mth_schoolYear::getCurrent()))) : ?>
                    <p  class="mb-10 text-nowrap">
                        <i class="icon md-graduation-cap mr-10"></i>
                        <span class="text-break">
                        <?= mth_schoolYear::getCurrent() ?> School of Enrollment: <?= $school ?>
                        <span>
                    </p>
                        <?php endif; ?>
                    
                    <p data-info-type="twitter" class="mb-10 text-nowrap">
                        <div class="checkbox-custom checkbox-primary">
                            <input type="checkbox" <?= $student->specialEd() ? 'CHECKED' : '' ?> disabled>
                            <label>
                                IEP / 5O4
                            </label>
                            </div>
                        <span class="text-break">
                        <span>
                    </p>
                    <p data-info-type="twitter" class="mb-10 text-nowrap">
                        
                            <?php if ($student->getGradeLevelValue() < 9) : ?>
                            <label>
                                Diploma Seeking: N/A
                            </label>
                                <?php else : ?>
                                <div class="checkbox-custom checkbox-primary">
                                <input type="checkbox" <?= $student->diplomaSeeking() ? 'CHECKED' : '' ?>disabled>
                                    <label>
                                        Diploma Seeking
                                    </label>
                                    </div>
                                <?php endif; ?>
                        
                        <span class="text-break">
                        <span>
                    </p>
                    <p  class="mb-10 text-nowrap">
                        <?php if ($student->getGradeLevel(mth_schoolYear::getCurrent()) > 2 && ($student->isPendingOrActive())) : ?>
                            
                            <div class="checkbox-custom checkbox-primary">
                            <input type="checkbox" <?= mth_testOptOut::getByStudent($student, mth_schoolYear::getCurrent()) ? 'CHECKED' : '' ?> disabled>
                            <label>
                                SAGE Opt-Out <small>(<?= mth_schoolYear::getCurrent() ?>)</small>
                            </label>
                            </div>
                        <?php else : ?>
                        <label>
                                SAGE Opt-Out <small>(<?= mth_schoolYear::getCurrent() ?>)</small>: N/A
                            </label>
                        <?php endif; ?>
                        
                        <span class="text-break">
                        <span>
                    </p>
                    <?php if (!empty($notifications)) : ?>
                    <br>
                    <div role="alert" class="alert dark alert-icon alert-danger">
                        <i class="icon md-notifications" aria-hidden="true"></i>
                        <h4>Notifications</h4>
                        <ul class="student-notification-list">
                            <?php foreach ($notifications as $notification) : ?>
                                <li><?= $notification ?></li>
                            <?php endforeach; ?>
                        </ul>
                        
                        </div>
                        <?php endif; ?>
                    
                    
                </div>
                <?php 
                $nextYear = mth_schoolYear::getNext();
                $hasPacket = ( ($student->getStatus() || ($nextYear && $student->getStatus($nextYear)))
                    && ($packet = mth_packet::getStudentPacket($student))
                    && $packet->getDateAccepted())
                    || (!$student->getStatus()
                    && ($nextYear)
                    && !$student->getStatus($nextYear)
                    && ($app = mth_application::getStudentApplication($student))
                    && $app->isAccepted());
                $schedule = mth_schedule::eachOfStudent($student->getID());
                $schedSlug = $schedule ? "/student/{$student->getSlug()}/schedule/{$schedule->schoolYear()}" : '';
                ?>
                <div class="panel-footer pt-20">
                    <a class="btn bg-info btn-round  waves-effect waves-light waves-round children-action<?= $hasPacket ? '' : ' disabled' ?>" href="/student/<?= $student->getSlug() ?>/packet" style="color:#fff">Enrollment Packet</a>
                    <a class="btn btn-danger btn-round  waves-effect waves-light waves-round children-action<?= $schedule ? '' : ' disabled' ?>" style="color:#fff" href="<?= $schedSlug ?>">Schedule</a>       
                </div>
            </div>
            <?php endforeach; ?>
            </div>
            <div class="col-lg-4">

                <div class="card p-20">
                    <h4 class="card-title">
                    Parent Contact Information
                    <a class="float-right" href="/_/user/profile"><i class="icon md-edit"></i></small></a>
                    </h4>
                
                        <div class="card-block p-0">
                        <?php foreach ($parent->getPhoneNumbers() as $phone) : ?>
                            <p data-info-type="phone" class="mb-10 text-nowrap">
                                <i class="icon md-phone mr-10"></i>
                                <span class="text-break"><?= $phone ?>
                                <span>
                            </p>
                            <?php endforeach; ?>
                        <p data-info-type="email" class="mb-10 text-nowrap">
                            <i class="icon md-email mr-10"></i>
                            <span class="text-break"><a ><?= $parent->getEmail() ?></a>
                            <span>
                        </p>
                        <p data-info-type="address" class="mb-10 text-nowrap">
                            <i class="icon md-pin mr-10"></i>
                            <?php if ($address = $parent->getAddress()) : ?>
                            <span class="text-break"><?= $address->getStreet() . " " . ($address->getStreet2() ? $address->getStreet2() . " " : '') . $address->getCity() . ', ' . $address->getState() . ' ' . $address->getZip() ?>
                            <span>
                            <?php endif ?>
                        </p>
                        </div>
                </div>
                <div class="card p-20">
                    <div class="row">
                        <div class="col-sm-6 text-center">
                            <a  href="/forms/reimbursement">
                            <div class="hexagon">
                                <i class="icon md-label"></i>
                            </div>
                            </a>
                            <span>Request Reimbursement</span>
                        </div>
                        <div class="col-sm-6 text-center">
                            <a href="/forms/test-opt-out">
                            <div class="hexagon">
                                <i class="icon md-book"></i>
                            </div>
                            </a>
                            <span>State Testing Opt-Out</span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6 text-center">
                            <a href="https://www.mytechhigh.com/consultation/" target="_blank">
                            <div class="hexagon">
                                <i class="icon md-help"></i>
                            </div>
                            </a>
                            <span>Request a 1-1 Consultation</span>
                        </div>
                        <div class="col-sm-6 text-center">
                            <a href="https://mytechhigh.instructure.com/grades" target="_blank">
                            <div class="hexagon">
                                <i class="icon md-assignment"></i>
                            </div>
                            </a>
                            <span>Check Student Grades</span>
                        </div>
                    </div>
        
                    <br>
                    <div class="panel container-collapse mb-0 panel-primary panel-line">
                        <div class="panel-heading" id="exampleHeadingDefaultOne" role="tab">
                            <a class="panel-title collapsed p-10" style="font-size:14px" data-toggle="collapse" href="#other-actions" data-parent="#other-actions" aria-expanded="false" aria-controls="other-actions">
                                <i class="panel-action icon md-chevron-right icon-collapse" ></i> Other Actions
                            </a>
                        </div>
                        <div class="panel-collapse collapse info-collapse" id="other-actions" aria-labelledby="other-actions" role="tabpanel" style="">
                            <div class="panel-body p-0">
                                <div class="list-group">
                                    <a class="list-group-item" href="https://www.mytechhigh.com/consultation/" target="_blank">
                                        <i class="icon md-assignment-account" aria-hidden="true"></i>Request a Transcript
                                    </a>
                                    <a class="list-group-item" href="https://www.mytechhigh.com/consultation/" target="_blank">
                                        <i class="icon md-assignment-returned" aria-hidden="true"></i>Request to Withdraw
                                    </a>
                                    <a class="list-group-item" href="https://www.mytechhigh.com/consultation/" target="_blank">
                                        <i class="icon md-assignment-alert" aria-hidden="true"></i> Request to begin the Special Ed referral process
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Change Student Email Modal -->
<div class="modal fade" id="changeEmail" tabindex="-1" role="dialog" aria-labelledby="changeEmail" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="changeEmailForm" method="GET">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changeEmailTitle">Email</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
        
                <div class="form-group">
                    <input type="email" class="form-control" id="updateEmail" name="updateEmail" required/>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-round" data-dismiss="modal">Cancel</button>
                <button type="submit" id="update" class="btn btn-primary btn-round" data-loading-text="Updating.." autocomplete="off">Update</button>
            </div>
        </div>
        </form>
    </div>
</div>
<!-- End Change Student Email Modal -->
<?php
core_loader::includejQueryValidate();
core_loader::printFooter('student');
?>
<script type="text/javascript">
    $(function(){
        updateSlug = '';

        $('#changeEmail').on('show.bs.modal', function (e) {
            var $btn = $(e.relatedTarget);
            updateSlug  = $btn.data('slug');
            updateEmail = $btn.data('email');
            $('#updateEmail').val(updateEmail);
        });

        $('#changeEmailForm').validate({
            rules: {
                updateEmail: {required:true,email:true}
            }
        });

        $('#update').click(function(){
            var newEmail = $('#updateEmail').val();
            swal({
                title: "Are you sure?",
                text: "You want to set the student\'s email to "+newEmail,
                type: "warning",
                showCancelButton: !0,
                confirmButtonClass: "btn-warning",
                confirmButtonText: "Yes",
                cancelButtonText: "Cancel",
                closeOnConfirm: !1,
                closeOnCancel: true
            },
            function () {
                location = '/student/'+updateSlug+'?updateEmail=' + encodeURIComponent(newEmail);
            });
            return false;
        });

    });
</script>
