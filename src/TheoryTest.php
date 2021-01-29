<?php

namespace TheoryTest\ADI;

use DBAL\Database;
use Configuration\Config;
use Smarty;

class TheoryTest extends \TheoryTest\Car\TheoryTest
{
    protected $seconds = 5400;
    protected $section = 'aditheory';
    
    public $passmark = 85;
    public $passmarkPerCat = 20;
    
    protected $scriptVar = 'adi';
    
    /**
     * Set up all of the components needed to create a Theory Test
     * @param Database $db This should be an instance of Database
     * @param Config $config This should be an instance of Config
     * @param Smarty $layout This needs to be an instance of Smarty Templating
     * @param object $user This should be and instance if the User Class
     * @param false|int $userID If you wish to emulate a user set this value to the users ID else set to false
     * @param string|false $templateDir Set the template location if different from default else set to false
     */
    public function __construct(Database $db, Config $config, Smarty $layout, $user, $userID = false, $templateDir = false, $theme = 'bootstrap')
    {
        parent::__construct($db, $config, $layout, $user, $userID, $templateDir, $theme);
        $this->layout->addTemplateDir(($templateDir === false ? str_replace(basename(__DIR__), '', dirname(__FILE__)).'templates'.DS.$theme : $templateDir), 'aditheory');
        $this->setImagePath(DS.'images'.DS.'adi'.DS);
    }
    
    /**
     * Sets the tables
     */
    public function setTables()
    {
        $this->testsTable = $this->config->table_adi_theory_tests;
        $this->questionsTable = $this->config->table_adi_questions;
        $this->learningProgressTable = $this->config->table_adi_progress;
        $this->progressTable = $this->config->table_adi_test_progress;
        $this->dvsaCatTable = $this->config->table_adi_dvsa_sections;
    }

    /**
     * Create a new ADI Theory Test for the test number given
     * @param int $theorytest Should be the test number
     * @return string Returns the HTML for a test
     */
    public function createNewTest($theorytest = 1)
    {
        $this->clearSettings();
        $this->setTest($theorytest);
        if (method_exists($this->user, 'checkUserAccess')) {
            $this->user->checkUserAccess($theorytest, 'adi');
        }
        $this->setTestName();
        if ($this->anyExisting() === false) {
            $this->chooseQuestions($theorytest);
        }
        return $this->buildTest();
    }
    
    /**
     * Sets the current test name
     * @param string $name The name of the test you want to set it to, if left blank will be Theory Test plus test number
     */
    protected function setTestName($name = '')
    {
        if (!empty($name)) {
            $this->testName = $name;
        } else {
            $this->testName = 'ADI Test '.$this->getTest();
        }
    }
    
    /**
     * Returns the question data for the given prim number
     * @param int $prim Should be the question prim number
     * @return array|boolean Returns question data as array if data exists else returns false
     */
    protected function getQuestionData($prim)
    {
        return $this->db->select($this->questionsTable, ['prim' => $prim], ['prim', 'question', 'mark', 'option1', 'option2', 'option3', 'option4', 'answerletters', 'format', 'dsaimageid', 'dsaexplanation']);
    }

    
    /**
     * Returns the DVSA Band for a given prim number
     * @param int $prim The prim number for the question you are looking for
     * @return int The DVSA Band for a given prim number
     */
    protected function getDVSABand($prim)
    {
        $dvsacat = $this->db->select($this->questionsTable, ['prim' => $prim], ['dsaband']);
        return $dvsacat['dsaband'];
    }
    
    /**
     * Returns the DVSA Band number for a given prim number
     * @param int $prim The prim number for the question you are looking for
     * @return int The DVSA Band for a given prim number
     */
    protected function getDVSABandNo($prim)
    {
        $dvsacat = $this->db->select($this->questionsTable, ['prim' => $prim], ['dsabandno']);
        return $dvsacat['dsabandno'];
    }
    
    /**
     * Returns the question information e.g. category, topic number for any given prim
     * @param int $prim this is the question unique number
     * @return array Returns that particular prim info
     */
    public function questionInfo($prim)
    {
        $questioninfo = $this->db->select($this->questionsTable, ['prim' => $prim], ['prim', 'dsaband', 'dsaqposition']);
        $catinfo = $this->db->select($this->dvsaCatTable, ['section' => $questioninfo['dsaband']]);
        
        $info = [];
        $info['prim'] = $questioninfo['prim'];
        $info['cat'] = $catinfo['name'];
        $info['topic'] = $questioninfo['dsaqposition'];
        return $info;
    }
    
