<?php
/**
 * This file is a part of G-Lex's zipFly compression library
 */

namespace zipFly;

use zipFly\Exceptions;

/**
 * G-Lex's ZIP Compression library
 */
class zipFly64 {

    /**
     * @var resource Output file pointer resource
     */
    private $fileHandle = false;

    /**
     * @var array File entries in the ZIP file
     */
    private $entries = [];

    /**
     * @var int Central Directory Start offset position of the ZIP file pointer
     */
    private $offsetCDStart = 0;


    use parts\headers;

    /**
     * Create new zipFly64 object
     * If you specify the $filename argument, it will create a new ZIP archive file with the given filename.
     * @param string $filename The name of the ZIP archive to create
     */
    public function __construct(string $filename = null) {
        if (PHP_INT_SIZE !== 8) {
            throw new Exceptions\zipFlyException(Exceptions\zipFlyException::NOT_64BIT, 1);
        }

        if (!is_null($filename)) {
            $this->create($filename);
        }
    }


    /**
     * Release all resources
     * If you didn't closed the last created ZIP archive, this will call the 'close' function
     */
    public function __destruct() {
        if ($this->isOpen()) {
            $this->close();
        }
    }


    /**
     * Create new ZIP archive file
     * Create a new file and open a ZIP file resource pointer with the given filename.
     * If the $overwrite argument is set to false and the file is already exists the function will throw an exception instead of deleting the old file.
     * @param string $filename Name of the ZIP archive file
     * @param bool $overwrite Overwrite the file if exists
     * @throws Exceptions\directoryException
     * @throws Exceptions\fileException
     */
    public function create($filename, $overwrite = true) {
        if ($this->isOpen()) {
            $this->close();
        }

        $directory = dirname($filename);

        if (!file_exists($directory)) {
            throw new Exceptions\directoryException($directory, Exceptions\directoryException::NOT_EXISTS, 1);
        }

        if (!is_writable($directory)) {
            throw new Exceptions\directoryException($directory, Exceptions\directoryException::NOT_WRITEABLE, 1);
        }

        if (is_readable($filename)) {
            if (!$overwrite) {
                throw new Exceptions\fileException($filename, Exceptions\fileException::EXISTS, 1);
            }

            unlink($filename);
        }

        $this->fileHandle = fopen($filename, 'w');
        $this->entries    = [];
    }


    /**
     * Sanitize the given file path
     * @param string $path
     * @return string
     */
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
     * Adds a file to the ZIP archive
     * Adds a file from a given '$localfile' path to the ZIP archive and place it inside the archive on the given $inArchiveFile path and name.
     * You can also specify the compression method to be used.
     * @param string $localfile Local file name and path to be added
     * @param string $inArchiveFile Destination name and path in the ZIP file
     * @param int $compressionMethod
     * @throws Exceptions\zipFlyException
     * @throws Exceptions\fileException
     * @throws \Exception
     */
    public function addFile($localfile, $inArchiveFile, $compressionMethod = null) {
        if (!$this->isOpen()) {
            throw new Exceptions\zipFlyException(Exceptions\zipFlyException::NOT_OPENED, 1);
        }

        if (!file_exists($localfile)) {
            throw new Exceptions\fileException($localfile, Exceptions\fileException::NOT_EXISTS, 1);
        }

        if (!is_readable($localfile)) {
            throw new Exceptions\fileException($localfile, Exceptions\fileException::NOT_READABLE, 1);
        }

        $preparedInArchiveFile = self::cleanPath($inArchiveFile);

        if (!$preparedInArchiveFile) {
            throw new Exceptions\zipFlyException(Exceptions\zipFlyException::BAD_INARCHIVE_PATH, 1, ['path' => $inArchiveFile]);
        }

        $length = strlen($preparedInArchiveFile);
        if (0x0000 > $length || $length > 0xffff) {
            throw new Exceptions\zipFlyException(Exceptions\zipFlyException::LONG_INARCHIVE_PATH, 1, ['path' => $inArchiveFile]);
        }

        if (isset($this->entries[$preparedInArchiveFile])) {
            throw new Exceptions\zipFlyException(Exceptions\zipFlyException::EXISTS_INARCHIVE_ENTRY, 1, ['path' => $inArchiveFile]);
        }

        $entry                                 = new parts\entry($this->fileHandle, $localfile, $preparedInArchiveFile, $compressionMethod);
        $this->entries[$preparedInArchiveFile] = $entry->getCentralDirectoryRecord();
    }


    /**
     * Close the ZIP archive
     * Write out the Central Directory and close the internal ZIP file resource pointer
     * @throws Exceptions\zipFlyException
     */
    public function close() {
        if (!$this->isOpen()) {
            throw new Exceptions\zipFlyException(Exceptions\zipFlyException::NOT_OPENED, 1);
        }

        $this->offsetCDStart = ftell($this->fileHandle);

        //Write Central Directory
        $cdSize = 0;
        foreach ($this->entries as $entry) {
            $cdSize += fwrite($this->fileHandle, $entry);
        }

        //Write Zip64 Central Directory
        if ($this->isZip64) {
            fwrite($this->fileHandle, $this->buildZip64EndOfCentralDirectoryRecord($cdSize));
            fwrite($this->fileHandle, $this->buildZip64EndOfCentralDirectoryLocator($cdSize));
        }

        fwrite($this->fileHandle, $this->buildEndOfCentralDirectoryRecord($cdSize));
        fclose($this->fileHandle);

        //Reset all internal variable
        $this->entries = [];
    }


    /**
     * Checks the internal ZIP file resource pointer is opened or not
     * An internal file resource pointer is opened when you call the "create" function or give a filename to the constructor.
     * After you call the "close" function the file pointer is closed.
     *
     * @return boolean True for opened ZIP file resource pointer
     * @throws Exceptions\zipFlyException
     */
    private function isOpen() {
        return is_resource($this->fileHandle);
    }


}
