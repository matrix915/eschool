<?php
($year = $_SESSION['mth_reports_school_year']) || die('Year not set');

$file = 'Pending Applicants';
$reportArr = array(array(
    'Student Last',
    'Student First',
    'Grade',
    'SPED',
    'Diploma',
    'Accepted Siblings (' . $year . ')',
    'Parent Last',
    'Parent First',
    'Parent Email',
    'Parent Phone',
    'Year',
    'Submit Date'
));

foreach (mth_application::getSubmittedApplications() as $application) {
    if (!($student = $application->getStudent())
        || !($parent = $student->getParent())
    ) {
        continue;
    }
    $reportArr[] = array(
        $student->getLastName(),
        $student->getFirstName(),
        $student->getGradeLevelValue($application->getSchoolYearID()),
        $student->specialEd(true),
        ($student->diplomaSeeking() ? 'Yes' : 'No'),
        ($student->hasActiveSiblings($year) ? 'Yes' : 'No'),
        $parent->getPreferredLastName(),
        $parent->getPreferredFirstName(),
        $parent->getEmail(),
        $parent->getPhone(),
        $application->getSchoolYear(),
        $application->getDateSubmitted('m/d/Y')
    );
}

include ROOT . core_path::getPath('../report.php');