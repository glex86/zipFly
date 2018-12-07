<?php
/**
 * This file is a part of G-Lex's zipFly compression library
 */

namespace zipFly;

use zipFly\Exceptions;

require_once 'parts/hashStream.php';

/**
 * G-Lex's ZIP Compression library
 */
class zipFly64 extends parts\base {

    /**
     * @var array File entries in the ZIP file
     */
    private $entries = [];


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
     * Enable or disable the supported ZIP features
     * @param type $zip64 ZIP64 extension
     * @param type $zipStream Streamable ZIPs
     * @throws Exceptions\zipFlyException
     */
    public function setZipFeature(bool $zip64 = true, bool $zipStream = false) {
        if ($this->isOpen()) {
            throw new Exceptions\zipFlyException(Exceptions\zipFlyException::ALREADY_OPEN, 1);
        }

        if (!is_null($zip64)) {
            self::$isZip64 = (bool)$zip64;
        }

        if (!is_null($zipStream)) {
            self::$isZipStream = (bool)$zipStream;
        }
    }


    /**
     * Create new ZIP archive file
     * Create a new file and open a ZIP file resource pointer with the given filename.
     * If the $overwrite argument is set to false and the file is already exists the function will throw an exception instead of deleting the old file.
     * @param string $output Name of the ZIP archive file
     * @param bool $overwrite Overwrite the file if exists
     * @throws Exceptions\directoryException
     * @throws Exceptions\fileException
     */
    public function create($output, $overwrite = true) {
        if ($this->isOpen()) {
            $this->close();
        }

        if (is_resource($output)) {
            $meta = stream_get_meta_data($output);

            if (preg_match('/[waxc+]/', $meta['mode']) !== 1) {
                throw new \RuntimeException;
            }

            if (!$meta['seekable'] && !self::$isZipStream) {
                throw new \RuntimeException;
            }

            self::$fileHandle = $output;
            return true;
        }

        $directory = dirname($output);

        if (!file_exists($directory)) {
            throw new Exceptions\directoryException($directory, Exceptions\directoryException::NOT_EXISTS, 1);
        }

        if (!is_writable($directory)) {
            throw new Exceptions\directoryException($directory, Exceptions\directoryException::NOT_WRITEABLE, 1);
        }

        if (is_readable($output)) {
            if (!$overwrite) {
                throw new Exceptions\fileException($output, Exceptions\fileException::EXISTS, 1);
            }

            unlink($output);
        }

        self::$fileHandle = fopen($output, 'wb');
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
    public function addFile($localfile, $inArchiveFile, $compressionMethod = self::METHOD_DEFLATE) {
        if (self::$isDebugMode) {
            $title       = $localfile.' -> '.$inArchiveFile.' ('.strlen($inArchiveFile).' bytes)';
            $titleLength = mb_strlen($title);
            echo "\n   +".str_repeat('-', $titleLength)."+\n  / {$title} \\\n";
            echo " +--".str_repeat('-', $titleLength).'--+'.str_repeat('-', 153 - $titleLength)."\n |\n";
        }

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

        $this->entries[$preparedInArchiveFile] = (string) new parts\entry($localfile, $preparedInArchiveFile, $compressionMethod);

        if (self::$isDebugMode) {
            echo ' |'.str_repeat('_', 158)."\n\n\n";
        }
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

        if (self::$isDebugMode) {
            $title       = sprintf('CENTRAL DIRECTORY - Total %d file entries', count($this->entries));
            $titleLength = mb_strlen($title);
            echo "\n +".str_repeat('-', $titleLength + 2)."+\n | {$title} |\n";
            echo ' +'.str_repeat('-', $titleLength + 2).'+'.str_repeat('-', 155 - $titleLength)."\n |\n";
        }

        if (self::$isZipStream) {
            $offsetCDStart = self::$localStreamOffset;
        } else {
            $offsetCDStart = ftell(self::$fileHandle);
        }

        //Write Central Directory
        $cdSize = 0;
        foreach ($this->entries as $entry) {
            $cdSize += fwrite(self::$fileHandle, $entry);
        }

        //Write Zip64 Central Directory
        if (self::$isZip64) {
            fwrite(self::$fileHandle, self::buildZip64EndOfCentralDirectoryRecord($offsetCDStart, sizeof($this->entries), $cdSize));
            fwrite(self::$fileHandle, self::buildZip64EndOfCentralDirectoryLocator($offsetCDStart, $cdSize));
        }

        fwrite(self::$fileHandle, self::buildEndOfCentralDirectoryRecord($offsetCDStart, sizeof($this->entries), $cdSize));
        fclose(self::$fileHandle);

        //Reset all internal variable
        $this->entries = [];

        if (self::$isDebugMode) {
            echo ' |'.str_repeat('_', 158)."\n\n\n";
        }
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
        return is_resource(self::$fileHandle);
    }


}
