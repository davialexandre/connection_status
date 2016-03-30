<?php

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
            'LAST_PING_THRESHOLD'
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