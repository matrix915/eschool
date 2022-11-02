<?php

/**
 * Description of withdrawal
 *
 * @author abe
 */
class mth_views_forms_withdrawal extends core_form
{
    public $birthdate;
    public $reason;
    public $new_school_name;
    public $new_school_address;
    public $signature;

    private $_withdrawal;

    protected static $monthsForNextYear = array(3, 4, 5, 6);

    public function __construct(mth_student $student)
    {
        $this->_withdrawal = mth_withdrawal::getLatestNotifiedByStudent($student->getID());
        $this->set_field_type('birthdate', self::TYPE_DATE);
        $this->set_field_type('reason', self::TYPE_SELECT);
        $this->set_field_label('reason', 'How will your student continue their education?');
        $this->set_field_options('reason', array('' => '') + mth_withdrawal::reasons());
        $this->set_field_type('new_school_name', self::TYPE_TEXT);
        $this->set_field_label('new_school_name', 'New Public School Name <small>(if applicable)</small>');
        $this->set_field_type('new_school_address', self::TYPE_TEXTAREA);
        $this->set_field_label('new_school_address', 'New School Address <small>(if applicable)</small>');
        $this->set_field_type('signature', self::TYPE_RAW);
        parent::__construct(false);
        $this->handleSubmittion();
    }

    protected function handleSubmittion()
    {
        if (!$this->submitted()) {
            return;
        }

        $student = $this->_withdrawal->student();

        if (!$student->getDateOfBirth()) {
            if (!$student->setDateOfBirth(strtotime($this->birthdate))) {
                $this->submitted = false;
                return;
            }
        }
        $this->_withdrawal->set_reason($this->reason);
        $this->_withdrawal->set_new_school_name($this->new_school_name);
        $this->_withdrawal->set_new_school_address($this->new_school_address);
        $this->_withdrawal->save_sig_file($this->signature);
        if (($this->submitted = $student->saveChanges() && $this->_withdrawal->submit())) {
            $this->clear_saved();
        }
    }

