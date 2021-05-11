<?php

namespace TheoryTest\ADI;

class RandomTest extends TheoryTest
{

    protected $testNo = 99;
    protected $scriptVar = 'adirandom';
    
    /**
     * Sets the current test name
     * @param string $name The name of the test you wish to set, if left blank will be Theory Test plus test number
     */
    protected function setTestName($name = '')
    {
        if (!empty($name)) {
            $this->testName = $name;
        } else {
            $this->testName = 'ADI Random Test';
        }
    }
    
    /**
     * Creates the test report HTML if the test has been completed
     * @param int $theorytest The test number you wish to view the report for
     * @return string Returns the HTML for the test report for the given test ID
     */
    public function createTestReport($theorytest = 99)
    {
        return parent::createTestReport(99);
    }
    
    /**
     * Picks some random questions for the ADI test
     * @return boolean If the questions have been selected and added to the database will return true else returns false
     */
    protected function chooseQuestions($testNo = 99)
    {
        $this->db->delete($this->progressTable, ['user_id' => $this->getUserID(), 'test_id' => $this->testNo]);
        $questions = $this->db->query("(SELECT `prim` FROM `".$this->questionsTable."` WHERE `dsaband` = '1' AND `includedintest` = '1' ORDER BY RAND() LIMIT 25)
UNION (SELECT `prim` FROM `".$this->questionsTable."` WHERE `dsaband` = '2a' AND `includedintest` = '1' ORDER BY RAND() LIMIT 5)
UNION (SELECT `prim` FROM `".$this->questionsTable."` WHERE `dsaband` = '2b' AND `includedintest` = '1' ORDER BY RAND() LIMIT 10)
UNION (SELECT `prim` FROM `".$this->questionsTable."` WHERE `dsaband` = '2c' AND `includedintest` = '1' ORDER BY RAND() LIMIT 5)
UNION (SELECT `prim` FROM `".$this->questionsTable."` WHERE `dsaband` = '2d' AND `includedintest` = '1' ORDER BY RAND() LIMIT 5)
UNION (SELECT `prim` FROM `".$this->questionsTable."` WHERE `dsaband` = '3a' AND `includedintest` = '1' ORDER BY RAND() LIMIT 10)
UNION (SELECT `prim` FROM `".$this->questionsTable."` WHERE `dsaband` = '3b' AND `includedintest` = '1' ORDER BY RAND() LIMIT 5)
UNION (SELECT `prim` FROM `".$this->questionsTable."` WHERE `dsaband` = '3c' AND `includedintest` = '1' ORDER BY RAND() LIMIT 10)
UNION (SELECT `prim` FROM `".$this->questionsTable."` WHERE `dsaband` = '4a' AND `includedintest` = '1' ORDER BY RAND() LIMIT 10)
UNION (SELECT `prim` FROM `".$this->questionsTable."` WHERE `dsaband` = '4b' AND `includedintest` = '1' ORDER BY RAND() LIMIT 15) ORDER BY RAND();");
         
        unset($_SESSION['test'.$this->getTest()]);
        foreach ($questions as $q => $question) {
            $this->questions[($q + 1)] = $question['prim'];
        }
        return $this->db->insert($this->progressTable, ['user_id' => $this->getUserID(), 'questions' => serialize($this->questions), 'answers' => serialize([]), 'test_id' => $this->testNo, 'started' => date('Y-m-d H:i:s'), 'status' => 0]);
    }
}
