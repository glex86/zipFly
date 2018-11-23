<?php

namespace zipFly\Exceptions;

use zipFly\Exceptions\abstractException;

class directoryException extends abstractException {

    const NOT_EXISTS    = 1;
    const NOT_WRITEABLE = 2;


    protected $messages = [
        self::NOT_EXISTS    => 'Directory is not exists',
        self::NOT_WRITEABLE => 'Directory is not writeable',
    ];


    public function __construct(string $directory, int $code = 0, int $backtraceOffset = 1) {
        parent::__construct($code, $backtraceOffset);

        $this->fields['directory'] = $directory;
    }


    public function getDirectory() {
        return $this->fields['directory'];
    }


}
