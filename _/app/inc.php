<?php
define('ROOT', realpath(dirname(__FILE__) . '/../..'));

require_once ROOT . '/_/includes/sessions.php';
require_once ROOT . '/_/vendor/autoload.php';

// workaround for non-EB while the code is still in both EB and non-EB envs.
if (!is_dir('/etc/elasticbeanstalk')) {
  $dotenv = Dotenv\Dotenv::createImmutable(dirname(dirname(__DIR__)));
  $dotenv->load();
}

function app_autoload($class = '') {
  $file = ROOT . '/_/app/' . str_replace(['_', '\\'], '/', $class) . '.php';
  if (!is_file($file)) {
    return false;
  }
  /** @noinspection PhpIncludeInspection */
  require_once $file;
  return true;
}

spl_autoload_register('app_autoload');

require_once (core_config::isCli() ? ROOT : $_SERVER['DOCUMENT_ROOT']) . '/_/config/config.php';

$bugsnag = Bugsnag\Client::make('71b773dfb69005d1360ca98fbca21752');
Bugsnag\Handler::registerWithPrevious($bugsnag);
$bugsnag->setReleaseStage(core_config::getEnvironmentLabel());

if (core_config::isDevelopment()) {
  function debug()
  {
    $args = func_get_args();
    $message = '';
    foreach ($args as $arg) {
      if (is_callable($arg)) {
        $arg();
        continue;
      }
      if (is_array($arg) || is_object($arg)) {
        $arg = print_r($arg, true) . "\n";
      }
      $message .= $arg . ' ';
    }
    $message .= "\n\n";
    foreach (debug_backtrace() as $n => $backtrace) {
      $message .= $backtrace['file'] . '(' . $backtrace['line'] . ')  ';
    }
    error_log('DEBUG MESSAGE: ' . $message);
  }
} else {
  function debug()
  {
    return;
  }
}
