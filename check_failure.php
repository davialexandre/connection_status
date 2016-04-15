<?php
require_once 'include/functions.php';

header('Content-Type: text/plain; charset=utf-8');

load_environment_variables();
check_auth_token();

db_query('
    UPDATE failure AS f
    INNER JOIN ping AS p ON f.connection = p.id
    SET f.end = p.last_ping_date
    WHERE f.end IS NULL AND p.last_ping_date > f.start
');

$last_ping_threshold = (int)getenv('LAST_PING_THRESHOLD');
$date_threshold = date('Y-m-d H:i:s', strtotime("-$last_ping_threshold minutes"));
$params = array(
    array('name' => ':date_threshold', 'value' => $date_threshold, 'type' => PDO::PARAM_STR)
);
$result = db_query('SELECT * FROM ping WHERE last_ping_date <= :date_threshold', $params);

$rows = db_fetch_all($result);
$insert_failure_query = 'INSERT INTO failure(connection, start) VALUES(:connection, :start)';
foreach($rows as $row) {
    $insert_failure_params = array(
        array('name' => ':connection', 'value' => $row['id'], 'type' => PDO::PARAM_STR),
        array('name' => ':start', 'value' => $row['last_ping_date'], 'type' => PDO::PARAM_STR),
    );
    db_query($insert_failure_query, $insert_failure_params);
}