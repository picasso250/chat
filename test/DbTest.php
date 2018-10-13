<?php
// phpcs:ignoreFile

use PHPUnit\Framework\TestCase;

class DbTest extends TestCase
{

    // only instantiate pdo once for test clean-up/fixture load
    static private $pdo = null;

    // only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
    private $conn = null;

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     * 
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    public function getConnection()
    {
        // if ($this->conn === null) {
        //     if (self::$pdo == null) {
        //         self::$pdo = new PDO( $GLOBALS['db_dsn'], $GLOBALS['db_username'], $GLOBALS['db_password'] );
        //     }
        //     // self::$pdo->exec("");
        //     $this->conn = $this->createDefaultDBConnection(self::$pdo, $GLOBALS['DB_DBNAME']);
        // }

        // return $this->conn;
    }

    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        return $this->createFlatXMLDataSet(dirname(__FILE__).'/_files/t-seed.xml');
    }

    public function testCanNewRoom()
    {
        // $ds = new PHPUnit_Extensions_Database_DataSet_QueryDataSet($this->getConnection());
        // $ds->addTable('t');
        $roomName = 'xc'.mt_rand();
        $rid = new_room($roomName);
        $rooms = get_rooms();
        $this->assertEquals($roomName, $rooms[count($rooms)-1]['name']);
        $room = get_room_by_name($roomName);
        $this->assertEquals($rid, $room['id']);
        $room2 = get_room_by_id($rid);
        $this->assertEquals($roomName, $room2['name']);
        Sv::db()->exec("DELETE from room where id=$rid");
    }
    public function testCanChat()
    {
        // $ds = new PHPUnit_Extensions_Database_DataSet_QueryDataSet($this->getConnection());
        // $ds->addTable('t');
        $roomName = 'xc'.mt_rand();
        $rid = new_room($roomName);
        
        $mid = new_msg([
            'group_id' => $rid,
            'name' => "xc",
            'msg' => "hello",
        ]);

        $lst = get_msg_lst("", $rid);
        $ids = array_column($lst, 'id');
        $this->assertContains($mid, $ids);

        Sv::db()->exec("DELETE from room where id=$rid");
        Sv::db()->exec("DELETE from msg where id=$mid");
    }

}
