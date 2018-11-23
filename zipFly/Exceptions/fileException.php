<?php

namespace zipFly\Exceptions;

use zipFly\Exceptions\abstractException;

class fileException extends abstractException {

    const EXISTS        = 1;
    const NOT_EXISTS    = 2;
    const NOT_READABLE  = 3;
    const NOT_WRITEABLE = 3;


    protected $messages = [
        self::EXISTS        => 'File is already exists',
        self::NOT_EXISTS    => 'File is not exists',
        self::NOT_READABLE  => 'File is not readable',
        self::NOT_WRITEABLE => 'File is not writeable',
    ];


    public function __construct(string $filename, int $code = 0, int $backtraceOffset = 1) {
        parent::__construct($code, $backtraceOffset);

        $this->fields['filename'] = $filename;
    }


    public function getFilename() {
        return $this->fields['filename'];
    }


}
