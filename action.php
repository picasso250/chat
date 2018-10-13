<?php

// phpcs:ignoreFile

/**
 * Action index
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 * @SuppressWarnings(PHPMD.Superglobals)
 * 
 * @return null
 */
function Action_index()
{
    $rooms = get_rooms();
    if (isset($_GET['api'])) {
        echo json_encode($rooms);
        return;
    }
    Res::renderWithLayout(['content' => ROOT_VIEW . "/index.phtml"], compact('rooms'));
}

/**
 * Action group
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 * @SuppressWarnings(PHPMD.Superglobals)
 * 
 * @return null
 */
function Action_group()
{
    if (isset($_GET['name'])) {
        $name = trim($_GET['name']);
        $group = get_room_by_name($name);
        $gid = $group['id'];
        if (!$group) {
            $gid = new_room($name);
        }
        header("location: ?a=group&id=$gid");
        return;
    }
    if (!isset($_GET['id'])) {
        return ("no id");
    }

    $gid = $_GET['id'];
    $group = get_room_by_id($gid);
    Res::renderWithLayout(['content' => ROOT_VIEW . "/group.phtml"], compact('gid', 'group'));
}

/**
 * Action Action_Send_msg
 *
 * @return null
 */
function Action_Send_msg()
{
    if (isset($_GET['jsonBody'])) {
        $args = json_decode(file_get_contents('php://input'), true);
        if (!isset($args['name'])) {
            return ("no name");
        }

        if (!isset($args['msg'])) {
            return ("no msg");
        }

        if (!isset($args['group_id'])) {
            return ("no group_id");
        }

        $name = trim($args['name']);
        $msg = trim($args['msg']);
        $group_id = trim($args['group_id']);
    } else {
        if (!isset($_POST['name'])) {
            return ("no name");
        }

        if (!isset($_POST['msg'])) {
            return ("no msg");
        }

        if (!isset($_POST['group_id'])) {
            return ("no group_id");
        }

        $name = trim($_POST['name']);
        $msg = trim($_POST['msg']);
        $group_id = trim($_POST['group_id']);
    }

    $_SESSION['name'] = $name;

    echo new_msg([
        'group_id'=>$group_id, 
        'name'=>$name, 
        'msg'=>trim($msg),
    ]);
}

/**
 * Action Action_Pull_msg
 *
 * @return null
 */
function Action_Pull_msg()
{
    $db = Sv::db();
    if (!isset($_GET['group_id'])) {
        return ("no group_id");
    }

    if (!isset($_GET['last_id'])) {
        return ("no last_id");
    }

    $group_id = $_GET['group_id'];
    $last_id = $_GET['last_id'];
    $data = get_msg_lst($last_id, $group_id);
    $data = Proc_data($data);
    $last_id = $data ? $data[count($data) - 1]['id'] : '';
    echo json_encode(compact('data', 'last_id'));
}
