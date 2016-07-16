<?php
namespace VKR\CSVBundle\Exception;

class MalformedCSVFieldsException extends \Exception
{
    public function __construct($message)
    {
        parent::__construct($message);
    }
}
