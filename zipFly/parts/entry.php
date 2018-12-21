<?php
/**
 * This file is a part of G-Lex's zipFly compression library
 */

namespace zipFly\parts;

/**
 * File entry in the ZIP archive
 */
class entry extends base {

    /** @var int General Purpose Bit Flaghs */
    private $gpFlags = self::GPFLAG_NONE;

    /** @var int Compression method to use */
    private $compressionMethod = self::METHOD_DEFLATE;

    /** @var int Compression level to use */
    private $compressionLevel = self::LEVEL_NORMAL;

    /** @var int Size of the compressed data */
    private $compressedSize = 0;

    /** @var int Size of the uncompressed data */
    private $uncompressedSize = 0;

    /** @var int CRC32 checksum of the uncompressed data */
    private $dataCRC32 = 0;

    /** @var string The Central Directory header for the current entry */
    private $centralDirectoryHeader = '';


    /**
     * Add new entry to the ZIP archive
     * @param mixed $data Uncompressed data string or stream resource
     * @param int $lastmodTimestamp Last file modification time
     * @param string $inArchiveFile Destination name and path in the ZIP file
     * @param int $compressionMethod Compression method to use
     * @param int $compressionLevel Compression level to use
     * @return string Central Directory Header
     */
    public static function create($data, $lastmodTimestamp, $inArchiveFile, $compressionMethod = self::METHOD_DEFLATE, $compressionLevel = self::LEVEL_NORMAL) {
        $entry = new self($compressionMethod, $compressionLevel);

        $offsetStart = self::$localStreamOffset;
        $lastmod     = self::getDosTime($lastmodTimestamp);


        if (self::$isZipStream) {
            self::$localStreamOffset += fwrite(self::$fileHandle, self::getLocalFileHeader($inArchiveFile, $lastmod, $entry->compressedSize, $entry->uncompressedSize, $entry->dataCRC32, $offsetStart, $entry->gpFlags, $entry->compressionMethod));
        }
        else {
            self::$localStreamOffset += 30 + strlen($inArchiveFile);
            if (self::$isZip64) {
                self::$localStreamOffset += 32;
            }
            //Seek to the calculated data-start position (in PHP you can seek past the end-of-file)
            fseek(self::$fileHandle, self::$localStreamOffset);
        }


        if (is_string($data)) {
            $entry->compressStringData($data);
        }
        else {
            $entry->compressStreamData($data);
        }

        self::$localStreamOffset += $entry->compressedSize;

        if (self::$isZipStream) {
            self::$localStreamOffset += fwrite(self::$fileHandle, self::getDataDescriptor($entry->uncompressedSize, $entry->compressedSize, $entry->dataCRC32));
        }
        else {
            //Create local header with compressed length
            fseek(self::$fileHandle, $offsetStart);
            fwrite(self::$fileHandle, self::getLocalFileHeader($inArchiveFile, $lastmod, $entry->compressedSize, $entry->uncompressedSize, $entry->dataCRC32, $offsetStart, $entry->gpFlags, $entry->compressionMethod));
            fseek(self::$fileHandle, self::$localStreamOffset);
        }

        return self::buildCentralDirectoryHeader($inArchiveFile, $lastmod, $entry->compressedSize, $entry->uncompressedSize, $entry->dataCRC32, $offsetStart, $entry->gpFlags, $entry->compressionMethod);
    }


    /**
     * Zip File entry
     * @param int $compressionMethod Compression method to use
     * @param int $compressionLevel Compression level to use
     */
    public function __construct($compressionMethod = self::METHOD_DEFLATE, $compressionLevel = self::LEVEL_NORMAL) {

        $this->gpFlags = self::$isZipStream ? self::GPFLAG_ADD : self::GPFLAG_NONE;

        $this->compressionMethod = $compressionMethod;
        $this->compressionLevel  = $compressionLevel;
        $this->compressorGPFlag();
    }


    /**
     * Compress and write out the given string data
     * @param string $data
     */
    private function compressStringData($data) {
        $this->uncompressedSize = strlen($data);
        $this->dataCRC32        = crc32($data);

        switch ($this->compressionMethod) {
            case self::METHOD_STORE:
                break;

            case self::METHOD_BZIP2:
                $data = bzcompress($data);
                break;

            default:
                $data = gzdeflate($data, self::$compressorParams[$this->compressionMethod][$this->compressionLevel]['level']);
                break;
        }

        $this->compressedSize = strlen($data);
        fwrite(self::$fileHandle, $data);
    }


    /**
     * Compress and write out the data from the given stream
     * @param resource $stream
     */
    private function compressStreamData($stream) {
        stream_filter_append($stream, "hash-stream", STREAM_FILTER_READ, array(&$this->dataCRC32, &$this->uncompressedSize));
        switch ($this->compressionMethod) {
            case self::METHOD_STORE:
                break;

            case self::METHOD_BZIP2:
                stream_filter_append($stream, 'bzip2.compress', STREAM_FILTER_READ);
                break;

            default:
                stream_filter_append($stream, 'zlib.deflate', STREAM_FILTER_READ, self::$compressorParams[$this->compressionMethod][$this->compressionLevel]);
                break;
        }

        $this->compressedSize = 0;
        while (!feof($stream)) {
            $this->compressedSize += fwrite(self::$fileHandle, fread($stream, self::STREAM_CHUNK_SIZE));
        }

        fclose($stream);
    }


    /**
     * Set the General Purpose Bit Compression level Flags
     */
    private function compressorGPFlag() {
        if (self::METHOD_DEFLATE === $this->compressionMethod) {
            $bit1 = false;
            $bit2 = false;
            switch ($this->compressionLevel) {
                case self::LEVEL_MAX:
                    $bit1 = true;
                    break;

                case self::LEVEL_NORMAL:
                    //$bit2 = true;
                    break;

                case self::LEVEL_MIN:
                    $bit1 = true;
                    $bit2 = true;
                    break;
            }

            $this->gpFlags |= ($bit1 ? self::GPFLAG_COMP1 : 0);
            $this->gpFlags |= ($bit2 ? self::GPFLAG_COMP2 : 0);
        }
    }


}
