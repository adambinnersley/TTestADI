<?php

namespace TheoryTest\ADI;

use DBAL\Database;
use Configuration\Config;
use Smarty;

class LearnTest extends \TheoryTest\Car\LearnTest
{

    protected $userType = 'adi';
    
    protected $scriptVar = 'adilearn';
    
    protected $categories = ['l2d' => 'dsaband', 'dvsa' => 'hcsection', 'hc' => 'ldclessonno'];
    protected $sortBy = ['l2d' => 'dsaqposition', 'dvsa' => 'hcqno', 'hc' => 'ldcqno'];
    protected $key = ['l2d' => 1, 'dvsa' => ['>=', '0'], 'hc' => ['>=', '0']];
    
    /**
     * Set up all of the components needed to create a Theory Test
     * @param Database $db This needs to be an instance of the database class
     * @param Config $config This needs to be an instance of the Configuration class
     * @param Smarty $layout This needs to be an instance of the Smarty Template class
     * @param object $user This should be the user class used
     * @param int|false $userID If you want to emulate a user set the user ID here
     * @param string|false $templateDir If you want to change the template location set this location here
     * @param string $theme This is the template folder to look at currently either 'bootstrap' or 'bootstrap4'
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
        $this->questionsTable = $this->config->table_adi_questions;
        $this->learningProgressTable = $this->config->table_adi_progress;
        $this->progressTable = $this->config->table_adi_test_progress;
        $this->dvsaCatTable = $this->config->table_adi_dvsa_sections;
    }
    
    /**
     * Creates a new test for the ADI Theory Test
     * @param int $sectionNo This should be the section number for the test
     */
    public function createNewTest($sectionNo = '1', $type = 'dvsa')
    {
        $this->clearSettings();
        $this->chooseStudyQuestions($sectionNo, $type);
        $this->setTest($type.$sectionNo);
        if ($type == 'l2d') {
            $title = 'Key Test Questions';
            $table = 'dsa_sections';
        } elseif ($type == 'dvsa') {
            $title = 'Publication';
            $table = 'publications';
        } else {
            $title = 'Module';
            $table = 'modules';
        }
        $sectionInfo = $this->getSectionInfo('adi_'.strtolower($table), $sectionNo);
        if ((!isset($sectionInfo['free']) || $sectionInfo['free'] == 0) && method_exists($this->user, 'checkUserAccess')) {
            $this->user->checkUserAccess(null, $this->userType);
        }
        $this->setTestName('ADI '.$title.' '.$sectionNo);
        return $this->buildTest();
    }
    
    /**
     * Gets the questions for the current section test
     * @param int $sectionNo This should be the section number for the test
     */
    protected function chooseStudyQuestions($sectionNo, $type = 'dvsa')
    {
        $this->testInfo['category'] = $this->categories[strtolower($type)];
        $this->testInfo['sort'] = $this->sortBy[strtolower($type)];
        $this->testInfo['key'] = $this->key[strtolower($type)];
        $this->testInfo['section'] = $sectionNo;
        setcookie('testinfo', serialize($this->testInfo), time() + 31536000, '/');
    }
    
    /**
     * Returns the question data for the given prim number
     * @param int $prim Should be the question prim number
     * @return array|boolean Returns question data as array if data exists else returns false
     */
    protected function getQuestionData($prim)
    {
        return $this->db->select($this->questionsTable, ['prim' => $prim], ['prim', 'question', 'mark', 'option1', 'option2', 'option3', 'option4', 'option5', 'option6', 'answerletters', 'dsaimageid', 'format', 'dsaexplanation']);
    }
    
    /**
     * Returns the number of questions in the current section
     * @return int This should be the number of questions for the section
     */
    public function numQuestions()
    {
        return count($this->db->selectAll($this->questionsTable, [$this->testInfo['category'] => $this->testInfo['section'], 'includedintest' => $this->testInfo['key']], ['prim']));
    }
    
    /**
     * Returns the current question number
     * @return int Returns the current question number
     */
    protected function currentQuestion()
    {
        if (!isset($this->current)) {
            $currentnum = $this->db->select($this->questionsTable, ['prim' => $this->currentPrim, $this->testInfo['category'] => $this->testInfo['section'], 'includedintest' => $this->testInfo['key']], [$this->testInfo['sort']]);
            $this->current = $currentnum[$this->testInfo['sort']];
        }
        return $this->current;
    }
    
