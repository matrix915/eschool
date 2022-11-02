<?php

core_loader::addCssRef('immunizationsCSS', core_path::getPath() . '/main.css');

cms_page::setPageTitle('Immunization Management');
cms_page::setPageContent('');
core_loader::printHeader('admin');
?>
    <div class="row higlight-links">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Immunizations List</h3>
                </div>
                <div class="card-block p-0">
                    <table class="table responsive higlight-links">
                        <?php foreach (mth_immunization_settings::getEach() as $immunization): ?>
                            <tr class="mth_immunization-row" id="mth_immunization-<?= $immunization->getID() ?>">
                                <td>
                                    <a title="Show Immunization" class="mth_immunization_toggle mth_immunization" onclick="showHideImmunization('<?= $immunization->getID() ?>')">
                                        <b><?= $immunization->getTitle() ?></b>
                                    </a>
                                </td>
                                <td style="text-align: right;">
                                    <a onclick="editImmunization(<?= $immunization->getID() ?>)"><i class="fa fa-gear"></i></a>
                                </td>
                            </tr>
                            <tr class="mth_immunization-details" id="mth_immunization-tr-<?= $immunization->getID() ?>"
                                style="display:none;">
                                <td colspan="2">
                                    <div class="list-group">
                                        <a class="list-group-item">
                                            <div class="row">
                                                <div class="col-md-3"><b>Minimum Grade Level</b></div>
                                                <div class="col-md-9"><?= mth_student::gradeLevelFullLabel($immunization->getMinGradeLevel()) ?></div>
                                            </div>
                                        </a>
                                        <a class="list-group-item">
                                            <div class="row">
                                                <div class="col-md-3"><b>Maximum Grade Level</b></div>
                                                <div class="col-md-9"><?= mth_student::gradeLevelFullLabel($immunization->getMaxGradeLevel()) ?></div>
                                            </div>
                                        </a>
                                        <a class="list-group-item">
                                            <div class="row">
                                                <div class="col-md-3"><b>Minimum School Year Required</b></div>
                                                <div class="col-md-9">No Minimum</div>
                                            </div>
                                        </a>
                                        <a class="list-group-item">
                                            <div class="row">
                                                <div class="col-md-3"><b>Maximum School Year Required</b></div>
                                                <div class="col-md-9">No Maximum</div>
                                            </div>
                                        </a>
                                        <a class="list-group-item">
                                            <div class="row">
                                                <div class="col-md-3"><b>Immunity Allowed</b></div>
                                                <div class="col-md-9"><?= $immunization->isImmunityAllowed() ? 'Yes' : 'No' ?></div>
                                            </div>
                                        </a>
                                        <a class="list-group-item">
                                            <div class="row">
                                                <div class="col-md-3"><b>Require Update if Exempt</b></div>
                                                <div class="col-md-9"><?= ($immunization->getLevelExemptUpdate() !=NULL) ? 'Yes - Level - '.implode(',',$immunization->getLevelExemptUpdate()) : 'No' ?></div>
                                            </div>
                                        </a>
                                        <a class="list-group-item">
                                            <div class="row">
                                                <div class="col-md-3"><b>Consecutive Vaccine</b></div>
                                                <div class="col-md-9"><?= ($settings = mth_immunization_settings::getByID($immunization->getConsecutiveVaccine())) ?  $settings->getTitle() : 'No' ?></div>
                                            </div>
                                        </a>
                                        <a class="list-group-item">
                                            <div class="row">
                                                <div class="col-md-3"><b>Minimum Spacing</b></div>
                                                <div class="col-md-9"><?= $immunization->timeLabel($immunization->getMinSpacingDate()) ? $immunization->getMinSpacingInterval() .' / '. $immunization->timeLabel($immunization->getMinSpacingDate()) : 'None' ?></div>
                                            </div>
                                        </a>
                                        <a class="list-group-item">
                                            <div class="row">
                                                <div class="col-md-3"><b>Maximum Spacing</b></div>
                                                <div class="col-md-9"><?= $immunization->timeLabel($immunization->getMaxSpacingDate()) ? $immunization->getMaxSpacingInterval() .' / '. $immunization->timeLabel($immunization->getMaxSpacingDate()) : 'None' ?></div>
                                            </div>
                                        </a>
                                        <a class="list-group-item">
                                            <div class="row">
                                                <div class="col-md-3"><b>Standard Response for Email Update</b></div>
                                                <div class="col-md-9"><?= $immunization->getEmailUpdateTemplate() ?></div>
                                            </div>
                                        </a>
                                        <a class="list-group-item">
                                            <div class="row">
                                                <div class="col-md-3"><b>Note/Tooltip</b></div>
                                                <div class="col-md-9"><?= $immunization->getTooltip() ?></div>
                                            </div>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <div class="card-footer">
                    <a onclick="editImmunization(0)"><i>New Immunization</i></a>
                </div>
            </div>
        </div>
    </div>
<?php
core_loader::addJsRef('immunizationsJS', core_path::getPath() . '/main.js');
core_loader::printFooter('admin');