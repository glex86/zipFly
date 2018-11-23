<?php

namespace zipFly\Exceptions;

use zipFly\Exceptions\abstractException;

class zipFlyException extends abstractException {

    const NOT_64BIT              = 1;
    const NOT_OPENED             = 2;
    const BAD_INARCHIVE_PATH     = 3;
    const LONG_INARCHIVE_PATH    = 4;
    const EXISTS_INARCHIVE_ENTRY = 5;


    protected $messages = [
        self::NOT_64BIT              => 'This class requires 64bit version of PHP',
        self::NOT_OPENED             => 'The internal ZIP file resource pointer is not opened',
        self::BAD_INARCHIVE_PATH     => 'Bad in-Archive file path given',
        self::LONG_INARCHIVE_PATH    => 'Too long in-Archive file path given',
        self::EXISTS_INARCHIVE_ENTRY => 'The in-Archive entry is already exists',
    ];


}
