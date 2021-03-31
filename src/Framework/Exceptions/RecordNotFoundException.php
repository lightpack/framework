<?php

namespace Lightpack\Exceptions;

class RecordNotFoundException extends HttpException 
{
    public function __construct() 
    {
        parent::__construct('Requested record/entity does not exists.', 404);
    }
}