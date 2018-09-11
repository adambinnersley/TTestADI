<?php

namespace TheoryTest\ADI;

class Review extends \TheoryTest\Car\Review{
    
    public $where = array();
    
    public $noOfTests = 6;
    
    protected $testType = 'ADI';
    
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
     * Returns the table names for the learning section
     * @return array
     */
    public function getSectionTables(){
        return [
            ['table' => 'adi_modules', 'name' => 'ADI Modules', 'section' => 'hc', 'sectionNo' => 'ldclessonno'],
            ['table' => 'adi_publications', 'name' => 'ADI Publication', 'section' => 'dsa', 'sectionNo' => 'hcsection'],
            ['table' => 'adi_dsa_sections', 'name' => 'Key Test Questions', 'section' => 'l2d', 'sectionNo' => 'dsaband', 'keyquestion' => true]
        ];
    }
    
    /**
     * Returns the HTML Table for the review section 
     * @return string Returns the HTML code for the learning review section
     */
    public function buildTables(){
        $this->getUserAnswers();
        foreach ($this->getSectionTables() as $i => $tables){
            if($tables['keyquestion'] === true){$this->where = ['includedintest' => 1];}
            if(is_array($tables)){
                $this->layout->assign('table', $this->buildReviewTable($tables['table'], $tables['sectionNo'], $tables['name'], $tables['section']), true);
                $this->layout->assign('table'.($i + 1).'name', $tables['name'], true);
                $this->layout->assign($tables['section'].'section', $this->layout->fetch('table-learning.tpl'), true);
            }
            elseif($tables === true){
                $this->layout->assign('cases', $this->reviewCaseStudy(), true);
                $this->layout->assign('reviewsection', $this->layout->fetch('table-case.tpl'), true);
            }
            if($tables['keyquestion'] === true){$this->where = [];}
        }
        return $this->layout->fetch('study.tpl');
    }
}
