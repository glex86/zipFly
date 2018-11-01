<?php

namespace zipFly\Exceptions;

class directoryException extends \RuntimeException {
    private $directory = '';

    public function __construct($message, string $directory, int $code = 0, \Exception $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->directory = $directory;
    }

    public function getDirectory() {
        return $this->directory;
    }
}