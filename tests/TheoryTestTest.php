<?php

namespace TheoryTest\ADI\Tests;

use PHPUnit\Framework\TestCase;
use DBAL\Database;
use Smarty;
use TheoryTest\Car\User;
use TheoryTest\ADI\TheoryTest;

class TheoryTestTest extends TestCase{
    protected static $db;
    protected static $user;
    protected static $template;
    protected $theoryTest;
    
    public static function setUpBeforeClass() {
        self::$db = new Database($GLOBALS['DB_HOST'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD'], $GLOBALS['DB_DBNAME']);
        if(!self::$db->isConnected()){
             $this->markTestSkipped(
                'No local database connection is available'
            );
        }
        if(self::$db->count('users') < 1){
            self::$db->query(file_get_contents(dirname(dirname(__FILE__)).'/vendor/adamb/user/database/database_mysql.sql'));
            self::$db->query(file_get_contents(dirname(dirname(__FILE__)).'/vendor/adamb/hcldc/database/mysql_database.sql'));
            self::$db->query(file_get_contents(dirname(dirname(__FILE__)).'/vendor/adamb/hcldc/tests/sample_data/mysql_data.sql'));
            self::$db->query(file_get_contents(dirname(dirname(__FILE__)).'/database/database_mysql.sql'));
            //self::$db->query(file_get_contents(dirname(__FILE__).'/sample_data/data.sql'));
        }
        self::$template = new Smarty();
        self::$template->setCacheDir(dirname(__FILE__).'/cache/')->setCompileDir(dirname(__FILE__).'/cache/');
        self::$user = new User(self::$db);
    }
    
    protected function setUp() {
        $this->theoryTest = new TheoryTest(self::$db, self::$template, self::$user);
    }
    
    public function testExample() {
        $this->markTestIncomplete();
    }
    
}
