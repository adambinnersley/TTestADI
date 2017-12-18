<?php

namespace TheoryTest\ADI;

class Review extends \TheoryTest\Car\Review{
    
    public $where = array();
    
    public $noOfTests = 6;
    
    protected $questionsTable = 'adi_questions';
    protected $DSACatTable = 'adi_dsa_sections';
    protected $progressTable = 'adi_progress';
    protected $testProgressTable = 'adi_test_progress';
    
    protected $testType = 'ADI';
    
    public function getSectionTables(){
        return array(
            array('table' => 'adi_modules', 'name' => 'ADI Modules', 'section' => 'l2d', 'sectionNo' => 'ldclessonno'),
            array('table' => 'adi_publications', 'name' => 'ADI Publication', 'section' => 'hc', 'sectionNo' => 'hcsection'),
            array('table' => 'adi_dsa_sections', 'name' => 'Key Test Questions', 'section' => 'dsa', 'sectionNo' => 'dsaband', 'keyquestion' => true)
        );
    }
    
    /**
     * Returns the HTML Table for the review section 
     * @return string Returns the HTML code for the learning review section
     */
    public function buildTables(){
        $this->getUserAnswers();
        foreach ($this->getSectionTables() as $i => $tables){
            if($tables['keyquestion'] === true){$this->where = array('includedintest' => 1);}
            if(is_array($tables)){
                self::$layout->assign('table', $this->buildReviewTable($tables['table'], $tables['sectionNo'], $tables['name'], $tables['section']), true);
                self::$layout->assign('table'.($i + 1).'name', $tables['name'], true);
                self::$layout->assign($tables['section'].'section', self::$layout->fetch('table-learning.tpl'), true);
                
            }
            elseif($tables === true){
                self::$layout->assign('cases', $this->reviewCaseStudy(), true);
                self::$layout->assign('reviewsection', self::$layout->fetch('table-case.tpl'), true);
            }
            if($tables['keyquestion'] === true){$this->where = array();}
        }
        return self::$layout->fetch('study.tpl');
    }
}
