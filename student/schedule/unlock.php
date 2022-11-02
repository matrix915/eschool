<?php

($schedule = mth_schedule::getByID($_GET['schedule'])) || die('Schedule not found');
($student = $schedule->student()) || die('Schedule student missing');
($parent = $student->getParent()) || die('Student\'s parent missing');

if (!empty($_GET['form'])) {
    core_loader::formSubmitable($_GET['form']) || die('Form is not submittable');

    if ($schedule->unlockPeriods($_POST['mth_schedule_period'])) {
        core_notify::addMessage('Schedule Unlocked');
    }else{
        core_notify::addError('Unable to set schedule for changes.');
    }
    exit('<html><script>
        if(parent.updateSchedule){
        parent.updateSchedule(' . $schedule->id() . '); 
        parent.global_popup_iframe_close("mth_schedule-edit-'.$schedule->id().'");
        }else{
        parent.location.reload(true);
        }
        </script></html>');
}

if (req_get::bool('secondSem')) {
    if (($oldSchedulePeriod = mth_schedule_period::get($schedule, mth_period::get(req_get::int('secondSem')), true))
        && !$oldSchedulePeriod->second_semester()
    ) {
        $oldSchedulePeriod->duplicateTo2ndSem(true);
    }
    core_loader::redirect('?schedule=' . $schedule->id());
}



core_loader::isPopUp();
core_loader::printHeader();
?>
    <script>
        $(function () {
            $('#mth_schedule-change-form').submit(function () {
                if ($('input.mth_schedule_periods-to_change:checked').length === 0) {
                    swal('','You need to select at least one period to change.','warning');
                    return false;
                }
                return true;
            });
        });
    </script>
    <form action="?form=<?= uniqid('mth_schedule-change-form-') ?>&schedule=<?= $schedule->id() ?>"
          method="post" id="mth_schedule-change-form">
        <fieldset>
            <legend>Request schedule unlock
                <small>to the following periods:</small>
            </legend>
            <?php while ($schedulPeriod = $schedule->eachPeriod()): ?>
                <?php
                if ($schedulPeriod->period_number()==1 || $schedulPeriod->hasDefault()) {
                    continue;
                }
                ?>
                <div class="checkbox-custom checkbox-primary">
                    <input type="checkbox" name="mth_schedule_period[<?= $schedulPeriod->id() ?>]"
                           value="<?= $schedulPeriod->id() ?>"
                           data-label="<?= $schedulPeriod->period() ?> <?= $schedulPeriod->second_semester() ? '(2nd Semester)' : '' ?>"
                           id="mth_schedule_period-<?= $schedulPeriod->id() ?>" class="mth_schedule_periods-to_change">
                    <label for="mth_schedule_period-<?= $schedulPeriod->id() ?>" >
                    <?= $schedulPeriod->period() ?>
                    <?= $schedulPeriod->second_semester() ? '(2nd Semester)' : '' ?>
                    <br>
                    <small style="display: inline"><?= $schedulPeriod ?></small>
                    <?php if (!$student->isMidYear($schedule->schoolYear()) && $schedulPeriod->second_sem_change_available() && !$schedulPeriod->second_semester()
                        && (!($secondSemPeriod = mth_schedule_period::get($schedule, $schedulPeriod->period(), true))
                            || !$secondSemPeriod->second_semester())
                    ): ?>
                        <br><a href="?schedule=<?= $schedule->id() ?>&secondSem=<?= $schedulPeriod->period()->num() ?>">Enable
                            2nd Sem. Change</a>
                    <?php endif; ?>
                    </label>
                </div>
                
            <?php endwhile; ?>
        </fieldset>
        
        <br>
        <p>
            <button  type="submit" class="btn btn-round btn-primary">Submit</button>
            <button  type="button" class="btn btn-round btn-secondary" onclick="top.global_popup_iframe_close('mth_schedule-change')">Cancel</button>
        </p>
    </form>
<?php
core_loader::printFooter();

//.replace(/<!--START-->((\n|\r|.)*)<!--END-->/,"<!--START-->$1\n<p>another test</p>\n<!--END-->")
//