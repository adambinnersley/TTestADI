<?php
namespace TheoryTest\ADI;

class DeleteData extends \TheoryTest\Car\DeleteData{
    public $learningProgressTable = 'adi_progress';
    public $progressTable = 'adi_test_progress';
}
