<?php

define('ROOT', dirname(__DIR__));

require ROOT . '/lib.php';
require ROOT . '/action.php';
require ROOT . '/model.php';


// env
if (!dotEnv()) {
    die("no .env file");
}

// db service
Sv::db(
    function () {
        $db = new Pdo($_ENV['db_dsn'], $_ENV['db_username'], $_ENV['db_password']);
        $db->setAttribute(Pdo::ATTR_EMULATE_PREPARES, false);
        $db->setAttribute(Pdo::ATTR_DEFAULT_FETCH_MODE, pdo::FETCH_ASSOC);
        return $db;
    }
);
