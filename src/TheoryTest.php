<?php

namespace TheoryTest\ADI;

use DBAL\Database;
use Configuration\Config;
use Smarty;

class TheoryTest extends \TheoryTest\Car\TheoryTest{
    protected $seconds = 5400;
    protected $section = 'aditheory';
    
    public $passmark = 85;
    public $passmarkPerCat = 20;
    
    protected $audioLocation = '/audio/adi';
    
    protected $scriptVar = 'adiquestions';
    
    protected $testType = 'adi';
    
    /**
     * Set up all of the components needed to create a Theory Test
     * @param Database $db This should be an instance of Database
     * @param Config $config This should be an instance of Config
     * @param Smarty $layout This needs to be an instance of Smarty Templating
     * @param object $user This should be and instance if the User Class
     * @param false|int $userID If you wish to emulate a user set this value to the users ID else set to false
     * @param string|false $templateDir If you want to change the template location set this location here else set to false
     */
    public function __construct(Database $db, Config $config, Smarty $layout, $user, $userID = false, $templateDir = false) {
        parent::__construct($db, $config, $layout, $user, $userID, $templateDir);
        $this->layout->addTemplateDir(($templateDir === false ? str_replace(basename(__DIR__), '', dirname(__FILE__)).'templates' : $templateDir), 'aditheory');
        $this->setImagePath(ROOT.DS.'images'.DS.'adi'.DS);
    }
    
