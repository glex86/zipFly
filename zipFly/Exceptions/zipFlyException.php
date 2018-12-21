<?php

namespace zipFly\Exceptions;

use zipFly\Exceptions\abstractException;

class zipFlyException extends abstractException {

    const NOT_64BIT              = 1;
    const NOT_OPENED             = 11;
    const ALREADY_OPEN           = 12;
    const BAD_INARCHIVE_PATH     = 21;
    const LONG_INARCHIVE_PATH    = 22;
    const EXISTS_INARCHIVE_ENTRY = 23;
    const STREAM_EXPECTED        = 31;
    const STREAM_NOT_READABLE    = 32;
    const STREAM_NOT_WRITEABLE   = 33;
    const STREAM_NOT_SEEKABLE    = 34;


    protected $messages = [
        self::NOT_64BIT              => 'This class requires 64bit version of PHP',
        self::NOT_OPENED             => 'The internal ZIP file resource pointer is not opened',
        self::ALREADY_OPEN           => 'You can not set archive features after the archive created',
        self::BAD_INARCHIVE_PATH     => ['path'=>'The given in-Archive file path (%path%) is not valid', 'The given in-Archive file path is not valid'],
        self::LONG_INARCHIVE_PATH    => ['path'=>'The given in-Archive file path (%path%) is too long', 'The given in-Archive file path is too long'],
        self::EXISTS_INARCHIVE_ENTRY => ['path'=>'The given in-Archive file path (%path%) is already exists', 'The given in-Archive file path is already exists'],
        self::STREAM_EXPECTED        => ['param,type'=>'Expects parameter %param% to be stream resource, %type% given', 'Expects parameter to be stream resource'],
        self::STREAM_NOT_READABLE    => 'The given stream resource is not readable',
        self::STREAM_NOT_WRITEABLE   => 'The given stream resource is not writeable',
        self::STREAM_NOT_SEEKABLE    => 'The given stream resource is not seekable. Try using Streamable ZIP format.',
    ];


}
