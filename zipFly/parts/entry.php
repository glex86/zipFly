<?php
/**
 * This file is a part of G-Lex's zipFly compression library
 */

namespace zipFly\parts;

class entry extends base {

    private $gpFlags                = self::GPFLAG_NONE;
    private $compression            = self::METHOD_DEFLATE + self::LEVEL_NORMAL;
    private $compressedSize         = 0;
    private $uncompressedSize       = 0;
    private $dataCRC32              = 0;
    private $centralDirectoryHeader = '';


    public function __construct($localfile, $inArchiveFile, $compressionMethod = self::METHOD_DEFLATE, $compressionLevel = self::LEVEL_NORMAL) {
        if (self::$isZipStream) {
            $offsetStart = self::$localStreamOffset;
        } else {
            $offsetStart = ftell(self::$fileHandle);
        }

        $sourceFileInfo = new \SplFileInfo($localfile);
        $lastmod        = self::getDosTime($sourceFileInfo->getMTime());

        $this->gpFlags = self::$isZipStream ? self::GPFLAG_ADD : self::GPFLAG_NONE;

        $this->compression = $compressionMethod + $compressionLevel;
        $this->compressorGPFlag();

        if (self::$isZipStream) {
            self::$localStreamOffset += fwrite(self::$fileHandle,
                    self::getLocalFileHeader($inArchiveFile, $lastmod, $this->compressedSize, $this->uncompressedSize, $this->dataCRC32, $offsetStart, $this->gpFlags, $this->compression & 0xffff));
        }
        else {
            //Calculate the size of the local header
            $localHeaderLength = 30 + strlen($inArchiveFile);
            if (self::$isZip64) {
                $localHeaderLength += 32;
            }

            //Seek to the calculated data-start position (in PHP you can seek past the end-of-file)
            fseek(self::$fileHandle, $offsetStart + $localHeaderLength);
        }


        $this->compressEntry($sourceFileInfo->getRealPath());

        if (self::$isZipStream) {
            self::$localStreamOffset += $this->compressedSize;
            self::$localStreamOffset += fwrite(self::$fileHandle, self::getDataDescriptor($this->uncompressedSize, $this->compressedSize, $this->dataCRC32));
        }
        else {
            //Create new local header with updated compressed length
            $offsetEnd = ftell(self::$fileHandle);
            fseek(self::$fileHandle, $offsetStart);
            fwrite(self::$fileHandle,
                    self::getLocalFileHeader($inArchiveFile, $lastmod, $this->compressedSize, $this->uncompressedSize, $this->dataCRC32, $offsetStart, $this->gpFlags, $this->compression & 0xffff));
            fseek(self::$fileHandle, $offsetEnd);
        }

        $this->centralDirectoryHeader = self::buildCentralDirectoryHeader($inArchiveFile, $lastmod, $this->compressedSize, $this->uncompressedSize, $this->dataCRC32, $offsetStart, $this->gpFlags,
                        $this->compression & 0xffff);
    }


    private function compressEntry($localFile) {
        $stream = \fopen($localFile, 'r');

        stream_filter_append($stream, "hash-stream", STREAM_FILTER_READ, array(&$this->dataCRC32, &$this->uncompressedSize));


        switch ($this->compression & 0xffff) {
            case self::METHOD_STORE:
                break;

            case self::METHOD_BZIP2:
                stream_filter_append($stream, 'bzip2.compress', STREAM_FILTER_READ);
                break;

            default:
                if ($this->compression & 0xffff0000 == self::LEVEL_MAX) {
                    $params = array('level' => 9);
                }
                elseif ($this->compression & 0xffff0000 == self::LEVEL_MIN) {
                    $params = array('level' => 1);
                }
                else {
                    $params = array('level' => 6);
                }
                stream_filter_append($stream, 'zlib.deflate', STREAM_FILTER_READ, $params);
                break;
        }

        $this->compressedSize = 0;


        while (!feof($stream) && $data = fread($stream, self::STREAM_CHUNK_SIZE)) {
            $this->compressedSize += strlen($data);

            fwrite(self::$fileHandle, $data);
        }


        fclose($stream);
    }


    private function compressorGPFlag() {
        if (self::METHOD_DEFLATE === $this->compression) {
            $bit1 = false;
            $bit2 = false;
            switch ($this->compression & 0xffff0000) {
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


    public function __toString() {
        return $this->centralDirectoryHeader;
    }


}
