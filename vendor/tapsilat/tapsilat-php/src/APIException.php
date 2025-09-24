<?php
namespace Tapsilat;

use Exception;

class APIException extends Exception
{
    public $statusCode;
    public $code;
    public $error;

    public function __construct($statusCode, $code, $error)
    {
        parent::__construct("Tapsilat API Error\nstatus_code:{$statusCode}\ncode:{$code}\nerror:{$error}", $code);
        $this->statusCode = $statusCode;
        $this->code = $code;
        $this->error = $error;
    }
}
