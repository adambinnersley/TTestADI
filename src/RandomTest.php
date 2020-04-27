<?php

namespace TheoryTest\ADI;

class RandomTest extends TheoryTest{

    protected $testNo = 100;
    protected $scriptVar = 'adirandom';
    
    /**
     * Sets the current test name
     * @param string $name This should be the name of the test you wish to set it to if left blank will just be Theory Test plus test number
     */
    protected function setTestName($name = ''){
        if(!empty($name)){
            $this->testName = $name;
        }
        else{
            $this->testName = 'ADI Random Test';
        }
    }
    
    /**
     * Picks some random questions for the ADI test
     * @return boolean If the questions have been selected and added to the database will return true else returns false
     */
    protected function chooseQuestions($testNo = 100){  
        $this->testNo = intval($testNo);
        $this->db->delete($this->progressTable, ['user_id' => $this->getUserID(), 'test_id' => $this->testNo]);
        $questions = $this->db->query("(SELECT `prim` FROM `".$this->questionsTable."` WHERE `dsaband` = '1' AND `includedintest` = '1' LIMIT 25)
UNION (SELECT `prim` FROM `".$this->questionsTable."` WHERE `dsaband` = '2a' AND `includedintest` = '1' LIMIT 5)
UNION (SELECT `prim` FROM `".$this->questionsTable."` WHERE `dsaband` = '2b' AND `includedintest` = '1' LIMIT 10)
UNION (SELECT `prim` FROM `".$this->questionsTable."` WHERE `dsaband` = '2c' AND `includedintest` = '1' LIMIT 5)
UNION (SELECT `prim` FROM `".$this->questionsTable."` WHERE `dsaband` = '2d' AND `includedintest` = '1' LIMIT 5)
UNION (SELECT `prim` FROM `".$this->questionsTable."` WHERE `dsaband` = '3a' AND `includedintest` = '1' LIMIT 10)
UNION (SELECT `prim` FROM `".$this->questionsTable."` WHERE `dsaband` = '3b' AND `includedintest` = '1' LIMIT 5)
UNION (SELECT `prim` FROM `".$this->questionsTable."` WHERE `dsaband` = '3c' AND `includedintest` = '1' LIMIT 10)
UNION (SELECT `prim` FROM `".$this->questionsTable."` WHERE `dsaband` = '4a' AND `includedintest` = '1' LIMIT 10)
UNION (SELECT `prim` FROM `".$this->questionsTable."` WHERE `dsaband` = '4b' AND `includedintest` = '1' LIMIT 15) ORDER BY RAND();");
         
        unset($_SESSION['test'.$this->getTest()]);
        foreach($questions as $q => $question){
            $this->questions[($q + 1)] = $question['prim'];
        }
        return $this->db->insert($this->progressTable, ['user_id' => $this->getUserID(), 'questions' => serialize($this->questions), 'answers' => serialize([]), 'test_id' => $this->testNo, 'started' => date('Y-m-d H:i:s'), 'status' => 0, 'type' => $this->getTestType()]);
    }
}
