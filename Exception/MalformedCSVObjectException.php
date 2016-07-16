<?php
namespace VKR\CSVBundle\Exception;

class MalformedCSVObjectException extends \Exception
{
    public function __construct()
    {
        $message = 'CSV data must be an array or a traversable object';
        parent::__construct($message);
    }
}
