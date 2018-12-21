<?php
/**
 * This file is a part of G-Lex's zipFly compression library
 * required PHP version 5.6.3
 */

namespace zipFly;

use zipFly\Exceptions;

require_once 'parts/hashStream.php';

/**
 * G-Lex's ZIP Compression library
 */
class zipFly64 extends parts\base {

    /** @var array File entries in the ZIP file - Used for duplicate entry filter */
    private $entries = [];

    /** @var int Number of file entries in the generated zip file */
    protected $entryCount = 0;

    /** @var string The Central Directory */
    private $centralDirectory = '';

    /** @var int Duplicate entry filtering method */
    private $duplicateEntries = false;


    /**
     * Create new zipFly64 object
     * If you specify the $filename argument, it will create a new ZIP archive file with the given filename.
     * @param string $filename The name of the ZIP archive to create
     * @param int $duplicateEntryFilter Filtering method flag See DE_* constants
     */
    public function __construct(string $filename = null, $duplicateEntryFilter = self::DE_NONE) {
        if (PHP_INT_SIZE !== 8) {
            throw new Exceptions\zipFlyException(Exceptions\zipFlyException::NOT_64BIT, 1);
        }

        $this->duplicateEntries = $duplicateEntryFilter;

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
     * @param bool $zip64 Use ZIP64 extension
     * @param bool $zipStream Generate Streamable ZIP file
     * @throws Exceptions\zipFlyException
     */
    public function setZipFeature($zip64 = null, $zipStream = null) {
        if ($this->isOpen()) {
            throw new Exceptions\zipFlyException(Exceptions\zipFlyException::ALREADY_OPEN, 1);
        }

        if (!is_null($zip64)) {
            self::$isZip64 = (bool)$zip64;

            if (self::$isDebugMode) {
                \zipFly\parts\debugger::setZip64(self::$isZip64);
            }
        }

        if (!is_null($zipStream)) {
            self::$isZipStream = (bool)$zipStream;
        }
    }


    /**
     * Create new ZIP archive file
     * Create a new file and open a ZIP file resource pointer with the given filename.
     * If the $overwrite argument is set to false and the file is already exists the function will throw an exception instead of deleting the old file.
     * @param string $output Name of the output ZIP archive file
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
                throw new Exceptions\zipFlyException(Exceptions\zipFlyException::STREAM_NOT_WRITEABLE, 1);
            }

            if (!$meta['seekable'] && !self::$isZipStream) {
                throw new Exceptions\zipFlyException(Exceptions\zipFlyException::STREAM_NOT_SEEKABLE, 1);
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
     * Add new entry to the zip archive
     * @param mixed $data Uncompressed data or data stream
     * @param int $fileMTime Last file modification time
     * @param string $inArchiveFile Destination name and path in the ZIP file
     * @param int $compressionMethod Compression method to use
     * @param int $compressionLevel Compression level to use
     * @throws Exceptions\zipFlyException
     */
    private function addEntry($data, $fileMTime, $inArchiveFile, $compressionMethod, $compressionLevel) {
        if (!$this->isOpen()) {
            throw new Exceptions\zipFlyException(Exceptions\zipFlyException::NOT_OPENED, 2);
        }

        $preparedInArchiveFile = self::cleanPath($inArchiveFile);

        if (!$preparedInArchiveFile) {
            throw new Exceptions\zipFlyException(Exceptions\zipFlyException::BAD_INARCHIVE_PATH, 1, ['path' => $inArchiveFile]);
        }

        $length = strlen($preparedInArchiveFile);
        if (0x0000 > $length || $length > 0xffff) {
            throw new Exceptions\zipFlyException(Exceptions\zipFlyException::LONG_INARCHIVE_PATH, 1, ['path' => $inArchiveFile]);
        }

        //Duplicate Entry Filter
        if ($this->duplicateEntries) {
            $dePath = $this->duplicateEntries == self::DE_FULLPATH ? $preparedInArchiveFile : md5($preparedInArchiveFile);

            if (in_array($dePath, $this->entries)) {
                throw new Exceptions\zipFlyException(Exceptions\zipFlyException::EXISTS_INARCHIVE_ENTRY, 2, ['path' => $inArchiveFile]);
            }

            $this->entries[] = $dePath;
        }

        if (is_null($fileMTime)) {
            $fileMTime = time();
        }

        $this->centralDirectory .= parts\entry::create($data, $fileMTime, $preparedInArchiveFile, $compressionMethod, $compressionLevel);
        $this->entryCount++;
    }


    /**
     * Adds a file to the ZIP archive
     * Adds a file from a given '$localfile' path to the ZIP archive and place it inside the archive on the given $inArchiveFile path and name.
     * You can also specify the compression method to be used.
     * @param string $localfile Local file name and path to be added
     * @param string $inArchiveFile Destination name and path in the ZIP file
     * @param int $compressionMethod Compression method to use
     * @param int $compressionLevel Compression level to use
     * @throws Exceptions\zipFlyException
     * @throws Exceptions\fileException
     * @throws \Exception
     */
    public function addFile($localfile, $inArchiveFile, $compressionMethod = self::METHOD_DEFLATE, $compressionLevel = self::LEVEL_NORMAL) {
        if (!is_readable($localfile)) {
            throw new Exceptions\fileException($localfile, Exceptions\fileException::NOT_READABLE, 1);
        }

        if (self::$isDebugMode) {
            parts\debugger::addBlock('FILE: '.$localfile.' -> '.$inArchiveFile.' ('.strlen($inArchiveFile).' bytes)');
        }

        $sourceFileInfo = new \SplFileInfo($localfile);
        if ($sourceFileInfo->getSize() < 4 * 1024 * 1024) {
            $data = file_get_contents($localfile);
        }
        else {
            $data = fopen($localfile, 'rb');
        }

        $this->addEntry($data, $sourceFileInfo->getMTime(), $inArchiveFile, $compressionMethod, $compressionLevel);

        if (self::$isDebugMode) {
            parts\debugger::endBlock();
        }
    }


    /**
     * Add a file to a ZIP archive using its contents
     * @param string $content Uncompressed data
     * @param string $inArchiveFile Destination name and path in the ZIP file
     * @param int $fileMTime Last file modification time
     * @param int $compressionMethod Compression method to use
     * @param int $compressionLevel Compression level to use
     * @throws Exceptions\zipFlyException
     */
    public function addFromString($content, $inArchiveFile, $fileMTime = null, $compressionMethod = self::METHOD_DEFLATE, $compressionLevel = self::LEVEL_NORMAL) {
        if (self::$isDebugMode) {
            parts\debugger::addBlock('STRING -> '.$inArchiveFile.' ('.strlen($inArchiveFile).' bytes)');
        }

        $this->addEntry((string)$content, $fileMTime, $inArchiveFile, $compressionMethod, $compressionLevel);

        if (self::$isDebugMode) {
            parts\debugger::endBlock();
        }
    }


    /**
     * Add a file to a ZIP archive using an opened stream resource
     * @param string $stream Uncompressed data stream
     * @param string $inArchiveFile Destination name and path in the ZIP file
     * @param int $fileMTime Last file modification time
     * @param int $compressionMethod Compression method to use
     * @param int $compressionLevel Compression level to use
     * @throws Exceptions\zipFlyException
     */
    public function addFromStream($stream, $inArchiveFile, $fileMTime = null, $compressionMethod = self::METHOD_DEFLATE, $compressionLevel = self::LEVEL_NORMAL) {
        if (!is_resource($stream) || get_resource_type($stream) !== "stream") {
            throw new Exceptions\zipFlyException(Exceptions\zipFlyException::STREAM_EXPECTED, 1, ['param' => '1', 'type' => gettype($stream)]);
        }

        if (self::$isDebugMode) {
            parts\debugger::addBlock('STREAM -> '.$inArchiveFile.' ('.strlen($inArchiveFile).' bytes)');
        }

        // ensure resource is opened for reading (fopen mode must contain "r" or "+")
        $meta = stream_get_meta_data($stream);
        if (isset($meta['mode']) && $meta['mode'] !== '' && strpos($meta['mode'], 'r') === strpos($meta['mode'], '+')) {
            throw new Exceptions\zipFlyException(Exceptions\zipFlyException::STREAM_NOT_READABLE, 1);
        }

        $this->addEntry($stream, $fileMTime, $inArchiveFile, $compressionMethod, $compressionLevel);

        if (self::$isDebugMode) {
            parts\debugger::endBlock();
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
            parts\debugger::addBlock('CENTRAL DIRECTORY - Total '.$this->entryCount.' file entries');
        }

        //Write Central Directory
        $cdSize = fwrite(self::$fileHandle, $this->centralDirectory);

        //Write Zip64 Central Directory
        if (self::$isZip64) {
            fwrite(self::$fileHandle, self::buildZip64EndOfCentralDirectoryRecord(self::$localStreamOffset, $this->entryCount, $cdSize));
            fwrite(self::$fileHandle, self::buildZip64EndOfCentralDirectoryLocator(self::$localStreamOffset, $cdSize));
        }

        fwrite(self::$fileHandle, self::buildEndOfCentralDirectoryRecord(self::$localStreamOffset, $this->entryCount, $cdSize));
        fclose(self::$fileHandle);

        //Reset all internal variable
        $this->entries           = [];
        self::$localStreamOffset = 0;

        if (self::$isDebugMode) {
            parts\debugger::endBlock();
        }
    }


    /**
     * Checks the internal ZIP file resource pointer is opened or not
     * An internal file resource pointer is opened when you call the "create" function or give a filename to the constructor.
     * After you call the "close" function the file pointer is closed.
     *
     * @return boolean True for opened ZIP file resource pointer
     */
    private function isOpen() {
        return is_resource(self::$fileHandle);
    }


}
