<?php
/**
 * Created by PhpStorm.
 * User: abe
 * Date: 7/11/16
 * Time: 6:05 PM
 */
ini_set('max_execution_time', 90000);
use Dompdf\Dompdf;
use Dompdf\Options;

$available_PDFs = array('1' => 'USIR','2' => 'USIR(New)');

function clean($str)
{
    return htmlentities($str);
}

function field_clean($str)
{
    return preg_replace('/[^a-z0-9]+/', '', strtolower($str));
}

if (req_post::bool('pdf')) {

    define('OUTOUT_DIR', ROOT . '/_/mth_files/pdf-tool/' . date('Y-m-d-H-i-s/'));
    mkdir(OUTOUT_DIR, 0777, true);
    chmod(OUTOUT_DIR, 0777);

    define('REG_EXP', '/\[CONTENT\.([^\]?:=]+)(=([^?]+)\?([^:]*):([^\]]*))?\]/');
    define('REG_EXP_DATE', '/\[DATE\.([^\]]+)\]/');
    define('FIELD_NAME', 1);
    define('FULL_PATTERN', 0);
    define('HAS_LOGIC', 2);
    define('LOGIC_TEST_VALUE', 3);
    define('LOGIC_TRUE_VALUE', 4);
    define('LOGIC_FALSE_VALUE', 5);

    if (!$_FILES['csv_file']['size'] > 0
        || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK
        || !($handle = fopen($_FILES['csv_file']['tmp_name'], 'r'))
    ) {
        core_notify::addError('Unable to read CSV file.');
        core_loader::redirect();
    }
    /** @noinspection PhpUndefinedVariableInspection */
    $columns = array_map('field_clean', fgetcsv($handle));

    $dir = __DIR__ . '/' . req_post::int('pdf') . '/';

    $siteURL = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '&#58;//' . $_SERVER['HTTP_HOST'] . '/';
    $content = file_get_contents($dir . 'content.html');

    $search = array('[URL]', '[PATH]');
    $replace = array(
        $siteURL . core_path::getPath()->getString() . '/' . req_post::int('pdf') . '/',
        'https://infocenter.mytechhigh.com/_/admin/pdf-creator/2/');

    $date_matches = array();
    preg_match_all(REG_EXP_DATE, $content, $date_matches);
    $time = time();
    foreach ($date_matches[0] as $key => $full_pattern) {
        $search[] = $full_pattern;
        $replace[] = date($date_matches[1][$key], $time);
    }

    // must replace [URL] and [DATE...] patterns before [CONTENT...]
    $content = str_replace($search, $replace, $content);

    $matches = array();
    preg_match_all(REG_EXP, $content, $matches);
    $matches[FIELD_NAME] = array_map('field_clean', $matches[FIELD_NAME]);

    $file_name = file_get_contents($dir . 'file_name.txt');
    $count = 0;

    while (($values = fgetcsv($handle)) !== FALSE) {
        $search = array();
        $replace = array();
        $row = array_combine($columns, $values);

        foreach ($matches[FIELD_NAME] as $key => $field_name) {
            if (!isset($row[$field_name])) {
                continue;
            }

            $search[] = $matches[FULL_PATTERN][$key];
            if ($matches[HAS_LOGIC][$key]) {
                if (strpos($matches[LOGIC_TEST_VALUE][$key], '%') !== false) {
                    $pattern = field_clean($matches[LOGIC_TEST_VALUE][$key]);
                    if ($matches[LOGIC_TEST_VALUE][$key][0] == '%') {
                        $pattern = '/.*' . $pattern;
                    } else {
                        $pattern = '/' . $pattern;
                    }
                    if (substr($matches[LOGIC_TEST_VALUE][$key], -1) == '%') {
                        $pattern = $pattern . '.*/';
                    } else {
                        $pattern = $pattern . '/';
                    }
                    $replace[] = preg_match($pattern, field_clean($row[$field_name]))
                        ? $matches[LOGIC_TRUE_VALUE][$key]
                        : $matches[LOGIC_FALSE_VALUE][$key];
                } else {
                    $replace[] = field_clean($row[$field_name]) == field_clean($matches[LOGIC_TEST_VALUE][$key])
                        ? $matches[LOGIC_TRUE_VALUE][$key]
                        : $matches[LOGIC_FALSE_VALUE][$key];
                }
            } else {
                $replace[] = clean($row[$field_name]);
            }
        }

        $option = new Options();
        $option->setIsRemoteEnabled(true);

        $dompdf = new Dompdf();
        $dompdf->setOptions($option);
        $dompdf->setPaper('letter', 'landscape');
        $dompdf->loadHtml(str_replace($search, $replace, $content));
        $dompdf->render();
        //error_log(str_replace($search,$replace,$file_name));
        //error_log(preg_replace('/[^a-zA-Z0-9_\-.]+/','-',str_replace($search,$replace,$file_name)));
        file_put_contents(
            OUTOUT_DIR . preg_replace('/[^a-zA-Z0-9_\-.]+/', '-', str_replace($search, $replace, $file_name)),
            $dompdf->output());
        $count += 1;
    }

    core_notify::addMessage('Created ' . $count . ' PDFs');
    core_loader::redirect();
}

cms_page::setPageTitle('PDF tool');
core_loader::printHeader();
?>

    <form method="post" enctype="multipart/form-data">
        <p>Images need to be absolute paths from computer root</p>
        <p>
            <select name="pdf">
                <option value="">Select one...</option>
                <?php foreach ($available_PDFs as $key => $label) { ?>
                    <option value="<?= $key ?>"><?= $label ?></option>
                <?php } ?>
            </select>
        </p>
        <p>
            <label for="csv_file">CSV file</label>
            <input type="file" name="csv_file" id="csv_file">
        </p>
        <p>
            <button type="submit">Submit</button>
        </p>

    </form>

<?php
core_loader::printFooter();
