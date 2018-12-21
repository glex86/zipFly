<?php
/**
 * This file is a part of G-Lex's zipFly compression library
 */

namespace zipFly\parts;

/**
 * Zip base class
 */
abstract class base extends constants {

    use headers;

    /** @var int Current offset in the output stream */
    protected static $localStreamOffset = 0;

    /** @var bool Debug mode */
    protected static $isDebugMode = false;

    /** @var bool ZIP64 archive format or the standard zip format */
    protected static $isZip64 = true;

    /** @var bool Streamed ZIP format or the standard zip format */
    protected static $isZipStream = false;

    /** @var resource Output file pointer resource */
    protected static $fileHandle = false;


    /**
     * Set debug mode
     * @param bool $mode
     */
    public function setDebugMode($mode) {
        self::$isDebugMode = $mode;

        if (self::$isDebugMode) {
            \zipFly\parts\debugger::setZip64(self::$isZip64);
        }
    }


    /**
     * Is in debug mode or not
     * @return bool
     */
    public function getDebugMode() {
        return self::$isDebugMode;
    }


    /**
     * The current archive format is ZIP64 or the standard ZIP format
     * @return bool
     */
    public function getZip64Mode() {
        return self::$isZip64;
    }


    /**
     * The current archive format is ZipStream or the standard ZIP format
     * @return bool
     */
    public function getZipStreamMode() {
        return self::$isZipStream;
    }


    /**
     * Sanitize the given file path
     * @param string $path
     * @return string
     */
    protected static function cleanPath($path) {
        $path = trim(str_replace('\\', '/', $path), '/');
        $path = explode('/', $path);

        $newpath = array();
        foreach ($path as $p) {
            if ($p === '' || $p === '.') {
                continue;
            }
            if ($p === '..') {
                array_pop($newpath);
                continue;
            }
            array_push($newpath, $p);
        }
        return trim(implode('/', $newpath), '/');
    }


}
