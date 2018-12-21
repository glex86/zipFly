<?php

namespace zipFly\Exceptions;

use zipFly\Exceptions\abstractException;

class directoryException extends abstractException {

    const NOT_EXISTS    = 1;
    const NOT_WRITEABLE = 2;


    protected $messages = [
        self::NOT_EXISTS    => ['directory'=>'The given directory (%directory%) is not exists', 'The given directory is not exists'],
        self::NOT_WRITEABLE => ['directory'=>'The given directory (%directory%) is not writeable', 'The given directory is not writeable'],
    ];


    public function __construct($directory, $code = 0, $backtraceOffset = 1) {
        $this->fields['directory'] = $directory;
        parent::__construct($code, $backtraceOffset);
    }


    public function getDirectory() {
        return $this->fields['directory'];
    }


}
