<?php
namespace VKR\CSVBundle\TestHelpers;

class TestEntity
{
    protected $firstProperty;
    protected $secondProperty;

    public function setFirstProperty($firstProperty)
    {
        $this->firstProperty = $firstProperty;
    }

    public function getFirstProperty()
    {
        return $this->firstProperty;
    }

    public function setSecondProperty($secondProperty)
    {
        $this->secondProperty = $secondProperty;
    }

    public function getSecondProperty()
    {
        return $this->secondProperty;
    }
}
