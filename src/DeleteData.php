<?php
namespace TheoryTest\ADI;

class DeleteData extends \TheoryTest\Car\DeleteData{
    
    protected function setTables(){
        $this->learningProgressTable = $this->config->table_adi_progress;
        $this->progressTable = $this->config->table_adi_test_progress;
    }
    
}
