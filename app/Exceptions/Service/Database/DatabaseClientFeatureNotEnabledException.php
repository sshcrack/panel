<?php

namespace Kriegerhost\Exceptions\Service\Database;

use Kriegerhost\Exceptions\KriegerhostException;

class DatabaseClientFeatureNotEnabledException extends KriegerhostException
{
    public function __construct()
    {
        parent::__construct('Client database creation is not enabled in this Panel.');
    }
}
