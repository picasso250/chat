<?php

define('ROOT', dirname(__DIR__));
define('ROOT_VIEW', ROOT.'/view');

require dirname(__DIR__).'/lib.php';

// env
if (!dot_env()) die("no .env file");

session_start();

// db service
sv::db(function() {
    $db = new Pdo($_ENV['db_dsn'], $_ENV['db_username'], $_ENV['db_password']);
    $db->setAttribute(Pdo::ATTR_EMULATE_PREPARES, false);
    $db->setAttribute(Pdo::ATTR_DEFAULT_FETCH_MODE, pdo::FETCH_ASSOC);
    return $db;
});

$action = isset($_GET['a'])?$_GET['a']:'index';
$func = "action_$action";
if(!function_exists($func)) {
    die("no action");
}
$func();

function action_index(){
    $_inner_ = ROOT_VIEW."/index.php";
    $rooms = db::fetchAll("SELECT * from chat_room where id > 3 limit 100");
    if(isset($_GET['api'])) {
        echo json_encode($rooms);
        exit();
    }
    include ROOT_VIEW."/layout.php";
}
function action_group(){
    if (isset($_GET['name'])) {
        $name = trim($_GET['name']);
        $g = db::fetch("SELECT *from chat_room where `name`=? limit 1", [$name]);
        $id = $g['id'];
        if (!$g) {
            $sql = "INSERT into chat_room (`name`,created)values(?,now()) ON DUPLICATE KEY UPDATE `name`=?";
            db::execute($sql, [$name, $name]);
            $db=sv::db();
            $id = $db->lastInsertId();
        }
        header("location: ?a=group&id=$id");
        exit;
    }
    if (!isset($_GET['id']))die("no id");
    $id = $_GET['id'];
    $g = db::fetch("SELECT *from chat_room where `id`=? limit 1", [$id]);
    $_inner_ = ROOT_VIEW."/group.php";
    include ROOT_VIEW."/layout.php";
}
function action_send_msg(){
    $db=sv::db();
    if (isset($_GET['jsonBody'])) {
        $args = json_decode(file_get_contents('php://input'), true);
        if(!isset($args['name']))die("no name");
        if(!isset($args['msg'])) die("no msg");
        if(!isset($args['group_id']))die("no group_id");
        $name = trim($args['name']);
        $msg = trim($args['msg']);
        $group_id = trim($args['group_id']);
    } else {
        if(!isset($_POST['name']))die("no name");
        if(!isset($_POST['msg'])) die("no msg");
        if(!isset($_POST['group_id']))die("no group_id");
        $name = trim($_POST['name']);
        $msg = trim($_POST['msg']);
        $group_id = trim($_POST['group_id']);
    }

    $_SESSION['name'] = $name;

    $stmt=$db->prepare("INSERT into chat (group_id,`name`,msg,created)values(?,?,?,now())");
    $stmt->execute([$group_id,$name,trim($msg)]);
    echo $db->lastInsertId();
}
function action_pull_msg(){
    $db=sv::db();
    if(!isset($_GET['group_id']))die("no group_id");
    if(!isset($_GET['last_id']))die("no last_id");
    $group_id = $_GET['group_id'];
    $last_id = $_GET['last_id'];
    $where = $last_id == "" ? "" : "AND id>?";
    $para  = $last_id == "" ? [$group_id] : [$group_id,$last_id];
    $asc   = $last_id == "" ? "DESC" : "ASC";
    $sql = "SELECT * from chat where group_id=? $where ORDER BY id $asc limit 10";
    $stmt = $db->prepare($sql);
    for ($i=0; $i < 10; $i++) {
        $stmt->execute($para);
        $data = $stmt->fetchAll(Pdo::FETCH_ASSOC)?:[];
        if ($data) break;
        usleep(100*1000); // 100ms
    }
    if ($last_id=="")
        $data = array_reverse($data);
    $data = array_map(function($e){
        $e['created'] = date('c', strtotime($e['created']));
        return $e;
    }, $data);
    $last_id = $data?$data[count($data)-1]['id']:'';
    echo json_encode(compact('data', 'last_id'));
}
