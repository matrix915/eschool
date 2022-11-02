<?php
use function GuzzleHttp\json_encode;
use mth\yoda\assessment;
use mth\yoda\questions;

core_user::getUserLevel() || core_secure::loadLogin();

if (req_get::bool('checklist')) {
    echo mth_views_learninglog::checklistTemp(req_get::int('row'), req_get::txt('value'));
    exit;
}

if (req_get::bool('question')) {
    $isChecklist = req_get::int('ischecklist') == 1;
    $data = $_GET['data'];
    $row = req_get::int('row');
    echo mth_views_learninglog::questionTemp($data, $isChecklist, $row);
    exit;
}

function apendQuestions($assessment)
{
    $imported_questions = (($course = $assessment->getCourse()) && ($plgs = $course->getImportedPLGs())) ? $plgs : [];
    $start_order = isset($_POST['questions']) ? count($_POST['questions']) : 0;
    foreach ($imported_questions as $order => $question) {
        $_question = new questions();
        $_question->setInsert('data', $question->getData())
            ->setInsert('yoda_teacher_asses_id', $assessment->getID())
            ->setInsert('type', $question->getType())
            ->setInsert('number', $start_order + ($order + 1))
            ->setInsert('plg_subject', $question->getSubject())
            ->save();
    }
}
function saveLearningLog($assessment)
{

    $question_ids = [];
    if (!empty($_POST['questions'])) {
        foreach ($_POST['questions'] as $order => $question) {
            $new_question = $question['id'] == 0;
            //a checklist without items added on Front End will be consider as text type
            $is_checklist = isset($question['list']);
            $data = null;
            $_question = null;

            if ($is_checklist) {
                $checklist = [];
                foreach ($question['list'] as $list) {
                    $checklist[] = ['list' => req_sanitize::txt($list)];
                }

                $data = json_encode([
                    'title' => req_sanitize::txt($question['title']),
                    'checklist' => $checklist,
                ]);
            } else {
                $data = isset($question['data']) ? req_sanitize::txt($question['data']) : "";
            }
            
          //   $data =req_sanitize::txt($question['title']);

            if (!$new_question && ($_question = questions::getById($question['id']))) {
                $_question
                    ->set('data', $data)
                    ->set('number', $order + 1)
                    ->set('is_required', (int) isset($question['required']))
                    ->save();
            } else {
                $_question = new questions();
                $_question->setInsert('data', $data)
                    ->setInsert('yoda_teacher_asses_id', $assessment->getID())
                    ->setInsert('type', $is_checklist ? questions::CHECKLIST : questions::TEXT)
                    ->setInsert('number', $order + 1)
                    ->setInsert('is_required', (int) isset($question['required']))
                    ->save();
            }

            if ($_question && !in_array($_question->getID(), $question_ids)) {
                $question_ids[] = $_question->getID();
            }
        }
    }

    if (!empty($question_ids) && !questions::deleteExcept($question_ids, $assessment->getID())) {
        core_notify::addError('Error Removing some of the questions');
    }
}

if (!empty($_GET['form'])) {
    core_loader::formSubmitable($_GET['form']) || die('Form is not submittable');
    $is_new = req_post::int('id') == 0;
    $assessment = $is_new ? (new assessment()) : assessment::getById(req_post::int('id'));
    $deadline = date('Y-m-d H:i:s', strtotime(req_post::txt('deadline_date') . ' ' . req_post::txt('deadline_time')));
    if ($assessment) {
        if ($is_new) {
            if ($assessment
                ->setInsert('title', req_post::txt('title'))
                ->setInsert('data', json_encode(['details' => $_POST['details']]))
                ->setInsert('type', assessment::LLOG)
                ->setInsert('deadline', $deadline)
                ->setInsert('created_by_user_id', core_user::getCurrentUser()->getID())
                ->setInsert('course_id', req_get::int('course'))
                ->save()) {
                saveLearningLog($assessment);
                apendQuestions($assessment);
                core_notify::addMessage('Learning Log added.');
            } else {
                core_notify::addError('Error adding changes.');
            }
        } else {
            $LearningLogTitle = req_post::txt('title');
          //   $NewLearningLogTitle = '';

          //   if (str_contains($LearningLogTitle, '&')) {

          //       $arrLearningLog =  explode("&", $LearningLogTitle);

          //       foreach ($arrLearningLog as &$value) {
          //           $NewLearningLogTitle = $NewLearningLogTitle.$value."\&";
          //       }
          //   }

            // $LearningLogTitle = "test title";  test_harry 4-3-0 ?:"{}| & and - plus & here !@#$%^&*()333

            if ($assessment
                ->set('title', $LearningLogTitle)
                ->set('data', json_encode(['details' => $_POST['details']]))
                ->set('deadline', $deadline)
                ->save()) {
                saveLearningLog($assessment);
                core_notify::addMessage('Learning Log changes saved.');
            } else {
                core_notify::addError('Error saving changes.');
            }
        }
    } else {
        core_notify::addError('There is an error saving this Learning Log.');
    }
    if (!$is_new) {
        core_loader::redirect('?log=' . req_post::int('id'));
    } else {
        exit('<!DOCTYPE html><html><script>
          parent.location.reload();
         </script></html>');
    }
}

$assessment = null;
$NEW = !req_get::bool('log');
if (!$NEW && !($assessment = assessment::getById(req_get::int('log')))) {
    die('Assessment not found');
} elseif ($NEW) {
    $assessment = new assessment();
}

core_loader::isPopUp();
core_loader::addCssRef('timecss', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.10.0/jquery.timepicker.min.css');
core_loader::printHeader();
core_loader::includejQueryUI();
?>
<div class="log-header">
     <span class="float-right">
     <button type="button" class="btn btn-round btn-primary" id="save-log">
          <i class="fa fa-check"></i> Save
     </button>
     <button type="button" class="btn btn-round btn-default" onclick="closeLog()" title="Close">
          <i class="fa fa-close"></i>
     </button>
     </span>
     <h4><span style="color:#2196f3"><?=$NEW ? 'Add' : 'Edit'?> Learning Log</h4>
</div>
<form name="logsform" id="logform" method="post" action='?form=<?=uniqid('yoda-edit-learning-log') . ($NEW ? ('&course=' . req_get::int('course')) : ('&log=' . req_get::int('log')))?>'>
     <div class="row" style="margin-top: 60px;">
     <?php mth_views_learninglog::getView($assessment, true)?>
     </div>
</form>
<?php
core_loader::addJsRef('momentjs', core_config::getThemeURI() . '/vendor/calendar/moment.min.js');
core_loader::addJsRef('calendarjs', 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/js/bootstrap-datetimepicker.min.js');
core_loader::addJsRef('ckeditorcdn', "//cdn.ckeditor.com/4.10.1/full/ckeditor.js");
core_loader::addJsRef('timepickercdn', "https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.10.0/jquery.timepicker.min.js");
core_loader::includejQueryValidate();
core_loader::addJsRef('learninglogjs', core_config::getThemeURI() . '/assets/js/LearningLog.js');
core_loader::printFooter();
?>
<script>
     function closeLog() {
          parent.global_popup_iframe_close('yoda_assessment_edit');
          if(parent.updateHomeroomList!=undefined){
               parent.updateHomeroomList();
          }
     }
     $(function(){
          $('#logform').validate();
     });
</script>