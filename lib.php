<?php
// magic class!

// phpmd.phar lib.php text codesize,design,unusedcode

// service, container, IoC
class sv {
    static $lazy;
    static $pool;
    static function __callStatic($name, $args){
        $value = isset($args[0]) ? $args[0] : null;
        if ($value=== null){
            // get
            if(isset(self::$pool[$name]))return self::$pool[$name];
            if(isset(self::$lazy[$name])) {
                $f = self::$lazy[$name];
                return self::$pool[$name]=$f();
            }
            return null;
        } else {
            // set
            if (is_callable($value)) self::$lazy[$name]=$value;
            else self::$pool[$name]=$value;
        }
    }
}
function dot_env($root=__DIR__){
    if (defined("ROOT")&&$root==="") $root=ROOT;
    $file="$root/.env";
    if(!file_exists($file)) return false;
    $vars=parse_ini_file($file);
    foreach($vars as $k=>$v){
        $_ENV[$k]=$v;
    }
    return true;
}
class db
{
    static function execute($sql, $vars=[]) {
        $db = sv::db();
        $stmt=$db->prepare($sql);
        $stmt->execute($vars);
        return $stmt;
    }
    static function fetchAll($sql, $vars=[]) {
        $stmt = self::execute($sql, $vars);
        $a = $stmt->fetchAll();
        return $a ? $a : [];
    }
    static function fetch($sql, $vars=[]) {
        $stmt = self::execute($sql, $vars);
        $a = $stmt->fetch();
        return $a;
    }
}
