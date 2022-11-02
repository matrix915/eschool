<?php

$schoolYear = &$_SESSION['mth_reports_school_year'];

core_loader::isPopUp();
core_loader::printHeader();
?>
    <style media="print">
        input {
            display: none;
        }
    </style>
    <div class="iframe-actions">
        <button  type="button" class="btn btn-secondary btn-round"  onclick="print()">Print</button>
        <button  type="button" class="btn btn-secondary btn-round"  onclick="top.global_popup_iframe_close('reportPopup')">Close</button>
    </div>
    
    <h1 style="margin-top: 0">Statistics Report <?= $schoolYear ?></h1>
    <div class="card">
        <div class="card-block">
            <div class="row">
                <div class="col p-0">
                    <?php $gradeAgeReport = mth_student_report::getGradeAge($schoolYear) ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th><?= $gradeAgeReport->label_heading ?></th>
                                <th><?= $gradeAgeReport->extra1_heading ?></th>
                                <th><?= $gradeAgeReport->total_heading ?></th>
                                <th><?= $gradeAgeReport->percent_heading ?></th>
                            </tr>
                        </thead>
                        <tbody>
                    <?php while ($item = $gradeAgeReport->eachItem()): ?>
                        <tr>
                            <td><?= $item->label ?></td>
                            <td><?= $item->extra1 ?></td>
                            <td><?= $item->total ?></td>
                            <td><?= $item->percent ?></td>
                        </tr>
                    <?php endwhile; ?>
                            <td class="text-right">Average:</td>
                            <td class="bold-text"><?= $gradeAgeReport->end_extra1 ?></td>
                            <td class="bold-text"><?= $gradeAgeReport->end_total ?></td>
                            <td></td>
                        </tbody>
                    </table>
                    <div style="margin-bottom: 20px;"></div>
                    <?php mth_student_report::printReport(mth_student_report::getGender($schoolYear)) ?>
                   
                </div>
                <div class="col">
                    <?php mth_student_report::printReport(mth_student_report::getDistrictOfResidence($schoolYear)) ?>
                </div>
            </div>
            <div class="row">
                <div class="col">
                   <?php mth_student_report::printReport(mth_student_report::getDiplomaSeekingByGrade($schoolYear)) ?>
                </div>
                <div class="col">
                    <?php mth_student_report::printReport(mth_student_report::getGPAbyGrade($schoolYear)) ?>
                </div>
            </div>
            <div class="row">
                <div class="col">
                <?php mth_student_report::printReport(mth_student_report::getSPEDbyGrade($schoolYear)) ?>
                </div>
                <div class="col">
                    <?php mth_student_report::printReport(mth_student_report::getESchoolByGrade($schoolYear)) ?>
                </div>
            </div>
        </div>
    </div>
<?php
core_loader::printFooter();
