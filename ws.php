<?php

define('ROOT', (__DIR__));

require ROOT.'/lib.php';

// env
dot_env();

// db service
sv::db(function() {
    $db = new Pdo($_ENV['db_dsn'], $_ENV['db_username'], $_ENV['db_password']);
    $db->setAttribute(Pdo::ATTR_EMULATE_PREPARES, false);
    return $db;
});

$stdin = fopen('php://stdin', 'r');
while ($line = fgets($stdin)) {
    $room_id = trim($line);
    break;
}

$db=sv::db();

// init
$sql = "SELECT * from chat where group_id=? ORDER BY id DESC limit 10";
$stmt = $db->prepare($sql);
$stmt->execute([$room_id]);
$data = $stmt->fetchAll(Pdo::FETCH_ASSOC)?:[];

$data = array_reverse($data);
$data = array_map(function($e){
    $e['created'] = date('c', strtotime($e['created']));
    return $e;
}, $data);

$last_id = 0;
foreach ($data as $msg) {
    echo json_encode($msg),"\n";
    $last_id = $msg['id'];
}

// go on
$sql = "SELECT * from chat where group_id=? and id>? ORDER BY id ASC limit 10";
$stmt = $db->prepare($sql);
while (true) {
    $stmt->execute([$room_id, $last_id]);
    $data = $stmt->fetchAll(Pdo::FETCH_ASSOC)?:[];
    if ($data) {
        $data = array_map(function($e){
            $e['created'] = date('c', strtotime($e['created']));
            return $e;
        }, $data);
        foreach ($data as $msg) {
            echo json_encode($msg),"\n";
            $last_id = $msg['id'];
        }
    }
    usleep(100*1000); // 100ms
}
