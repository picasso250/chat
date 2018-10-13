<?php

/**
 * Entry file
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

require ROOT.'/lib.php';
require ROOT.'/action.php';
require ROOT . '/model.php';

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