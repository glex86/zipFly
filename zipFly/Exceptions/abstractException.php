<?php

namespace zipFly\Exceptions;

abstract class abstractException extends \ErrorException {

    protected $messages      = [];
    protected $fields        = [];
    private $backtraceOffset = -1;
    private $callerFile      = '';
    private $callerLine      = '';


    public function __construct(int $code = 0, int $backtraceOffset = 1, $fields = []) {
        $message = isset($this->messages[$code]) ? $this->messages[$code] : 'Unknown internal error';

        $trace                 = $this->getTrace();
        $this->backtraceOffset = min(count($trace), max(0, $backtraceOffset));

        $this->callerFile = $this->backtraceOffset == 0 ? $this->getFile() : $trace[$this->backtraceOffset - 1]['file'];
        $this->callerLine = $this->backtraceOffset == 0 ? $this->getLine() : $trace[$this->backtraceOffset - 1]['line'];

        foreach ($fields as $key => $val) {
            $this->fields[$key] = strval($val);
        }

        parent::__construct($message, $code, E_ERROR, $this->getFile(), $this->getLine());
    }


    function getExceptionTraceAsString() {
        $trace = explode("\n", $this->getTraceAsString());

        if ($this->backtraceOffset > 1) {
            for ($i = 0; $i < $this->backtraceOffset - 1; $i++) {
                array_shift($trace);
            }
        }

        foreach ($trace as $nr => &$line) {
            $line = preg_replace('~^#\d+\s~', '#'.$nr, $line);
        }

        return $trace;
    }


    public function __toString() {
        if (defined('GLEX_DEFAULT_EXCEPTION_STRING') && GLEX_DEFAULT_EXCEPTION_STRING == true) {
            return parent::__toString();
        }

        $result = [get_class($this)." '{$this->message}' in {$this->callerFile}({$this->callerLine})"];

        if (count($this->fields)) {
            $result[] = "FIELDS:";

            foreach ($this->fields as $key => $val) {
                $result[] = sprintf(" - %s: %s", $key, $val);
            }
        }

        $result[] = "STACK TRACE:";
        $trace    = explode("\n", $this->getTraceAsString());
        if ($this->backtraceOffset > 1) {
            for ($i = 0; $i < $this->backtraceOffset - 1; $i++) {
                array_shift($trace);
            }
        }

        foreach ($trace as $nr => $line) {
            $result[] = preg_replace('~^#\d+\s~', " - {$nr}: ", $line);
        }

        return implode("\n", $result);
    }


}
