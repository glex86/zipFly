<?php
/**
 * This file is a part of G-Lex's zipFly compression library
 */

namespace zipFly\parts;

use zipFly\parts\constants as zipConst;

require_once 'hashStream.php';

class entry {

    use headers;

    private $zipHandle         = null;
    private $offsetStart       = 0;
    private $offsetData        = 0;
    private $offsetEnd         = 0;
    private $inArchiveFile     = '';
    private $sourceFileInfo    = null;
    private $gpFlags           = zipConst::GPFLAG_NONE;
    private $compression       = zipConst::METHOD_DEFLATE;
    private $level             = zipConst::LEVEL_NORMAL;
    private $lastmod           = 0;
    private $localHeader       = '';
    private $localHeaderLength = 0;
    private $cdRec             = '';
    private $uncompressedHash  = null;
    private $uncompressedSize  = 0;
    private $compressedSize    = 0;
    private $dataCRC32         = 0;


    public function __construct($zipFileHandle, $localfile, $inArchiveFile, $compressionMethod = zipConst::METHOD_DEFLATE, $compressionLevel = zipConst::LEVEL_NORMAL) {
        $this->zipHandle = $zipFileHandle;

        $this->offsetStart = ftell($this->zipHandle);


        $this->sourceFileInfo = new \SplFileInfo($localfile);
        $this->lastmod        = $this->sourceFileInfo->getMTime();

        $this->inArchiveFile = $inArchiveFile;


        $this->compression = $compressionMethod;
        $this->level       = $compressionLevel;
        $this->compressorGPFlag();

        $this->localHeader       = $this->getLocalFileHeader();
        $this->localHeaderLength = strlen($this->localHeader);
        $this->offsetData        = $this->offsetStart + $this->localHeaderLength;
        fseek($this->zipHandle, $this->offsetData);

        $this->compressEntry();
        $this->offsetEnd = ftell($this->zipHandle);

        // build cdRec
        $this->cdRec = $this->buildCentralDirectoryHeader();

        //Create new local header with updated compressed length
        $this->localHeader = $this->getLocalFileHeader();
        fseek($this->zipHandle, $this->offsetStart);
        fwrite($this->zipHandle, $this->localHeader);
        fseek($this->zipHandle, $this->offsetEnd);
    }


    public function storeHash($hash, $size) {
        $this->uncompressedHash = $hash;
        $this->uncompressedSize = $size;
    }


    private function compressEntry() {
        $stream = \fopen($this->sourceFileInfo->getRealPath(), 'r');

        stream_filter_append($stream, "hash-stream", STREAM_FILTER_READ, array(&$this, 'storeHash'));


        switch ($this->compression) {
            case zipConst::METHOD_STORE:
                break;

            case zipConst::METHOD_BZIP2:
                stream_filter_append($stream, 'bzip2.compress', STREAM_FILTER_READ);
                break;

            default:
                if ($this->level == zipConst::LEVEL_MAX) {
                    $params = array('level' => 9);
                }
                elseif ($this->level == zipConst::LEVEL_MIN) {
                    $params = array('level' => 1);
                }
                else {
                    $params = array('level' => 6);
                }
                stream_filter_append($stream, 'zlib.deflate', STREAM_FILTER_READ, $params);
                break;
        }

        $this->compressedSize = 0;


        while (!feof($stream) && $data = fread($stream, zipConst::STREAM_CHUNK_SIZE)) {
            $this->compressedSize += strlen($data);

            fwrite($this->zipHandle, $data);
        }


        fclose($stream);

        $this->dataCRC32 = $this->uncompressedHash[1];
    }


    private function compressorGPFlag() {
        if (zipConst::METHOD_DEFLATE === $this->compression) {
            $bit1 = false;
            $bit2 = false;
            switch ($this->level) {
                case zipConst::LEVEL_MAX:
                    $bit1 = true;
                    break;

                case zipConst::LEVEL_NORMAL:
                    //$bit2 = true;
                    break;

                case zipConst::LEVEL_MIN:
                    $bit1 = true;
                    $bit2 = true;
                    break;
            }

            $this->gpFlags |= ($bit1 ? zipConst::GPFLAG_COMP1 : 0);
            $this->gpFlags |= ($bit2 ? zipConst::GPFLAG_COMP2 : 0);
        }
    }


    public function getCentralDirectoryRecord() {
        return $this->cdRec;
    }


}