    /**
     * Sets the tables
     */
    public function setTables() {
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
    public function createNewTest($theorytest = 1){
        $this->clearSettings();
        $this->setTest($theorytest);
        if(method_exists($this->user, 'checkUserAccess')){$this->user->checkUserAccess($theorytest, 'adi');}
        $this->setTestName();
        if($this->anyExisting() === false){
            $this->chooseQuestions($theorytest);
        }
        return $this->buildTest();
    }
    
    /**
     * Choose some random questions from each of the categories and insert them into the progress database
     * @param int $testNo This should be the test number you which to get the questions for
     * @return boolean
     */
    protected function chooseQuestions($testNo){
        $this->db->delete($this->progressTable, ['user_id' => $this->getUserID(), 'test_id' => $testNo]);
        $questions = $this->db->selectAll($this->questionsTable, ['mt'.$testNo => ['>=', 1]], '*', ['mt'.$testNo => 'ASC']);
        unset($_SESSION['test'.$this->getTest()]);
        foreach($questions as $q => $question){
            $this->questions[($q + 1)] = $question['prim'];
        }
        return $this->db->insert($this->progressTable, ['user_id' => $this->getUserID(), 'questions' => serialize($this->questions), 'answers' => serialize([]), 'test_id' => $testNo, 'started' => date('Y-m-d H:i:s'), 'status' => 0, 'type' => $this->getTestType()]);
    }
    
    /**
     * Sets the current test name
     * @param string $name This should be the name of the test you wish to set it to if left blank will just be Theory Test plus test number
     */
    protected function setTestName($name = ''){
        if(!empty($name)){
            $this->testName = $name;
        }
        else{
            $this->testName = 'ADI Test '.$this->getTest();
        }
    }
    
    /**
     * Returns the question data for the given prim number
     * @param int $prim Should be the question prim number
     * @return array|boolean Returns question data as array if data exists else returns false
     */
    protected function getQuestionData($prim){
        return $this->db->select($this->questionsTable, ['prim' => $prim], ['prim', 'question', 'mark', 'option1', 'option2', 'option3', 'option4', 'answerletters', 'format', 'dsaimageid', 'dsaexplanation']);
    }

    
    /**
     * Returns the DSA Band for a given prim number
     * @param int $prim The prim number for the question you are looking for
     * @return int The DSA Band for a given prim number
     */
    protected function getDSABand($prim){
        $dsacat = $this->db->select($this->questionsTable, ['prim' => $prim], ['dsaband']);
        return $dsacat['dsaband'];
    }
    
    /**
     * Returns the DSA Band number for a given prim number
     * @param int $prim The prim number for the question you are looking for
     * @return int The DSA Band for a given prim number
     */
    protected function getDSABandNo($prim){
        $dsacat = $this->db->select($this->questionsTable, ['prim' => $prim], ['dsabandno']);
        return $dsacat['dsabandno'];
    }
    
    /**
     * Returns the question information e.g. category, topic number for any given prim
     * @param int $prim this is the question unique number
     * @return array Returns that particular prim info
     */
    public function questionInfo($prim){
        $questioninfo = $this->db->select($this->questionsTable, ['prim' => $prim], ['prim', 'dsaband', 'dsaqposition']);
        $catinfo = $this->db->select($this->dsaCategoriesTable, ['section' => $questioninfo['dsaband']]);
        
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
    public function setTime($time, $type = 'taken'){
        if($time){
            if($type == 'taken'){
                list($hours, $mins, $secs) = explode(':', $time);
                $time = gmdate('H:i:s', ($this->seconds - (($hours * 60 * 60) + ($mins * 60) + $secs)));
                $this->db->update($this->progressTable, array('time_'.$type => $time), array('user_id' => $this->getUserID(), 'test_id' => $this->getTest()));
            }
            else{
                $_SESSION['time_'.$type]['test'.$this->getTest()] = $time;
            }
        }
    }
    
    /**
     * Returns the number of seconds remaining for the current test
     * @return int Returns the number of seconds remaining for the current test
     */
    protected function getSeconds(){
        $time = $this->getTime('remaining');
        list($hours, $minutes, $seconds) = explode(':', $time);
        return (($hours * 3600) + ($minutes * 60) + $seconds);
    }
    
    /**
     * Marks the current test for the user
     * @return void Nothing is returned
     */
    protected function markTest(){
        $this->getQuestions();
        foreach($this->questions as $prim){
            if($_SESSION['test'.$this->getTest()][$this->questionNo($prim)]['status'] == 4){$type = 'correct';}
            else{$type = 'incorrect';}
             
            $dsa = $this->getDSABand($prim);
            $dsano = $this->getDSABandNo($prim);
            $this->testresults['dsa'][$dsa][$type] = (int)$this->testresults['dsa'][$dsa][$type] + 1;
            $this->testresults['dsano'][$dsano][$type] = (int)$this->testresults['dsano'][$dsano][$type] + 1;
        }
        
        $pass = true;
        foreach($this->testresults['dsano'] as $value){
            if($pass !== false && $value['correct'] < $this->passmarkPerCat){
                $pass = false;
            }
        }
        unset($this->testresults['dsano']);
        
        $this->testresults['correct'] = $this->numCorrect();
        $this->testresults['incorrect'] = ($this->numQuestions() - $this->numCorrect());
        $this->testresults['incomplete'] = $this->numIncomplete();
        $this->testresults['flagged'] = $this->numFlagged();
        $this->testresults['numquestions'] = $this->numQuestions();
        $this->testresults['percent']['correct'] = round(($this->testresults['correct'] / $this->testresults['numquestions']) * 100);
        $this->testresults['percent']['incorrect'] = round(($this->testresults['incorrect'] / $this->testresults['numquestions']) * 100);
        $this->testresults['percent']['flagged'] = round(($this->testresults['flagged'] / $this->testresults['numquestions']) * 100);
        $this->testresults['percent']['incomplete'] = round(($this->testresults['incomplete'] / $this->testresults['numquestions']) * 100);
        if($this->numCorrect() >= $this->passmark && $pass === true){
            $this->testresults['status'] = 'pass';
            $status = 1;
        }
        else{
            $this->testresults['status'] = 'fail';
            $status = 2;
        }
        $this->db->update($this->progressTable, array('status' => $status, 'results' => serialize($this->testresults), 'complete' => date('Y-m-d H:i:s'), 'totalscore' => $this->numCorrect()), array('user_id' => $this->getUserID(), 'test_id' => $this->getTest(), 'status' => 0));
    }
    
    /**
     * Returns an overview of the results to be put into a table
     * @return string Returns an overview of the results to be put into a table
     */
    protected function createOverviewResults(){
        $dsacats = $this->db->selectAll($this->dsaCategoriesTable);
        $catresults = array();
        foreach($dsacats as $i => $dsacat){
            $catresults[$i]['section'] = $dsacat['section'];
            $catresults[$i]['name'] = str_replace($dsacat['section'].'.', '', $dsacat['name']);
            $catresults[$i]['correct'] = (int)$this->testresults['dsa'][$dsacat['section']]['correct'];
            $catresults[$i]['incorrect'] = (int)$this->testresults['dsa'][$dsacat['section']]['incorrect'];
            $catresults[$i]['total'] = ((int)$this->testresults['dsa'][$dsacat['section']]['correct'] + (int)$this->testresults['dsa'][$dsacat['section']]['incorrect'] + (int)$this->testresults['dsa'][$dsacat['section']]['unattempted']);
        }
        return $catresults;
    }
}
