<?php

define('ROOT', dirname(__DIR__));
define('ROOT_VIEW', ROOT.'/view');

// env
$env=parse_ini_file(ROOT."/.env");
foreach ($env as $key => $value) {
    $_ENV[$key] = $value;
}

session_start();

$action = isset($_GET['a'])?$_GET['a']:'index';
$func = "action_$action";
if(!function_exists($func)) {
    die("no action");
}
$func();

function action_index(){
    $_inner_ = ROOT_VIEW."/index.php";
    include ROOT_VIEW."/layout.php";
}
function action_group(){
    if (!isset($_GET['id']))die("no id");
    $id = $_GET['id'];
    $_inner_ = ROOT_VIEW."/group.php";
    include ROOT_VIEW."/layout.php";
}
function action_send_msg(){
    $db=get_db();
    if(!isset($_POST['name']))die("no name");
    if(!isset($_POST['msg']))die("no msg");
    if(!isset($_POST['group_id']))die("no group_id");

    $name = trim($_POST['name']);

    $_SESSION['name'] = $name;

    $stmt=$db->prepare("INSERT into chat (group_id,`name`,msg,created)values(?,?,?,now())");
    $stmt->execute([$_POST['group_id'],$name,trim($_POST['msg'])]);
}
function action_pull_msg(){
    $db=get_db();
    if(!isset($_GET['group_id']))die("no group_id");
    if(!isset($_GET['last_id']))die("no last_id");
    $group_id = $_GET['group_id'];
    $last_id = $_GET['last_id'];
    $where = $last_id == "" ? "" : "AND id>?";
    $para = $last_id == "" ? [$group_id] : [$group_id,$last_id];
    $asc = $last_id == "" ? "DESC" : "ASC";
    $sql = "SELECT * from chat where group_id=? $where order by id $asc limit 10";
    $stmt = $db->prepare($sql);
    for ($i=0; $i < 10; $i++) {
        $stmt->execute($para);
        $data = $stmt->fetchAll(Pdo::FETCH_ASSOC)?:[];
        if ($data) break;
        usleep(100*1000);
    }
    if ($last_id=="")
        $data = array_reverse($data);
    $last_id = $data?$data[count($data)-1]['id']:'';
    echo json_encode(compact('data', 'last_id'));
}

// lib ==============
function get_db(){
    static $db;
    if (!$db){
        $db =new Pdo($_ENV['db_dsn'], $_ENV['db_username'], $_ENV['db_password']);
        $db->setAttribute(Pdo::ATTR_EMULATE_PREPARES, false);
    }
    return $db;
}