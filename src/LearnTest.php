<?php

namespace TheoryTest\ADI;

use DBAL\Database;
use Smarty;

class LearnTest extends \TheoryTest\Car\LearnTest{
    public $questionsTable = 'adi_questions';
    public $progressTable = 'adi_progress';
    public $dsaCategoriesTable = 'adi_dsa_sections';
    
    protected $audioLocation = '/audio/adi';
    
    protected $userType = 'adi';
    
    protected $categories = array('dsa' => 'dsaband', 'hc' => 'hcsection', 'l2d' => 'ldclessonno');
    protected $sortBy = array('dsa' => 'dsaqposition', 'hc' => 'hcqno', 'l2d' => 'ldcqno');
    protected $key = array('dsa' => 1, 'hc' => array('>=', '0'), 'l2d' => array('>=', '0'));
    
    /**
     * Set up all of the components needed to create a Theory Test
     * @param Database $db This should be an instance of Database
     * @param Smarty $layout This needs to be an instance of Smarty Templating
     * @param object $user This should be and instance if the User Class
     * @param false|int $userID If you wish to emulate a user set this value to the users ID else set to false
     */
    public function __construct(Database $db, Smarty $layout, $user, $userID = false) {
        parent::__construct($db, $layout, $user, $userID);
        $this->setImagePath(ROOT.DS.'images'.DS.'adi'.DS);
    }
    
    /**
     * Creates a new test for the ADI Theory Test
     * @param int $sectionNo This should be the section number for the test
     */
    public function createNewTest($sectionNo = '1', $type = 'dsa'){
        $this->clearSettings();
        $this->chooseStudyQuestions($sectionNo, $type);
        $this->setTest($sectionNo, $type);
        if($type == 'dsa'){$title = 'Key Test Questions'; $table = 'dsa_sections';}
        elseif($type == 'hc'){$title = 'Publication'; $table = 'publications';}
        else{$title = 'Module'; $table = 'modules';}
        $learnName = self::$db->select('adi_'.strtolower($table), array('section' => $sectionNo), array('free'));
        if($learnName['free'] == 0 && method_exists(self::$user, 'checkUserAccess')){self::$user->checkUserAccess(NULL, $this->userType);}
        $this->setTestName('ADI '.$title.' '.$sectionNo);
        return $this->buildTest();
    }
    
