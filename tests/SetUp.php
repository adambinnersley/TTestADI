<?php

namespace  TheoryTest\ADI\Tests;

error_reporting(0);

use PHPUnit\Framework\TestCase;
use DBAL\Database;
use Configuration\Config;
use Smarty;
use TheoryTest\Car\User;

abstract class SetUp extends TestCase
{
    
    protected $db;
    protected $config;
    protected $user;
    protected $template;
    
    protected function setUp() : void
    {
        $this->db = new Database($GLOBALS['DB_HOST'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD'], $GLOBALS['DB_DBNAME']);
        if (!$this->db->isConnected()) {
             $this->markTestSkipped(
                 'No local database connection is available'
             );
        }
        $this->db->query(file_get_contents(dirname(dirname(__FILE__)).'/vendor/adamb/user/database/database_mysql.sql'));
        if ($this->db->count('users') < 1) {
            $this->db->query(file_get_contents(dirname(dirname(__FILE__)).'/vendor/adamb/hcldc/database/mysql_database.sql'));
            $this->db->query(file_get_contents(dirname(dirname(__FILE__)).'/vendor/adamb/hcldc/tests/sample_data/mysql_data.sql'));
            $this->db->query(file_get_contents(dirname(dirname(__FILE__)).'/database/database_mysql.sql'));
            $this->db->query(file_get_contents(dirname(__FILE__).'/sample_data/data.sql'));
        }
        $this->config = new Config($this->db);
        $this->template = new Smarty();
        $this->template->setCacheDir(dirname(__FILE__).'/cache/')->setCompileDir(dirname(__FILE__).'/cache/');
        $this->user = new User($this->db);
    }
    
    protected function tearDown() : void
    {
        $this->db = null;
        $this->template = null;
        $this->user = null;
    }
}
