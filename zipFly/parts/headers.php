<?php
/**
 * This file is a part of G-Lex's zipFly compression library
 */

namespace zipFly\parts;

use zipFly\parts\constants as zipConst;

trait headers {
    private $versionToExtract = 46;
    private $isZip64          = true;


    private static function hexen($value) {
        $res = unpack('H*', $value)[1];
        $res = str_split($res, 2);
        $res = implode(' ', $res);

        return $res;
    }


    private static function pack16le($data) {
        return pack('v', $data);
    }


    private static function pack32le($data) {
        return pack('V', $data);
    }


    private static function pack64le($data) {
        return pack('P', $data);
    }


    private static function generateHeader($fields, $name) {
        //echo str_pad(' +-- '.$name.' ', 200, '-')."\n";
        // <editor-fold defaultstate="collapsed" desc=" CONVERTER ">
        /*
         * CONVERTER:
         * ^\s+\.([^\s]+)\s+//((?:\s[\w\-]+)+)\s++(\d+) bytes
         * [$1, '$2', $3],\n
         */
        // </editor-fold>

        $fileOut = '';
        foreach ($fields as $field) {
            /*
              echo ' | ', str_pad(strlen($fileOut), 3);
              echo ' + ', str_pad(strlen($field[0]).' bytes', 8);
              echo ' | ', str_pad(substr(self::hexen($field[0]), 0, 23), 24);
              echo ' | ', $field[2].' bytes';
              echo ' | ', $field[1], "\n";
             */
            $fileOut .= $field[0];
        }
//        echo " + Total size: ".strlen($fileOut)." bytes \n\n";

        return $fileOut;
    }


    private function buildZip64ExtendedInformationField($uncompressedSize = 0, $compressedSize = 0) {
        $fields = [
            [self::pack16le(0x0001),                'tag for this "extra" block type (ZIP64)', 2],
            [self::pack16le(28),                    'size of this "extra" block', 2],
            [self::pack64le($uncompressedSize),     'original uncompressed file size', 8],
            [self::pack64le($compressedSize),       'size of compressed data', 8],
            [self::pack64le($this->offsetStart),    'offset of local header record', 8],
            [self::pack32le(0),                     'number of the disk on which this file starts', 4],
        ];

        return self::generateHeader($fields, 'ZIP64 EXTENDED INFORMATION');
    }


    function getLocalFileHeader() {
        $compressedSize   = $this->compressedSize;
        $uncompressedSize = $this->uncompressedSize;

        $isDir     = False;
        $dataCRC32 = $this->dataCRC32;

        $dosTime = self::getDosTime($this->lastmod);
        if ($this->isZip64) {
            $zip64Ext           = $this->buildZip64ExtendedInformationField($uncompressedSize, $compressedSize);
            $uncompressedSize   = -1;
            $compressedSize     = -1;
        }
        else {
            $zip64Ext = '';
        }

        $fields = [
                    [self::pack32le(zipConst::ZIP_LOCAL_FILE_HEADER), 'local file header signature', 4],
                    [self::pack16le($this->versionToExtract),         'version needed to extract', 2],
                    [self::pack16le($this->gpFlags),                  'general purpose bit flag', 2],
                    [self::pack16le($this->compression),              'compression method', 2],
                    [self::pack32le($dosTime),                        'last mod file time + last mod file date ', 4],
                    [self::pack32le($dataCRC32),                      'crc-32', 4],
                    [self::pack32le($compressedSize),                 'compressed size', 4],
                    [self::pack32le($uncompressedSize),               'uncompressed size', 4],
                    [self::pack16le(strlen($this->inArchiveFile)),    'file name length', 2],
                    [self::pack16le(strlen($zip64Ext)),               'extra field length', 2],
                    [$this->inArchiveFile,                            'file name', 'v'],
                    [$zip64Ext,                                       'extra field', 'v']
            ];

        return self::generateHeader($fields, 'LOCAL FILE HEADER');
    }


    private function addDataDescriptor($uncompressedSize, $compressedSize, $dataCRC32) {
        if ($this->isZip64) {
            $length                 = 24;
            $paramLength            = 8;
            $packedCompressedSize   = self::pack64le($compressedSize);
            $packedUncompressedSize = self::pack64le($uncompressedSize);
        }
        else {
            $length                 = 16;
            $paramLength            = 4;
            $packedCompressedSize   = self::pack32le($compressedSize);
            $packedUncompressedSize = self::pack32le($uncompressedSize);
        }

        $fields = [
            [self::pack32le(zipConst::ZIP_STREAM_DATA_DESCRIPTOR),    'Extended Local file header signature', 4],
            [self::pack32le($dataCRC32),                              'CRC-32', 4],
            [$packedCompressedSize,                                   'Compressed size', $paramLength],
            [$packedUncompressedSize,                                 'Uncompressed size', $paramLength]
        ];

        fwrite($this->zipHandle, $this->generateHeader($fields, 'DATA DESCRIPTOR / ELFH'));

        return $length;
    }


