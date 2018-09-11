<?php
namespace TheoryTest\ADI;

class DeleteData extends \TheoryTest\Car\DeleteData{
    
    /**
     * Set the database tables
     */
    protected function setTables(){
        $this->learningProgressTable = $this->config->table_adi_progress;
        $this->progressTable = $this->config->table_adi_test_progress;
    }
    
}
