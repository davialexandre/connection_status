<?php
require_once 'vendor/autoload.php';
require_once 'include/functions.php';

header('Content-Type: text/plain; charset=utf-8');

load_environment_variables();
check_auth_token();

$connection_name = filter_input(
    INPUT_POST,
    'connection',
    FILTER_SANITIZE_STRING,
    ['flags' => FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH]
);

if(!$connection_name) {
    echo 'A requisição não enviou todos os parâmetros necessários';
    send_status_code(500, 'Internal Server Error');
}

$query = '
  INSERT INTO ping(id, last_ping_date) VALUES(:id, :date)
  ON DUPLICATE KEY UPDATE last_ping_date = :date
';
$params = array(
    array('name' => ':id', 'value' => $connection_name, 'type' => PDO::PARAM_STR),
    array('name' => ':date', 'value' => date('Y-m-d H:i:s'), 'type' => PDO::PARAM_STR),
);
db_query($query, $params);