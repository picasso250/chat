<?php

/**
 * Magic class!
 * phpmd.phar . text codesize,design,unusedcode
 * phpcs . -n
 *
 * @category File
 * @package  GLOBAL
 * @author   xiaochi <wxiaochi@qq.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://coding.net/u/picasso250/p/10x-programer/git
 */

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

Res::$layout_tpl=ROOT_VIEW."/layout.phtml";

$action = isset($_GET['a'])?$_GET['a']:'index';
$func = "Action_$action";
if (!function_exists($func)) {
    die("no action");
}
$r=$func();
if (is_string($r)) {
    echo $r;
}

/**
 * Action index
 *
 * @return null
 */
function Action_index()
{
    $rooms = Db::fetchAll("SELECT * from chat_room where id > 3 limit 100");
    if (isset($_GET['api'])) {
        echo json_encode($rooms);
        return;
    }
    Res::renderWithLayout(['content'=>ROOT_VIEW."/index.phtml"], compact('rooms'));
}

/**
 * Action group
 *
 * @return null
 */
function Action_group()
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
    Res::renderWithLayout(['content'=>ROOT_VIEW."/group.phtml"], compact('id', 'g'));
}

/**
 * Action Action_Send_msg
 *
 * @return null
 */
function Action_Send_msg()
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

/**
 * Action Action_Pull_msg
 *
 * @return null
 */
function Action_Pull_msg()
{
    $db=Sv::db();
    if (!isset($_GET['group_id']))return("no group_id");
    if (!isset($_GET['last_id']))return("no last_id");
    $group_id = $_GET['group_id'];
    $last_id = $_GET['last_id'];
    list($para, $sql) = Build_sql($last_id, $group_id);
    $stmt = $db->prepare($sql);
    for ($i=0; $i < 10; $i++) {
        $stmt->execute($para);
        $data = $stmt->fetchAll(Pdo::FETCH_ASSOC)?:[];
        if ($data) break;
        usleep(100*1000); // 100ms
    }
    if ($last_id=="")
        $data = array_reverse($data);
    $data = Proc_data($data);
    $last_id = $data?$data[count($data)-1]['id']:'';
    echo json_encode(compact('data', 'last_id'));
}

/**
 * Build pull msg sql
 *
 * @param string $last_id  id of msg, can be empty
 * @param string $group_id room id
 *
 * @return array
 */
function Build_sql($last_id, $group_id)
{
    $where = $last_id == "" ? "" : "AND id>?";
    $para = $last_id == "" ? [$group_id] : [$group_id, $last_id];
    $asc = $last_id == "" ? "DESC" : "ASC";
    $sql = "SELECT * from chat where group_id=? $where ORDER BY id $asc limit 10";
    return array($para, $sql);
}

/**
 * Process message list
 *
 * @param array $data message list
 *
 * @return array
 */
function Proc_data($data)
{
    $data = array_map(
        function ($e) {
            $e['created'] = date('c', strtotime($e['created']));
            return $e;
        }, $data
    );
    return $data;
}
