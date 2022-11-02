<?php
/* @var $parent mth_parent */
use mth\packet\LivesWithEnum;
use mth\packet\LivingLocationEnum;

/* @var $student mth_student */
/* @var $packet mth_packet */
/* @var $packetURI */
/* @var $packetStep */
/* @var $packetRunValidation */

if (!empty($_GET['packetForm2'])) {
    core_loader::formSubmitable('packetForm2-' . $_GET['packetForm2']) || die();

    $student->setDateOfBirth(strtotime($_POST['dob']));
    $packet->setBirthPlace(req_post::txt('birth_place'));
    $packet->setBirthCountry(req_post::txt('birth_country'));
    $student->setGender($_POST['gender']);
    $packet->setHispanic($_POST['hispanic']);

    if (($key = array_search('other', $_POST['race']))) {
        $_POST['race'][$key] = $_POST['raceOther'];
    }
    $packet->setRace($_POST['race']);
    $packet->setLanguage($_POST['language']);
    $packet->setLanguageAtHome($_POST['language_home']);
    $packet->setLanguageHomeChild(req_post::txt('language_home_child'));
    $packet->setLanguageFriends(req_post::txt('language_friends'));
    $packet->setLanguageHomePreferred(req_post::txt('language_home_preferred'));

    $packet->setHouseholdSize($_POST['household_size']);
    $packet->setHouseholdIncome($_POST['household_income']);

    $packet->setWorkedInAgriculture(req_post::bool('worked_in_agriculture'));
    $packet->setMilitary(req_post::bool('military'));
    $packet->setMilitaryBranch(req_post::txt('military_branch'));

    $packet->setWorkMove(req_post::bool('work_move'));

    $packet->setLivingLocation(req_post::int('living_location'));
    $packet->setLivesWith(req_post::is_set('lives_with')?req_post::int('lives_with'):null);

    $packetStep = 3;

  if(levelTwoComplete($packet, $student)) {
    header('location: ' . $packetURI . '/3');
    exit();
  }
  header('location: ' . $packetURI);
  exit();
}
core_loader::printHeader('student');
core_loader::includejQueryUI();

 /** @noinspection PhpIncludeInspection */