    /**
     * Gets the questions for the current section test
     * @param int $sectionNo This should be the section number for the test
     */
    protected function chooseStudyQuestions($sectionNo, $type = 'dsa') {
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
    protected function getQuestionData($prim){
        return self::$db->select($this->questionsTable, array('prim' => $prim), array('prim', 'question', 'mark', 'option1', 'option2', 'option3', 'option4', 'option5', 'option6', 'answerletters', 'dsaimageid', 'format', 'dsaexplanation'));
    }
    
    /**
     * Returns the number of questions in the current section
     * @return int This should be the number of questions for the section
     */
    public function numQuestions(){
        return count(self::$db->selectAll($this->questionsTable, array($this->testInfo['category'] => $this->testInfo['section'], 'includedintest' => $this->testInfo['key']), array('prim')));
    }
    
    /**
     * Returns the current question number
     * @param int $prim This should be the current questions unique prim number
     * @return int Returns the current question number
     */
    protected function currentQuestion(){
        if(!isset($this->current)){
            $currentnum = self::$db->select($this->questionsTable, array('prim' => $this->currentPrim, $this->testInfo['category'] => $this->testInfo['section'], 'includedintest' => $this->testInfo['key']), array($this->testInfo['sort']));
            $this->current = $currentnum[$this->testInfo['sort']];
        }
        return $this->current;
    }
    
    /**
     * Returns the Previous question HTML for the current question
     * @return string Returns the previous question HTML with the correct prim number for the previous question
     */
    protected function prevQuestion(){
        if($_COOKIE['skipCorrect'] == 1){$prim = $this->getIncomplete('prev');}
        elseif($this->currentQuestion() != 1 && $this->testInfo['category']){
            $prim = self::$db->fetchColumn($this->questionsTable, array($this->testInfo['sort'] => array('<', $this->currentQuestion()), $this->testInfo['category'] => $this->testInfo['section'], 'includedintest' => $this->testInfo['key']), array('prim'), 0, array($this->testInfo['sort'] => 'DESC'));
        }
        else{$prim = $this->getLastQuestion();}
        return '<div class="prevquestion btn btn-theory" id="'.$prim.'"><span class="fa fa-angle-left fa-fw"></span><span class="hidden-xs"> Previous</span></div>';
    }
    
    /**
     * Returns the Next question HTML for the current question
     * @return string Returns the next question HTML with the correct prim number for the next question
     */
    protected function nextQuestion(){
        if($_COOKIE['skipCorrect'] == 1){$prim = $this->getIncomplete();}
        elseif($this->currentQuestion() < $this->numQuestions() && $this->testInfo['category']){
            $prim = self::$db->fetchColumn($this->questionsTable, array($this->testInfo['sort'] => array('>', $this->currentQuestion()), $this->testInfo['category'] => $this->testInfo['section'], 'includedintest' => $this->testInfo['key']), array('prim'), 0, array($this->testInfo['sort'] => 'ASC'));
        }
        else{$prim = $this->getFirstQuestion();}
        return '<div class="nextquestion btn btn-theory" id="'.$prim.'"><span class="fa fa-angle-right fa-fw"></span><span class="hidden-xs"> Next</span></div>';
    }
    
    /**
     * Returns the prim number for the next or previous incomplete question
     * @param string $nextOrPrev Should be either next of previous for which way you want the next question to be
     * @return int|string Returns the prim number for the next or previous question or none if no more incomplete questions exist
     */
    protected function getIncomplete($nextOrPrev = 'next'){
        if(strtolower($nextOrPrev) == 'next'){$dir = '>'; $sort = 'ASC'; $start = '0';}
        else{$dir = '<'; $sort = 'DESC'; $start = '100000';}
        
        $questions = self::$db->selectAll($this->questionsTable, array($this->testInfo['sort'] => array($dir, $this->currentQuestion()), $this->testInfo['category'] => $this->testInfo['section'], 'includedintest' => $this->testInfo['key']), array('prim'), array($this->testInfo['sort'] => $sort));
        foreach($questions as $question){
            if($this->useranswers[$question['prim']]['status'] <= 1){
                return $question['prim'];
            }
        }
        
        $questions = self::$db->selectAll($this->questionsTable, array($this->testInfo['sort'] => array($dir, $start), $this->testInfo['category'] => $this->testInfo['section'], 'includedintest' => $this->testInfo['key']), array('prim'), array($this->testInfo['sort'] => $sort));
        foreach($questions as $question){
            if($this->useranswers[$question['prim']]['status'] <= 1){
                return $question['prim'];
            }
        }
        return 'none';
    }
    
    /**
     * Returns the first questions prim number for the current section
     * @return int Returns the prim number of the first question in the current section
     */
    protected function getFirstQuestion(){
        $question = self::$db->select($this->questionsTable, array($this->testInfo['sort'] => '1', $this->testInfo['category'] => $this->testInfo['section'], 'includedintest' => $this->testInfo['key']), array('prim'));
        return $question['prim'];
    }
    
    /**
     * Returns the prim number for the last question
     * @return int Returns the prim number for the last question
     */
    protected function getLastQuestion(){
        $question = self::$db->select($this->questionsTable, array($this->testInfo['category'] => $this->testInfo['section'], 'includedintest' => $this->testInfo['key']), array('prim'), array($this->testInfo['sort'] => 'DESC'));
        return $question['prim'];
    }
    
    /**
     * Returns any related information about the current question
     * @param string $explanation This should be the DSA explanation for the database as it has already been retrieved
     * @param int $prim This should be the questions unique prim number
     * @return string Should return any related question information in a tabbed format
     */
    public function dsaExplanation($explanation, $prim){
        $settings = $this->checkSettings();
        if($settings['hint'] == 'on'){$class = ' visible';}
        return '<div class="col-xs-12 showhint'.$class.'">
<ul class="nav nav-tabs">
<li class="active"><a href="#tab-1" aria-controls="profile" role="tab" data-toggle="tab">Highway Code +</a></li>
<li><a href="#tab-2" aria-controls="profile" role="tab" data-toggle="tab">DVSA Advice</a></li>
</ul>
<div class="tab-content">
<div role="tabpanel" class="tab-pane active" id="tab-1">'.$this->highwayCodePlus($prim).'</div>
<div role="tabpanel" class="tab-pane" id="tab-2">'.$this->addAudio($prim, 'DSA').$explanation.'</div>
</div>
</div>';
    }
}