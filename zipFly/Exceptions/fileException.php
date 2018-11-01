<?php

namespace zipFly\Exceptions;

class fileException extends \RuntimeException {
    const FILE_EXISTS = 1;
    const FILE_NOT_EXISTS = 2;
    const FILE_NOT_READABLE = 3;

    private $filename = '';

    public function __construct($message, string $filename, int $code = 0, \Exception $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->filename = $filename;
    }

    public function getFilename() {
        return $this->filename;
    }

    public function getCodeAsString(int $code) {
        switch ($code) {
            case self::FILE_EXISTS:
                return 'exists';
                break;

            case self::FILE_NOT_EXISTS:
                return 'not-exists';
                break;

            case self::FILE_NOT_READABLE:
                return 'not-exists';
                break;

            default:
                return 'unknown';
                break;
        }
    }

    public function getReason() {
        return $this->getCodeAsString($this->code);
    }
}