    /**
     * Returns the Previous question HTML for the current question
     * @return string Returns the previous question HTML with the correct prim number for the previous question
     */
    protected function prevQuestion()
    {
        if ($_COOKIE['skipCorrect'] == 1) {
            $prim = $this->getIncomplete('prev');
        } elseif ($this->currentQuestion() != 1 && $this->testInfo['category']) {
            $prim = $this->db->fetchColumn($this->questionsTable, [$this->testInfo['sort'] => ['<', $this->currentQuestion()], $this->testInfo['category'] => $this->testInfo['section'], 'includedintest' => $this->testInfo['key']], ['prim'], 0, [$this->testInfo['sort'] => 'DESC']);
        } else {
            $prim = $this->getLastQuestion();
        }
        return ['id' => $prim, 'test' => 'Previous', 'icon' => 'angle-left'];
    }
    
    /**
     * Returns the Next question HTML for the current question
     * @return string Returns the next question HTML with the correct prim number for the next question
     */
    protected function nextQuestion()
    {
        if ($_COOKIE['skipCorrect'] == 1) {
            $prim = $this->getIncomplete();
        } elseif ($this->currentQuestion() < $this->numQuestions() && $this->testInfo['category']) {
            $prim = $this->db->fetchColumn($this->questionsTable, [$this->testInfo['sort'] => ['>', $this->currentQuestion()], $this->testInfo['category'] => $this->testInfo['section'], 'includedintest' => $this->testInfo['key']], ['prim'], 0, [$this->testInfo['sort'] => 'ASC']);
        } else {
            $prim = $this->getFirstQuestion();
        }
        return ['id' => $prim, 'test' => 'Next', 'icon' => 'angle-right'];
    }
    
    /**
     * Returns the prim number for the next or previous incomplete question
     * @param string $nextOrPrev Should be either next of previous for which way you want the next question to be
     * @return int|string Returns the prim number for the next or previous question or none if no more incomplete questions exist
     */
    protected function getIncomplete($nextOrPrev = 'next')
    {
        if (strtolower($nextOrPrev) == 'next') {
            $dir = '>';
            $sort = 'ASC';
            $start = '0';
        } else {
            $dir = '<';
            $sort = 'DESC';
            $start = '100000';
        }
        
        $searchCurrentQuestion = $this->findNextQuestion($dir, $this->currentQuestion(), $sort);
        if ($searchCurrentQuestion !== false) {
            return $searchCurrentQuestion;
        }
        $searchStart = $this->findNextQuestion($dir, $start, $sort);
        if ($searchStart !== false) {
            return $searchStart;
        }
        return 'none';
    }
    
    /**
     * Finds the next question from the given parameters
     * @param string $dir This should be the direction to search for the next question '>' or '<'
     * @param int $start The start number to search for the next question
     * @param string $sort The sort order for the next question ASC or DESC
     * @return int|false Will return the prim number for the next question
     */
    protected function findNextQuestion($dir, $start, $sort)
    {
        foreach ($this->db->selectAll($this->questionsTable, [$this->testInfo['sort'] => [$dir, $start], $this->testInfo['category'] => $this->testInfo['section'], 'includedintest' => $this->testInfo['key']], ['prim'], [$this->testInfo['sort'] => $sort]) as $question) {
            if ($this->useranswers[$question['prim']]['status'] <= 1) {
                return $question['prim'];
            }
        }
        return false;
    }
    
    /**
     * Returns the first questions prim number for the current section
     * @return int Returns the prim number of the first question in the current section
     */
    protected function getFirstQuestion()
    {
        $question = $this->db->select($this->questionsTable, [$this->testInfo['sort'] => '1', $this->testInfo['category'] => $this->testInfo['section'], 'includedintest' => $this->testInfo['key']], ['prim']);
        return $question['prim'];
    }
    
    /**
     * Returns the prim number for the last question
     * @return int Returns the prim number for the last question
     */
    protected function getLastQuestion()
    {
        $question = $this->db->select($this->questionsTable, [$this->testInfo['category'] => $this->testInfo['section'], 'includedintest' => $this->testInfo['key']], ['prim'], [$this->testInfo['sort'] => 'DESC']);
        return $question['prim'];
    }
}
