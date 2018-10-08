<?php

define('ROOT', dirname(__DIR__));
define('ROOT_VIEW', ROOT.'/view');

require dirname(__DIR__).'/lib.php';

// env
if (!dotEnv()) die("no .env file");

session_start();

// db service
Sv::db(
    function () {
        $db = new Pdo($_ENV['db_dsn'], $_ENV['db_username'], $_ENV['db_password']);
        $db->setAttribute(Pdo::ATTR_EMULATE_PREPARES, false);
        $db->setAttribute(Pdo::ATTR_DEFAULT_FETCH_MODE, pdo::FETCH_ASSOC);
        return $db;
    }
);

Res::$layout_tpl=ROOT_VIEW."/layout.php";

$action = isset($_GET['a'])?$_GET['a']:'index';
$func = "action_$action";
if(!function_exists($func)) {
    die("no action");
}
$r=$func();
if (is_string($r)) {
    echo $r;
}

function action_index()
{
    $rooms = Db::fetchAll("SELECT * from chat_room where id > 3 limit 100");
    if(isset($_GET['api'])) {
        echo json_encode($rooms);
        return;
    }
    Res::renderWithLayout(['content'=>ROOT_VIEW."/index.php"], compact('rooms'));
}
function action_group()
{
    if (isset($_GET['name'])) {
        $name = trim($_GET['name']);
        $g = Db::fetch("SELECT *from chat_room where `name`=? limit 1", [$name]);
        $id = $g['id'];
        if (!$g) {
            $sql = "INSERT into chat_room (`name`,created)values(?,now()) ON DUPLICATE KEY UPDATE `name`=?";
            Db::execute($sql, [$name, $name]);
            $db=Sv::db();
            $id = $db->lastInsertId();
        }
        header("location: ?a=group&id=$id");
        return;
    }
    if (!isset($_GET['id'])) return("no id");
    $id = $_GET['id'];
    $g = Db::fetch("SELECT *from chat_room where `id`=? limit 1", [$id]);
    Res::renderWithLayout(['content'=>ROOT_VIEW."/group.php"], compact('id', 'g'));
}
function action_send_msg()
{
    $db=Sv::db();
    if (isset($_GET['jsonBody'])) {
        $args = json_decode(file_get_contents('php://input'), true);
        if(!isset($args['name']))return ("no name");
        if(!isset($args['msg'])) return ("no msg");
        if(!isset($args['group_id']))return ("no group_id");
        $name = trim($args['name']);
        $msg = trim($args['msg']);
        $group_id = trim($args['group_id']);
    } else {
        if(!isset($_POST['name']))return ("no name");
        if(!isset($_POST['msg'])) return ("no msg");
        if(!isset($_POST['group_id']))return ("no group_id");
        $name = trim($_POST['name']);
        $msg = trim($_POST['msg']);
        $group_id = trim($_POST['group_id']);
    }

    $_SESSION['name'] = $name;

    $stmt=$db->prepare("INSERT into chat (group_id,`name`,msg,created)values(?,?,?,now())");
    $stmt->execute([$group_id,$name,trim($msg)]);
    echo $db->lastInsertId();
}
function action_pull_msg()
{
    $db=Sv::db();
    if(!isset($_GET['group_id']))return("no group_id");
    if(!isset($_GET['last_id']))return("no last_id");
    $group_id = $_GET['group_id'];
    $last_id = $_GET['last_id'];
    list($para, $sql) = build_sql($last_id, $group_id);
    $stmt = $db->prepare($sql);
    for ($i=0; $i < 10; $i++) {
        $stmt->execute($para);
        $data = $stmt->fetchAll(Pdo::FETCH_ASSOC)?:[];
        if ($data) break;
        usleep(100*1000); // 100ms
    }
    if ($last_id=="")
        $data = array_reverse($data);
    $data = proc_data($data);
    $last_id = $data?$data[count($data)-1]['id']:'';
    echo json_encode(compact('data', 'last_id'));
}

/**
 * @param  $last_id
 * @param  $group_id
 * @return array
 */
function build_sql($last_id, $group_id)
{
    $where = $last_id == "" ? "" : "AND id>?";
    $para = $last_id == "" ? [$group_id] : [$group_id, $last_id];
    $asc = $last_id == "" ? "DESC" : "ASC";
    $sql = "SELECT * from chat where group_id=? $where ORDER BY id $asc limit 10";
    return array($para, $sql);
}

/**
 * @param  $data
 * @return array
 */
function proc_data($data)
{
    $data = array_map(
        function ($e) {
            $e['created'] = date('c', strtotime($e['created']));
            return $e;
        }, $data
    );
    return $data;
}
