<?php

namespace zipFly\Exceptions;

use zipFly\Exceptions\abstractException;

class fileException extends abstractException {

    const EXISTS        = 1;
    const NOT_EXISTS    = 2;
    const NOT_READABLE  = 3;
    const NOT_WRITEABLE = 3;


    protected $messages = [
        self::EXISTS        => ['filename'=>'The given file (%filename%) is already exists', 'The given file is already exists.'],
        self::NOT_EXISTS    => ['filename'=>'The given file (%filename%) is not exists or not readable', 'The given file is not exists or not readable.'],
        self::NOT_READABLE  => ['filename'=>'The given file (%filename%) is not readable', 'The given file is not readable.'],
        self::NOT_WRITEABLE => ['filename'=>'The given file (%filename%) is not writeable', 'The given file is not writeable.'],
    ];


    public function __construct($filename, $code = 0, $backtraceOffset = 1) {
        $this->fields['filename'] = $filename;
        parent::__construct($code, $backtraceOffset);
    }


    public function getFilename() {
        return $this->fields['filename'];
    }


}