    private function buildCentralDirectoryHeader() {
        $dosTime = self::getDosTime($this->lastmod);

        if ($this->isZip64) {
            $zip64Ext = $this->buildZip64ExtendedInformationField($this->uncompressedSize, $this->compressedSize);

            $uncompressedSize   = -1;
            $compressedSize     = -1;
            $diskNo             = -1;
            $offset             = -1;
        }
        else {
            $zip64Ext   = '';
            $uncompressedSize   = $this->uncompressedSize;
            $compressedSize     = $this->compressedSize;
            $diskNo             = 0;
            $offset             = $this->offsetStart;
        }

        //hack
        $extFileAttr = 32;

        $fields = [
                [self::pack32le(zipConst::ZIP_CENTRAL_FILE_HEADER),   'central file header signature', 4],
                [self::pack16le(zipConst::ATTR_MADE_BY_VERSION),      'version made by', 2],
                [self::pack16le($this->versionToExtract),             'version needed to extract', 2],
                [self::pack16le($this->gpFlags),                      'general purpose bit flag', 2],
                [self::pack16le($this->compression),                  'compression method', 2],
                [self::pack32le($dosTime),                            'last mod file time + last mod file date', 4],
                [self::pack32le($this->dataCRC32),                    'crc-32', 4],
                [self::pack32le($compressedSize),                     'compressed size', 4],
                [self::pack32le($uncompressedSize),                   'uncompressed size', 4],
                [self::pack16le(strlen($this->inArchiveFile)),        'file name length', 2],
                [self::pack16le(strlen($zip64Ext)),                   'extra field length', 2],
                [self::pack16le(0),                                   'file comment length', 2],
                [self::pack16le($diskNo),                             'disk number start', 2],
                [self::pack16le(0),                                   'internal file attributes', 2],
                [self::pack32le($extFileAttr),                        'external file attributes', 4],
                [self::pack32le($offset),                             'relative offset of local header', 4],
                [$this->inArchiveFile,                                'file name', 'v'],
                [$zip64Ext,                                           'extra field', 'v'],
                ['',                                                  'file comment', 'v']
            ];

        return $this->generateHeader($fields, 'CENTRAL DIRECTORY HEADER');
    }


    private function buildZip64EndOfCentralDirectoryRecord($cdRecLength) {
        $cdRecCount       = sizeof($this->entries);

        $fields = [
                [self::pack32le(zipConst::ZIP64_END_OF_CENTRAL_DIRECTORY),    'zip64 end of central dir signature', 4],
                [self::pack64le(44),                                          'size of zip64 end of central directory record', 8],
                [self::pack16le(zipConst::ATTR_MADE_BY_VERSION),              'version made by', 2],
                [self::pack16le($this->versionToExtract),                     'version needed to extract', 2],
                [self::pack32le(0),                                           'number of this disk', 4],
                [self::pack32le(0),                                           'number of the disk with the start of the central directory', 4],
                [self::pack64le($cdRecCount),                                 'total number of entries in the central directory on this disk', 8],
                [self::pack64le($cdRecCount),                                 'total number of entries in the central directory', 8],
                [self::pack64le($cdRecLength),                                'size of the central directory', 8],
                [self::pack64le($this->offsetCDStart),                               'offset of start of central directory with respect to the starting disk number', 8],
                ['',                                                          'zip64 extensible data sector', 'v']
            ];

        return $this->generateHeader($fields, 'ZIP64 END of CENTRAL DIRECTORY RECORD');
    }


    private function buildZip64EndOfCentralDirectoryLocator($cdRecLength) {
        $zip64RecStart = $this->offsetCDStart + $cdRecLength;

        $fields = [
                [self::pack32le(zipConst::ZIP64_END_OF_CENTRAL_DIR_LOCATOR),  'zip64 end of central dir locator signature', 4],
                [self::pack32le(0),                                           'number of the disk with the start of the zip64 end of central directory', 4],
                [self::pack64le($zip64RecStart),                              'relative offset of the zip64 end of central directory record', 8],
                [self::pack32le(1),                                           'total number of disks', 4],
            ];

        return $this->generateHeader($fields, 'END of CENTRAL DIRECTORY LOCATOR');
    }


    private function buildEndOfCentralDirectoryRecord($cdRecLength) {
        if ($this->isZip64) {
            $diskNumber  = -1;
            $cdRecCount  = -1;
            $cdRecLength = -1;
            $offset      = -1;
        }
        else {
            $diskNumber = 0;
            $cdRecCount = sizeof($this->entries);
            $offset = $this->offsetCDStart;
        }

        $fields = [
                [self::pack32le(zipConst::ZIP_END_OF_CENTRAL_DIRECTORY),  'end of central dir signature', 4],
                [self::pack16le($diskNumber),                             'number of this disk', 2],
                [self::pack16le($diskNumber),                             'number of the disk with the start of the central directory', 2],
                [self::pack16le($cdRecCount),                             'total number of entries in the central directory on this disk', 2],
                [self::pack16le($cdRecCount),                             'total number of entries in the central directory', 2],
                [self::pack32le($cdRecLength),                            'size of the central directory', 4],
                [self::pack32le($offset),                                 'offset of start of central directory with respect to the starting disk number', 4],
                [self::pack16le(0),                                       'ZIP file comment length', 2],
                ['',                                                      'ZIP file comment', 'v']
            ];

        return $this->generateHeader($fields, 'END of CENTRAL DIRECTORY RECORD');
    }


    private static function getDosTime($timestamp = 0) {
        $timestamp = (int)$timestamp;
        $oldTZ     = @date_default_timezone_get();
        date_default_timezone_set('UTC');
        $date      = ($timestamp == 0 ? getdate() : getdate($timestamp));
        date_default_timezone_set($oldTZ);
        if ($date['year'] >= 1980) {
            return (($date['mday'] + ($date['mon'] << 5) + (($date['year'] - 1980) << 9)) << 16) | (($date['seconds'] >> 1) + ($date['minutes'] << 5) + ($date['hours'] << 11));
        }
        return 0x0000;
    }
}
