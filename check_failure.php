<?php
require_once 'vendor/autoload.php';
require_once 'include/functions.php';

header('Content-Type: text/plain; charset=utf-8');

load_environment_variables();
check_auth_token();

$db_host = getenv('DB_HOST');
$db_name = getenv('DB_NAME');
$db_user = getenv('DB_USER');
$db_password = getenv('DB_PASSWORD');
try {
    $conn = new PDO("mysql:dbname=$db_name;host=$db_host", $db_user, $db_password, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));
    $conn->exec('
        UPDATE failure AS f
        INNER JOIN ping AS p ON f.connection = p.id
        SET f.end = p.last_ping_date
        WHERE f.end IS NULL AND p.last_ping_date > f.start
    ');
    $statement = $conn->prepare('
        SELECT * FROM ping WHERE last_ping_date <= :date_threshold
    ');
    $last_ping_threshold = (int)getenv('LAST_PING_THRESHOLD');
    $date_threshold = date('Y-m-d H:i:s', strtotime("-$last_ping_threshold minutes"));
    $statement->bindParam(':date_threshold', $date_threshold, PDO::PARAM_STR);
    $statement->execute();
    $rows = $statement->fetchAll();
    foreach($rows as $row) {
        $statement = $conn->prepare('INSERT INTO failure(connection, start) VALUES(:connection, :start)');
        $statement->bindParam(':connection', $row['id']);
        $statement->bindParam(':start', $row['last_ping_date']);
        $statement->execute();
    }
} catch (\Exception $e) {
    echo $e->getMessage();
    send_status_code(500, 'Internal Server Error');
} finally {
    $conn = null;
}