    public function print_html($postToUrl, $submitButtonLabel = 'Submit')
    {
        core_loader::includejQueryUI();
        core_loader::includejQueryValidate();
        core_loader::addJsRef('jSignature', '/_/mth_includes/jSignature/libs/jSignature.min.js');
        core_loader::printJsCssRefs();
        ?>
        <form id="<?= $this->form_id() ?>" method="post" enctype="<?= $this->encoding ?>">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="core_form-field-label">Student Name</label>
                        <input type="text" readonly class="form-control" value="<?= $this->_withdrawal->student()->getName() ?>">
                    </div>
                    <label class="core_form-field-label">Birthdate</label>
                    <?php if ($this->_withdrawal->student()->getDateOfBirth()) : ?>
                        <div class="form-group">
                            <input type="text" readonly class="form-control" value="<?= $this->_withdrawal->student()->getDateOfBirth('m/d/Y') ?>">
                        </div>
                    <?php else : $this->print_html_field_block('birthdate', 'p');
                            endif; ?>
                    <div class="form-group">
                        <label class="core_form-field-label">Grade</label>
                        <input type="text" readonly class="form-control" value="<?= $this->_withdrawal->student()->getGradeLevelValue() ?>">
                    </div>
                    <div class="form-group">
                        <label class="core_form-field-label">Address</label>
                        <textarea readonly class="form-control"><?= $this->_withdrawal->student()->getAddress()->getFull(false) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="core_form-field-label">Phone</label>
                        <input type="text" readonly class="form-control" value="<?= $this->_withdrawal->student()->getParent()->getPhone() ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="core_form-field-label">Effective Withdrawal Date</label>
                        <input type="text" readonly class="form-control" value="<?= $this->_withdrawal->student()->getStatusDate($this->_withdrawal->school_year(), 'm/d/Y') ?>">
                    </div>
                    <?php
                            $this->print_html_field_block('reason', 'div');
                            $this->print_html_field_block('new_school_name', 'div');
                            $this->print_html_field_block('new_school_address', 'div');
                            ?>
                    <p>
                        I (parent/guardian) verify my intent to withdraw my student:
                    </p>
                    <div class="p">
                        <label>Signature
                            <small style="display: inline">(use the mouse to sign) -
                                <a onclick="mth_withdrawal_form.sigdiv.jSignature('reset')">Clear</a></small>
                        </label>
                        <div style="height: 0; overflow: hidden;"><input type="text" name="<?= $this->field_name('signature') ?>" id="<?= $this->field_name('signature') ?>" required>
                        </div>
                        <div id="<?= $this->field_name('signature') ?>-div" style="background: #fff"></div>
                        <label for="<?= $this->field_name('signature') ?>" class="error"></label>
                    </div>
                </div>
            </div>


            <p>
                <button onclick="mth_withdrawal_form.submit()" type="button" class="btn btn-round btn-primary btn-lg"><?= $submitButtonLabel ?></button>
            </p>
        </form>
        <!--[if lt IE 9]>
        <script type="text/javascript" src="/_/mth_includes/jSignature/libs/flashcanvas.min.js"></script>
        <![endif]-->
        <script>
            mth_withdrawal_form = {
                form: $('#<?= $this->form_id() ?>'),
                sigdiv: $("#<?= $this->field_name('signature') ?>-div"),
                init: function() {
                    mth_withdrawal_form.sigdiv.jSignature();
                    mth_withdrawal_form.form.validate({
                        rules: {
                            "<?= $this->field_name('reason') ?>": {
                                required: true
                            },
                            "<?= $this->field_name('birthdate') ?>": {
                                required: true
                            },
                            "<?= $this->field_name('new_school_name') ?>": {
                                required: {
                                    depends: mth_withdrawal_form.newSchoolRequired
                                }
                            },
                            "<?= $this->field_name('new_school_address') ?>": {
                                required: {
                                    depends: mth_withdrawal_form.newSchoolRequired
                                }
                            }
                        }
                    });
                },
                newSchoolRequired: function() {
                    var reasonVal = $("#<?= $this->field_name('reason') ?>").val();
                    return reasonVal === '<?= mth_withdrawal::REASON_TRANS_LOCAL ?>' ||
                        reasonVal === '<?= mth_withdrawal::REASON_TRANS_ONLINE ?>'
                },
                submit: function() {
                    if (mth_withdrawal_form.sigdiv.length > 0 && mth_withdrawal_form.sigdiv.jSignature('isModified')) {
                        $('#<?= $this->field_name('signature') ?>').val(mth_withdrawal_form.sigdiv.jSignature("getData", "svgbase64")[1]);
                    } else {
                        $('#<?= $this->field_name('signature') ?>').val('');
                    }
                    if (!mth_withdrawal_form.form.valid()) {
                        return;
                    }
                    var errorHandler = function() {
                        swal('', 'Unable to submit the form. Please refresh the page and try again.', 'error');
                        setTimeout(function() {
                            location.reload(true)
                        }, 2000);
                    };
                    global_waiting();
                    if (mth_withdrawal_form.submited) {
                        errorHandler();
                        return;
                    }
                    mth_withdrawal_form.submited = true;
                    $.ajax({
                        url: '<?= $this->format_post_url($postToUrl) ?>',
                        method: 'POST',
                        data: mth_withdrawal_form.form.serialize(),
                        success: function(data) {
                            if (data == '1') {
                                global_waiting_hide();
                                mth_withdrawal_form.form.html('<p>Thank you! Your form submission has been received.');
                            } else {
                                errorHandler();
                            }
                        },
                        error: errorHandler
                    })
                }
            };
            //$.validator.setDefaults({debug:true});
            $(function() {
                mth_withdrawal_form.init();
                <?php $grade_level = $this->_withdrawal->student()->getGradeLevelValue() == 'K' ? 0 : $this->_withdrawal->student()->getGradeLevelValue(); ?>
                var birthdateField = $('#<?= $this->field_name('birthdate') ?>');
                if (birthdateField.length) {
                    birthdateField.datepicker({
                        minDate: (new Date(<?= date('Y', strtotime('-20 years')) ?>, 0, 1)),
                        maxDate: (new Date(<?= date('Y', strtotime('-4 years')) ?>, 11, 31)),
                        changeMonth: true,
                        changeYear: true,
                        defaultDate: '-<?= $grade_level + 5 ?>y'
                    });
                }
            });
        </script><?php
                        }

                        /**
                         *
                         * @return mth_schoolYear
                         */
                        public static function year()
                        {
                            if (in_array(date('n'), self::$monthsForNextYear)) {
                                return mth_schoolYear::getNext();
                            }
                            return mth_schoolYear::getCurrent();
                        }
                    }
