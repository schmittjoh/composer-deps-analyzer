<?php

namespace JMS\Composer\Exception;

class MissingLockFileException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('You need to run "composer install --dev" or commit your composer.lock file before analyzing dependencies.');
    }
}