<?php
/**
 * This file is a part of G-Lex's zipFly compression library
 */

namespace zipFly;

use zipFly\parts\entry;

class zipFly64 {

    private $filename   = null;
    private $fileHandle = null;
    private $entries    = [];
    private $offset     = 0;
    private $isOpen     = false;


    use parts\headers;

    /**
     * Create new zipFly64 object
     * If you specify the filename, it will create it
     * @param string $filename
     */
    public function __construct(string $filename = null) {
        if (PHP_INT_SIZE !== 8) {
            throw new \Exception('64bit required');
        }

        if (!is_null($filename)) {
            $this->create($filename);
        }
    }


    /**
     * Release all resources
     */
    public function __destruct() {
        if ($this->isOpen) {
            $this->close();
        }
    }


    /**
     * Create new ZIP file archive
     * If the $overwrite argument false and the destination file is exists the function throws an exception
     * @param string $filename Name of the ZIP file
     * @param bool $overwrite Overwrite the destination
     * @throws Exceptions\directoryException
     * @throws Exceptions\fileException
     */
    public function create(string $filename, bool $overwrite = true) {
        if ($this->isOpen) {
            $this->close();
        }

        $directory = dirname($filename);

        if (!file_exists($directory)) {
            throw new Exceptions\directoryException('Destination directory is not exists', $directory);
        }

        if (!is_writable($directory)) {
            throw new Exceptions\directoryException('Destination directory is not writeable', $directory);
        }

        if (is_readable($filename)) {
            if (!$overwrite) {
                throw new Exceptions\fileException('Destination file is exists', $filename, Exceptions\fileException::FILE_EXISTS);
            }

            unlink($filename);
        }

        $this->filename   = $filename;
        $this->fileHandle = fopen($this->filename, 'w');
        $this->offset     = 0;
        $this->entries    = [];
        $this->isOpen     = true;
    }


    private static function cleanPath($path) {
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


    /**
     * Add file to the ZIP archive
     * It compresses the file on the fly
     * @param string $localfile Local file name and path to be added
     * @param string $inArchiveFile Destination name and path in the ZIP file
     * @param int $compressionMethod
     * @throws Exceptions\zipFlyException
     * @throws Exceptions\fileException
     * @throws \Exception
     */
    public function addFile(string $localfile, string $inArchiveFile, int $compressionMethod = null) {
        if (!$this->isOpen) {
            throw new Exceptions\zipFlyException('The Zip file is not initialized', Exceptions\zipFlyException::NOT_INITIALIZED);
        }

        if (!is_readable($localfile)) {
            throw new Exceptions\fileException('Input file not exists', $localfile, Exceptions\fileException::FILE_NOT_READABLE);
        }

        $preparedInArchiveFile = self::cleanPath($inArchiveFile);

        if (!$preparedInArchiveFile) {
            throw new \Exception('In-Archive file path must be given: '.$inArchiveFile);
        }

        $length = strlen($preparedInArchiveFile);
        if (0x0000 > $length || $length > 0xffff) {
            throw new \Exception('Illegal in archive name parameter');
        }

        if (isset($this->entries[$preparedInArchiveFile])) {
            throw new \Exception('In-Archive file path is already exists: '.$inArchiveFile);
        }

        $this->entries[$preparedInArchiveFile] = new entry($this->fileHandle, $localfile, $preparedInArchiveFile, $compressionMethod);

        $this->offset = ftell($this->fileHandle);
    }


    /**
     * Close the ZIP archive file
     * @throws Exceptions\zipFlyException
     */
    public function close() {
        if (!$this->isOpen) {
            throw new Exceptions\zipFlyException('The Zip file is not initialized', Exceptions\zipFlyException::NOT_INITIALIZED);
        }

        //Write Central Directory
        $cdSize = 0;
        foreach ($this->entries as $entry) {
            $cdSize += fwrite($this->fileHandle, $entry->getCentralDirectoryRecord());
        }

        //Write Zip64 Central Directory
        if ($this->isZip64) {
            fwrite($this->fileHandle, $this->buildZip64EndOfCentralDirectoryRecord($cdSize));
            fwrite($this->fileHandle, $this->buildZip64EndOfCentralDirectoryLocator($cdSize));
        }

        fwrite($this->fileHandle, $this->buildEndOfCentralDirectoryRecord($cdSize));
        fclose($this->fileHandle);

        //Reset all internal variable
        $this->isOpen     = false;
        $this->filename   = null;
        $this->offset     = 0;
        $this->entries    = [];
        $this->fileHandle = null;
    }


}
