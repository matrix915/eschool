<?php
function custom_session_close() {
  return true;
}

function custom_session_die($id = '') {
  core_db::runQuery(sprintf("DELETE LOW_PRIORITY FROM sessions WHERE id='%s'", $id));
  return true;
}

function custom_session_gc($maxlifetime = 0) {
  return true;
}

function custom_session_open($path = '', $name = '') {
  return true;
}

function custom_session_read($id = '') {
  if ($data = core_db::runGetValue(sprintf("SELECT data FROM sessions WHERE id='%s'", $id))) {
    return $data;
  }
  return '';
}

function custom_session_write($id = '', $data = '') {
  $success = false;
  $data = core_db::escape($data);
  if (core_db::runQuery(
                sprintf("INSERT INTO sessions (id, data, accessed) 
                VALUES('%s', '%s', UNIX_TIMESTAMP(NOW())) ON DUPLICATE KEY UPDATE data='%s', accessed=UNIX_TIMESTAMP(NOW())", $id, $data, $data))) {
    $success = true;
  }
  return $success;
}

ini_set('session.use_only_cookies', 1);
ini_set('session.gc_probability', 0);
session_set_save_handler('custom_session_open', 'custom_session_close', 'custom_session_read', 'custom_session_write', 'custom_session_die', 'custom_session_gc');
register_shutdown_function('session_write_close');
