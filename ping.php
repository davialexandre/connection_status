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

$db_host = getenv('DB_HOST');
$db_name = getenv('DB_NAME');
$db_user = getenv('DB_USER');
$db_password = getenv('DB_PASSWORD');
try {
    $conn = new PDO("mysql:dbname=$db_name;host=$db_host", $db_user, $db_password, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));
    $statement = $conn->prepare('
      INSERT INTO ping(id, last_ping_date) VALUES(:id, :date)
      ON DUPLICATE KEY UPDATE last_ping_date = :date
    ');
    $statement->bindParam(':id', $connection_name, PDO::PARAM_STR);
    $statement->bindParam(':date', date('Y-m-d H:i:s'), PDO::PARAM_STR);
    $statement->execute();
} catch (\Exception $e) {
    echo $e->getMessage();
    send_status_code(500, 'Internal Server Error');
} finally {
    $conn = null;
}