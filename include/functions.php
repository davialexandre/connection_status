<?php
require_once __DIR__.'/../vendor/autoload.php';

function send_status_code($status_code, $message) {
    header("HTTP/1.0 $status_code $message");
    if($status_code > 200 || $status_code > 299) {
        exit(1);
    }

    exit(0);
}

function load_environment_variables() {
    $dotenv = new Dotenv\Dotenv(__DIR__.'/..');
    try {
        $dotenv->load();
        $dotenv->required([
            'DB_HOST',
            'DB_NAME',
            'DB_USER',
            'DB_PASSWORD',
            'AUTH_TOKEN',
            'LAST_PING_THRESHOLD',
            'DATE_FORMAT',
            'FAILURES_LIST_SIZE'
        ]);
    } catch(\Exception $ex) {
        echo 'Não foi possível carregar as configurações';
        send_status_code(500, 'Internal Server Error');
    }
}

function check_auth_token() {
    $auth_token = filter_input(
        INPUT_POST,
        'auth_token',
        FILTER_SANITIZE_STRING
    );

    if (!$auth_token || $auth_token != getenv('AUTH_TOKEN')) {
        echo 'Você não tem autorização para acessar esse recurso';
        send_status_code(401, 'Unauthorized');
    }
}

function get_db_connection() {
    static $connection = null;

    $connection_options = array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'');
    $db_host = getenv('DB_HOST');
    $db_name = getenv('DB_NAME');
    $db_user = getenv('DB_USER');
    $db_password = getenv('DB_PASSWORD');
    if(!$connection) {
        try {
            $connection = new PDO("mysql:dbname=$db_name;host=$db_host", $db_user, $db_password, $connection_options);
        } catch(\Exception $e) {
            echo $e->getMessage();
            send_status_code(500, 'Internal Server Error');
        }
    }

    return $connection;
}

function db_query($query, $params = array()) {
    $connection = get_db_connection();
    $statement = $connection->prepare($query);
    foreach($params as $param) {
        $statement->bindParam($param['name'], $param['value'], $param['type']);
    }
    $statement->execute();

    return $statement;
}

function db_fetch_all(PDOStatement $statement) {
    if($statement) {
        return $statement->fetchAll();
    }

    return array();
}

function db_fetch_row(PDOStatement $statement) {
    if($statement) {
        return $statement->fetch();
    }

    return [];
}

function get_distinct_connections() {
    $query = 'SELECT id FROM ping';
    $rows = db_fetch_all(db_query($query));
    return array_column($rows, 'id');
}

function get_last_closed_failures_for_connection($connection, $limit = 5) {
    $query = 'SELECT * FROM failure
              WHERE connection = :connection AND end IS NOT NULL
              ORDER BY id DESC
              LIMIT :limit';
    $params = [
        [
            'name'  => ':connection',
            'value' => $connection,
            'type'  => PDO::PARAM_STR
        ],
        [
            'name'  => ':limit',
            'value' => $limit,
            'type'  => PDO::PARAM_INT
        ]
    ];
    $result = db_query($query, $params);
    return db_fetch_all($result);
}

function get_last_failure_for_connection($connection) {
    $query = 'SELECT * FROM failure 
              WHERE connection = :connection 
              ORDER BY id DESC
              LIMIT 1';
    $params = [
        [
            'name'  => ':connection',
            'value' => $connection,
            'type'  => PDO::PARAM_STR
        ]
    ];

    return db_fetch_row(db_query($query, $params));
}

function get_durantion_of_disconnection($start, $end = null) {
    $start = strtotime($start);
    $end = $end ?: 'now';
    $end = strtotime($end);
    $duration = $end - $start;
    $duration_hours = (int)($duration / 3600);
    $duration_minutes = (int)(($duration % 3600)/60);
    $duration_seconds = (int)(($duration % 3600) % 60);

    $duration_units = [];
    if($duration_hours) {
        $duration_units[] = "{$duration_hours}h";
    }
    if($duration_minutes) {
        $duration_units[] = "{$duration_minutes}m";
    }
    if($duration_seconds) {
        $duration_units[] = "{$duration_seconds}s";
    }

    return implode(' ', $duration_units);
}

function get_days_since_date($date) {
    $start = new DateTime($date);
    $end = new DateTime();
    $diff = $start->diff($end);
    if($diff) {
        return $diff->days;
    }

    return 0;
}