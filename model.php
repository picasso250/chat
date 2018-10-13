<?php

// phpcs:ignoreFile

/**
 * Undocumented function
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 * 
 * @return void
 */
function get_rooms()
{
    return Db::fetchAll("SELECT * from chat_room where id > 0 limit 100");
}

/**
 * Undocumented function
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 *
 * @return void
 */
function get_room_by_name($name)
{
    return Db::fetch("SELECT *from chat_room where `name`=? limit 1", [$name]);
}


/**
 * Undocumented function
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 *
 * @return void
 */
function get_room_by_id($rid)
{
    return Db::fetch("SELECT *from chat_room where `id`=? limit 1", [$rid]);
}

/**
 * Undocumented function
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 *
 * @return void
 */
function new_room($name)
{
    $sql = "INSERT into chat_room (`name`,created)values(?,now()) ON DUPLICATE KEY UPDATE `name`=?";
    Db::execute($sql, [$name, $name]);
    $dbs = Sv::db();
    return $dbs->lastInsertId();
}

/**
 * Undocumented function
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 *
 * @return void
 */
function new_msg($data)
{
    $dbs = Sv::db();
    $stmt = $dbs->prepare("INSERT into chat (group_id,`name`,msg,created) values(:group_id,:name,:msg,now())");
    $stmt->execute($data);
    return $dbs->lastInsertId();
}
/**
 * Undocumented function
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 *
 * @return void
 */
function get_msg_lst($lastId, $groupId)
{
    $dbs = Sv::db();
    list($para, $sql) = Build_sql($lastId, $groupId);
    $stmt = $dbs->prepare($sql);
    for ($i = 0; $i < 10; $i++) {
        $stmt->execute($para);
        $data = $stmt->fetchAll(Pdo::FETCH_ASSOC) ?: [];
        if ($data) {
            break;
        }

        usleep(100 * 1000); // 100ms
    }
    if ($lastId == "") {
        $data = array_reverse($data);
    }
    return $data;
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
