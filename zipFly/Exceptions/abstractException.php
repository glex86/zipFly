<?php

namespace zipFly\Exceptions;

abstract class abstractException extends \ErrorException {

    protected $messages      = [];
    protected $fields        = [];
    protected $unusedFields  = [];
    private $backtraceOffset = -1;
    private $callerFile      = '';
    private $callerLine      = '';


    public function __construct($code = 0, $backtraceOffset = 1, $fields = []) {
        $trace                 = $this->getTrace();
        $this->backtraceOffset = min(count($trace), max(1, $backtraceOffset))-1;

        $this->callerFile = $backtraceOffset == 0 ? $this->getFile() : $trace[$this->backtraceOffset]['file'];
        $this->callerLine = $backtraceOffset == 0 ? $this->getLine() : $trace[$this->backtraceOffset]['line'];

        $traceLine = $trace[$this->backtraceOffset];
        $function = (isset($traceLine['class']) ? $traceLine['class'].$traceLine['type'] : '').$traceLine['function'].'()';

        foreach ($fields as $key => $val) {
            $this->fields[$key] = strval($val);
        }

        $this->unusedFields = $this->fields;
        parent::__construct("{$function}: '{$this->parseMessage($code)}'", $code, E_ERROR, $this->getFile(), $this->getLine());
    }

    private function parseMessage($code) {
        $message = isset($this->messages[$code]) ? $this->messages[$code] : 'Unknown internal error';

        if (!is_array($message)) {
            return $message;
        }

        $filter = function($carry, $item) {
            if (!array_key_exists($item, $this->fields)) {
                return '';
            }

            unset($this->unusedFields[$item]);
            return  str_replace('%'.$item.'%', $this->fields[$item], $carry);
        };

        foreach ($message as $needs => $value) {
            if (is_numeric($needs)) {
                return $value;
            }

            $needs  = array_filter(array_map('trim', explode(',', $needs)));
            $result = array_reduce($needs, $filter, $value);

            if (strlen($result)) {
                return $result;
            }

            $this->unusedFields = $this->fields;
        }

        return 'Unknown internal error';
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

        $result = [get_class($this).": {$this->message} in {$this->callerFile}({$this->callerLine})"];

        if (count($this->unusedFields)) {
            $result[] = "\nFIELDS: ";

            $maxLength = max(array_map('strlen', array_keys($this->unusedFields)))+2;
            foreach ($this->unusedFields as $key => $val) {
                $result[] = sprintf(" %{$maxLength}s: %s", '['.$key.']', $val);
            }
        }

        $result[] = "\nSTACK TRACE:";
        $trace    = explode("\n", $this->getTraceAsString());

        $maxTraceLength = strlen(count($trace)-1)+2;
        foreach ($trace as $nr => $line) {
            $sNr = $nr - $this->backtraceOffset;
            $sNr = $sNr == 0 ? str_repeat(' ', $maxTraceLength-2).'#0' : sprintf("%+{$maxTraceLength}d", $sNr);
            $result[] = preg_replace('~^#\d+\s~', " {$sNr}: ", $line);
        }

        return implode("\n", $result);
    }


}