    /**
     * Produces the amount of time the user has spent on the test
     * @param int $time This should be the amount of seconds remaining for the current test
     * @return void
     */
    public function setTime($time, $type = 'taken')
    {
        if ($time) {
            if ($type == 'taken') {
                list($hours, $mins, $secs) = explode(':', $time);
                $time = gmdate('H:i:s', ($this->seconds - (($hours * 60 * 60) + ($mins * 60) + $secs)));
                $this->userProgress['time_taken'] = $time;
                $this->db->update($this->progressTable, ['time_'.$type => $time], ['user_id' => $this->getUserID(), 'test_id' => $this->getTest(), 'current_test' => 1]);
            } else {
                $_SESSION['time_'.$type]['test'.$this->getTest()] = $time;
            }
        }
    }
    
    /**
     * Returns the number of seconds remaining for the current test
     * @return int Returns the number of seconds remaining for the current test
     */
    protected function getSeconds()
    {
        $time = $this->getTime('remaining');
        list($hours, $minutes, $seconds) = explode(':', $time);
        return ((intval($hours) * 3600) + (intval($minutes) * 60) + intval($seconds));
    }
    
    /**
     * Marks the current test for the user
     * @param int|false $time The time to set as taken for the current test of false to not update
     * @return void Nothing is returned
     */
    protected function markTest($time = false)
    {
        $this->getQuestions();
        foreach ($this->questions as $prim) {
            if ($_SESSION['test'.$this->getTest()][$this->questionNo($prim)]['status'] == 4) {
                $type = 'correct';
            } else {
                $type = 'incorrect';
            }
             
            $dvsa = $this->getDVSABand($prim);
            $dvsano = $this->getDVSABandNo($prim);
            $this->testresults['dvsa'][$dvsa][$type] = (int)$this->testresults['dvsa'][$dvsa][$type] + 1;
            $this->testresults['dvsano'][$dvsano][$type] = (int)$this->testresults['dvsano'][$dvsano][$type] + 1;
        }
        
        $pass = true;
        foreach ($this->testresults['dvsano'] as $value) {
            if ($pass !== false && $value['correct'] < $this->passmarkPerCat) {
                $pass = false;
            }
        }
        unset($this->testresults['dvsano']);
        
        $this->testresults['correct'] = $this->numCorrect();
        $this->testresults['incorrect'] = ($this->numQuestions() - $this->numCorrect());
        $this->testresults['incomplete'] = $this->numIncomplete();
        $this->testresults['flagged'] = $this->numFlagged();
        $this->testresults['numquestions'] = $this->numQuestions();
        $this->testresults['percent']['correct'] = round(($this->testresults['correct'] / $this->testresults['numquestions']) * 100);
        $this->testresults['percent']['incorrect'] = round(($this->testresults['incorrect'] / $this->testresults['numquestions']) * 100);
        $this->testresults['percent']['flagged'] = round(($this->testresults['flagged'] / $this->testresults['numquestions']) * 100);
        $this->testresults['percent']['incomplete'] = round(($this->testresults['incomplete'] / $this->testresults['numquestions']) * 100);
        if ($this->numCorrect() >= $this->passmark && $pass === true) {
            $this->testresults['status'] = 'pass';
            $status = 1;
        } else {
            $this->testresults['status'] = 'fail';
            $status = 2;
        }
        if ($time !== false && preg_match('~[0-9]+~', $time)) {
            list($hours, $mins, $secs) = explode(':', $time);
            $newtime = gmdate('H:i:s', ($this->seconds - (($hours * 60 * 60) + ($mins * 60) + $secs)));
            $this->userProgress['time_taken'] = $newtime;
        }
        $this->db->update($this->progressTable, array_merge(['status' => $status, 'results' => serialize($this->testresults), 'complete' => date('Y-m-d H:i:s'), 'totalscore' => $this->numCorrect()], ($time !== false ? ['time_taken' => $newtime] : [])), ['user_id' => $this->getUserID(), 'test_id' => $this->getTest(), 'status' => 0, 'current_test' => 1]);
    }
    
    /**
     * Returns an overview of the results to be put into a table
     * @return string Returns an overview of the results to be put into a table
     */
    protected function createOverviewResults()
    {
        $dvsaCats = $this->db->selectAll($this->dvsaCatTable);
        $catresults = [];
        foreach ($dvsaCats as $i => $dvsacat) {
            $catresults[$i]['section'] = $dvsacat['section'];
            $catresults[$i]['name'] = str_replace($dvsacat['section'].'.', '', $dvsacat['name']);
            $catresults[$i]['correct'] = (int)$this->testresults['dvsa'][$dvsacat['section']]['correct'];
            $catresults[$i]['incorrect'] = (int)$this->testresults['dvsa'][$dvsacat['section']]['incorrect'];
            $catresults[$i]['total'] = ((int)$this->testresults['dvsa'][$dvsacat['section']]['correct'] + (int)$this->testresults['dvsa'][$dvsacat['section']]['incorrect'] + (int)$this->testresults['dvsa'][$dvsacat['section']]['unattempted']);
        }
        return $catresults;
    }
}
