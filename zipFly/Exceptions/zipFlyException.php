<?php

namespace zipFly\Exceptions;

class zipFlyException extends \RuntimeException {
    const NOT_INITIALIZED = 1;

    public function __construct($message, int $code = 0, \Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    public function getCodeAsString(int $code) {
        switch ($code) {
            case self::NOT_INITIALIZED:
                return 'not initialized';
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