?>
<div class="page">
    <?= core_loader::printBreadCrumb('window');?>
    <div class="page-content container-fluid">   
        <form action="?packetForm2=<?= uniqid() ?>" id="packetForm2" method="post">
            <div class="card">
                <?php include core_config::getSitePath() . '/student/packet/header.php'; ?>
                <div class="card-block">
                    <div class="row">
                        <div class="col-md-6">
                            <h3><?= $student->getPreferredFirstName() ?>'s Personal Information</h3>
                            <div class="form-group">
                                <label for="dob">Date of Birth</label>
                                <input id="dob" name="dob" value="<?= $student->getDateOfBirth('m/d/Y') ?>" type="text" required class="form-control">
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="form-group">
                                        <label for="birth_place">Birthplace (City, State)</small>
                                        </label>
                                        <input id="birth_place" name="birth_place" type="text" required
                                            value="<?= $packet->getBirthPlace() ?>" class="form-control">
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-group">
                                        <label for="birth_country">
                                        Country
                                        </label>
                                        <select name="birth_country" id="birth_country" required class="form-control">
                                            <option></option>
                                            <?php foreach (mth_packet::getAvailableCountries() as $county_code => $county_name): ?>
                                                <option
                                                    value="<?= $county_code ?>" <?= $packet->getBirthCountry() == $county_code ? 'selected' : '' ?>>
                                                    <?= $county_name ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select name="gender" id="gender" required class="form-control">
                                    <option></option>
                                    <option <?= $student->getGender() === mth_person::GEN_FEMALE ? 'selected' : '' ?>><?= mth_person::GEN_FEMALE ?></option>
                                    <option <?= $student->getGender() === mth_person::GEN_MALE ? 'selected' : '' ?>><?= mth_person::GEN_MALE ?></option>
                                </select>
                            </div>
                           
                            <div class="form-group">
                                <label for="hispanic">Hispanic/Latino <a onclick="show_hispanic()">(?)</a></label>
                                <select name="hispanic" id="hispanic" required class="form-control" style="width:90px;">
                                    <option></option>
                                    <option <?= $packet->isHispanic() ? 'selected' : '' ?> value="1">Yes</option>
                                    <option <?= $packet->isHispanic() === false ? 'selected' : '' ?> value="0">No</option>
                                </select>
                            </div>
                            <p>
                                <label for="race">Race
                                    <small style="display: inline">(check all that apply)</small>
                                </label>
                                <?php $studentRace = $packet->getRace(FALSE); ?>
                                <?php foreach (mth_packet::getAvailableRace() as $raceID => $race): ?>
                                    <div class="checkbox-custom checkbox-primary">
                                        <input type="checkbox" name="race[]" id="race-<?= $raceID ?>" value="<?= $raceID ?>" <?= in_array($raceID, $studentRace) ? 'checked' : '' ?>
                                                class="race-group">
                                        <label for="race-<?= $raceID ?>"><?= $race ?></label>
                                    </div>
                                <?php endforeach; ?>
                                <?php
                                $raceOther = trim(preg_replace('/[0-9]+/', '', implode('', $studentRace)));
                                ?>
                                <div class="checkbox-custom checkbox-primary">
                                    <input type="checkbox" name="race[]" id="race-other" <?= $raceOther ? 'checked' : '' ?> value="other">
                                    <label for="raceOtherCB">Other: <input type="text" name="raceOther" id="raceOther" value="<?= $raceOther ?>"
                                            style="max-width: 150px;" class="race-group form-control"></label>
                                        
                                    
                                </div>
                                <label for="race[]" class="error" style="display: none;"></label>
                            </p>
                            <?php

                            $language_options = [
                                'English'=>'English',
                                'Spanish'=>'Spanish',
                                'Other'=>'Other (Indicate)'
                            ];
                            $languageBlock = function($name,$label,$selected) use ($language_options){
                                $selected_other = '';
                                if($selected && !isset($language_options[$selected])){
                                    $selected_other = $selected;
                                    $selected = 'Other';
                                }elseif(!$selected){
                                    $selected = 'English';
                                }
                                ?>
                                <div class="form-group">
                                    <label for="<?=$name?>"><?=$label?></label>
                                    <div class="row">
                                        <div class="col">
                                            <select name="<?=$name?>" id="<?=$name?>" class="language_select form-control" required>
                                                <option></option>
                                                <?php
                                                foreach($language_options as $value=>$option_label){
                                                    echo '<option value="',$value,'" ',($selected==$value?'selected':''),'>
                                                        ',$option_label,'
                                                        </option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col">
                                            <input type="text" name="<?=$name?>" id="<?=$name?>-other"
                                            style="display: none; max-width: 150px" disabled
                                            class="form-control"
                                            value="<?=$selected_other?>" required title="Other Language">
                                        </div>
                                    </div>
                                </div>
                                <?php
                            };
                            $languageBlock('language','First language learned by child',$packet->getLanguage());
                            $languageBlock('language_home','Language used most often by adults in the home',$packet->getLanguageAtHome());
                            $languageBlock('language_home_child','Language used most often by child in the home',$packet->getLanguageHomeChild());
                            $languageBlock('language_friends','Language used most often by child with friends outside the home',$packet->getLanguageFriends());
                            $languageBlock('language_home_preferred','Preferred correspondence language for adults in the home',$packet->getLanguageHomePreferred());

                            ?>
                           
                        </div>
                        <div class="col-md-6">
                            <h3>Voluntary Income Information</h3>
                            <p>Schools are eligible for Title 1 funds based on enrollment and student demographics.
                                They appreciate your voluntary participation in providing income information to assist them in
                                meeting grant requirements.</p>
                            <div class="form-group">
                                <label for="household_size">Household Size</label>
                                <input type="number" name="household_size" id="household_size"
                                    value="<?= $packet->getHouseholdSize() ?>" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="household_income">Household Gross Monthly Income</label>
                                <select name="household_income" id="household_income" class="form-control">
                                    <?php foreach (mth_packet::getAvailableIncome() as $incomeID => $income): ?>
                                        <option
                                            value="<?= $incomeID ?>" <?= $packet->getHouseholdIncome(false) == $incomeID ? 'selected' : '' ?>>
                                            <?= $income ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <h3>Other</h3>
                            <div class="form-group">
                                <label for="worked_in_agriculture">Has the parent/guardian or spouse worked in Agriculture?</label>
                                <select name="worked_in_agriculture" id="worked_in_agriculture" required class="form-control">
                                    <option <?= $packet->getWorkedInAgriculture() ? 'selected' : '' ?> value="1">Yes</option>
                                    <option <?= !$packet->getWorkedInAgriculture() ? 'selected' : '' ?> value="0">No</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="military">Is a parent or legal guardian on active duty in the military?</label>
                                <select name="military" id="military" required class="form-control">
                                    <option <?= $packet->getMilitary() ? 'selected' : '' ?> value="1">Yes</option>
                                    <option <?= !$packet->getMilitary() ? 'selected' : '' ?> value="0">No</option>
                                </select>
                            </div>
                            <div class="form-group" id="military_branch_cont">
                                <label for="military_branch">Military Branch</label>
                                <input type="text" name="military_branch" id="military_branch" class="form-control"  value="<?=$packet->getMilitaryBranch()?>">
                            </div>
                            <hr>
                            <div class="p">
                                <div class="checkbox-custom checkbox-primary">
                                    <input type="checkbox" name="work_move"
                                            id="work_move" <?=$packet->getWorkMove()?'checked':''?>>
                                            <label  for="work_move">
                                    Check the box if your family has moved at some time in the past 3 years to look for work in:

                                </label>
                                </div>
                               
                                <ul>
                                    <li>Agriculture (farming, orchards, dairy)</li>
                                    <li>A Nursery (trees, flowers, gardening)</li>
                                    <li>Fishing</li>
                                </ul>
                            </div>
                            <hr>
                            <h3>Answer two questions related to<br> the McKinney-Vento Act:</h3>
                            <p>
                                <label for="living_location">
                                    1. Is the student presently living
                                </label>
                                <?php
                                foreach(LivingLocationEnum::getLabels() as $value=> $label){
                                    ?>
                                    <div class="radio-custom radio-primary">
                                        <input type="radio" name="living_location"
                                            value="<?=$value?>" id="living_location-<?=$value?>"
                                                <?=$value==$packet->getLivingLocation(false)?'checked':''?>>
                                        <label><?=$label?></label>
                                    </div>
                                    <?php
                                }
                                ?>
                                <label id="living_location-error" class="error" for="living_location"></label>
                            </p>
                            <div id="lives_with_p" style="display: none;">
                                <label for="lives_with">
                                    2. The student lives with
                                </label>
                                <?php
                                foreach(LivesWithEnum::getLabels() as $value=> $label){
                                    ?>
                                    <div class="radio-custom radio-primary">
                                        <input type="radio" name="lives_with" value="<?=$value?>" disabled <?=$value==$packet->getLivesWith(false)?'checked':''?> DISABLED> 
                                        <label>
                                            <?=$label?>
                                        </label>
                                    </div>
                                    <?php
                                }
                                ?>
                                <label id="lives_with-error" class="error" for="lives_with"></label>
                            </div>
                            
                        </div>
                    </div>
                </div>
                <div class="card-footer text-right">
                    <button type="submit" class="btn btn-primary btn-round btn-lg">Next &raquo</button>
                </div>
            </div>
        </form>
    </div>
</div>
    
<?php
core_loader::printFooter('student');
?>
<script> 
    function military_branch(){
        var ismilitary = $('#military').val() == 1;
        $military_branch =  $('#military_branch_cont');
        if(ismilitary){
            $military_branch.fadeIn();
        }else{
            $military_branch.fadeOut();
        }
    }
    military_branch();
    $('#military').change(function(){
        military_branch();
    });    

    $('#dob').datepicker({
        minDate: (new Date(<?=date('Y', strtotime('-20 years'))?>, 0, 1)),
        maxDate: (new Date(<?=date('Y', strtotime('-4 years'))?>, 11, 31)),
        changeMonth: true,
        changeYear: true,
        defaultDate: '-<?=$student->getGradeLevel() + 5?>y'
    });
    var packetForm2 = $('#packetForm2');
    packetForm2.validate({
        rules: {
            "race[]": {
                required: true
            },
            raceOther: {
                required: "#reace-other:checked"
            },
            "student[dob]": {
                date: true
            },
            household_size: {
                min: 0
            },
            living_location: {
                required: true
            },
            lives_with: {
                required: "#living_location-<?=LivingLocationEnum::None_of_the_above?>:not(:checked)"
            }
        },
        messages: {
            "race[]": 'Please select a race.',
            household_size: 'Cannot be a negative number'
        }
    });
    <?php  if($packetRunValidation): ?>
    if (!packetForm2.valid()) {
        packetForm2.submit(); //this will focus the cursor on the problem fields
    }
    <?php  endif; ?>
</script>
<script>
    function show_hispanic(){
        swal("Hispanic/Latino", "A person of Cuban, Mexican, Puerto Rican, South or Central American,or other Spanish culture or origin, regardless of race.", "info");
    }
</script>
 <script>
    var $language_select = $('.language_select');
    $language_select.change(function(){
        if(this.value=='Other'){
            $('#'+this.id+'-other').show().prop('disabled',false).focus();
        }else{
            $('#'+this.id+'-other').val('').hide().prop('disabled',true);
        }
    });
    $language_select.change();
</script>
<script>
    var $birth_country = $('#birth_country');
    if ($birth_country.val() === '') {
        $birth_country.val('US');
    }
</script>
<script>
    var $none = $('input[name="living_location"][value="<?=LivingLocationEnum::None_of_the_above?>"]');
    var livingLocationChanged = function(){
        if(this.timmer){
            clearTimeout(this.timmer);
        }
        this.timmer = setTimeout(function(){
            if($none.prop('checked')){
                $('#lives_with_p').hide().find('input').prop('disabled',true);
            }else{
                $('#lives_with_p').show().find('input').prop('disabled',false);
            }
        },200);
    };
    var fun = function(){
        setTimeout(function(){livingLocationChanged();},100);
    };
    $('input[name="living_location"]').focus(fun).change(fun).click(fun);
    livingLocationChanged();
</script>