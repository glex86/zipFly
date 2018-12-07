<?php
/**
 * This file is a part of G-Lex's zipFly compression library
 */

namespace zipFly\parts;

trait headers {

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
        $debugOut = '';
        $fileOut  = '';
        foreach ($fields as $field) {
            if (self::$isDebugMode) {
                $debugOut .= sprintf(" |   | %3d + %-2d bytes | %-24s | %-2s bytes | %s\n", strlen($fileOut), strlen($field[0]), substr(self::hexen($field[0]), 0, 23), $field[2], $field[1]);
            }

            $fileOut .= $field[0];
        }

        if (self::$isDebugMode) {
            echo sprintf(" |  \n |   [ %s ] (Total size: %d bytes)\n", $name, strlen($fileOut));
            echo ' |   +'.str_repeat('-', 16).'+'.str_repeat('-', 26).'+'.str_repeat('-', 9).'+'.str_repeat('-', 100)."\n";
            echo $debugOut;
            echo ' |   +'.str_repeat('-', 16).'+'.str_repeat('-', 26).'+'.str_repeat('-', 9).'+'.str_repeat('-', 100)."\n |  \n";
        }

        return $fileOut;
    }


    private static function buildZip64ExtendedInformationField($startOffset, $uncompressedSize = 0, $compressedSize = 0) {
        $fields = [
            [self::pack16le(0x0001), 'tag for this "extra" block type (ZIP64)', 2],
            [self::pack16le(28), 'size of this "extra" block', 2],
            [self::pack64le($uncompressedSize), 'original uncompressed file size', 8],
            [self::pack64le($compressedSize), 'size of compressed data', 8],
            [self::pack64le($startOffset), 'offset of local header record', 8],
            [self::pack32le(0), 'number of the disk on which this file starts', 4],
        ];

        return self::generateHeader($fields, 'ZIP64 EXTENDED INFORMATION EXTRA FIELD');
    }


    protected static function getLocalFileHeader($fileName, $dosTime, $compressedSize, $uncompressedSize, $dataCRC32, $startOffset, $gpFlags, $compressionMethod) {
        if (self::$isZip64) {
            $zip64Ext         = self::buildZip64ExtendedInformationField($startOffset, $uncompressedSize, $compressedSize);
            $uncompressedSize = -1;
            $compressedSize   = -1;
        }
        else {
            $zip64Ext = '';
        }

        $fields = [
            [self::pack32le(self::ZIP_LOCAL_FILE_HEADER), 'local file header signature', 4],
            [self::pack16le(self::VERSION_TO_EXTRACT), 'version needed to extract', 2],
            [self::pack16le($gpFlags), 'general purpose bit flag', 2],
            [self::pack16le($compressionMethod), 'compression method', 2],
            [self::pack32le($dosTime), 'last mod file time + last mod file date ', 4],
            [self::pack32le($dataCRC32), 'crc-32', 4],
            [self::pack32le($compressedSize), 'compressed size', 4],
            [self::pack32le($uncompressedSize), 'uncompressed size', 4],
            [self::pack16le(strlen($fileName)), 'file name length', 2],
            [self::pack16le(strlen($zip64Ext)), 'extra field length', 2],
            [$fileName, 'file name', 'v'],
            [$zip64Ext, 'extra field', 'v']
        ];

        return self::generateHeader($fields, 'LOCAL FILE HEADER');
    }


    protected static function getDataDescriptor($uncompressedSize, $compressedSize, $dataCRC32) {
        if (self::$isZip64) {
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
            [self::pack32le(self::ZIP_STREAM_DATA_DESCRIPTOR), 'Extended Local file header signature', 4],
            [self::pack32le($dataCRC32), 'CRC-32', 4],
            [$packedCompressedSize, 'Compressed size', $paramLength],
            [$packedUncompressedSize, 'Uncompressed size', $paramLength]
        ];

        return self::generateHeader($fields, 'DATA DESCRIPTOR / ELFH');
    }


    protected static function buildCentralDirectoryHeader($fileName, $dosTime, $compressedSize, $uncompressedSize, $dataCRC32, $startOffset, $gpFlags, $compressionMethod) {
        if (self::$isZip64) {
            $zip64Ext = self::buildZip64ExtendedInformationField($startOffset, $uncompressedSize, $compressedSize);

            $uncompressedSize = -1;
            $compressedSize   = -1;
            $diskNo           = -1;
            $startOffset      = -1;
        }
        else {
            $zip64Ext         = '';
            $diskNo           = 0;
        }

        //hack
        $extFileAttr = 32;

        $fields = [
            [self::pack32le(self::ZIP_CENTRAL_FILE_HEADER), 'central file header signature', 4],
            [self::pack16le(self::ATTR_MADE_BY_VERSION), 'version made by', 2],
            [self::pack16le(self::VERSION_TO_EXTRACT), 'version needed to extract', 2],
            [self::pack16le($gpFlags), 'general purpose bit flag', 2],
            [self::pack16le($compressionMethod), 'compression method', 2],
            [self::pack32le($dosTime), 'last mod file time + last mod file date', 4],
            [self::pack32le($dataCRC32), 'crc-32', 4],
            [self::pack32le($compressedSize), 'compressed size', 4],
            [self::pack32le($uncompressedSize), 'uncompressed size', 4],
            [self::pack16le(strlen($fileName)), 'file name length', 2],
            [self::pack16le(strlen($zip64Ext)), 'extra field length', 2],
            [self::pack16le(0), 'file comment length', 2],
            [self::pack16le($diskNo), 'disk number start', 2],
            [self::pack16le(0), 'internal file attributes', 2],
            [self::pack32le($extFileAttr), 'external file attributes', 4],
            [self::pack32le($startOffset), 'relative offset of local header', 4],
            [$fileName, 'file name', 'v'],
            [$zip64Ext, 'extra field', 'v'],
            ['', 'file comment', 'v']
        ];

        return self::generateHeader($fields, 'CENTRAL DIRECTORY HEADER');
    }


    protected static function buildZip64EndOfCentralDirectoryRecord($cdStartOffset, $cdRecCount, $cdRecLength) {
        $fields = [
            [self::pack32le(self::ZIP64_END_OF_CENTRAL_DIRECTORY), 'zip64 end of central dir signature', 4],
            [self::pack64le(44), 'size of zip64 end of central directory record', 8],
            [self::pack16le(self::ATTR_MADE_BY_VERSION), 'version made by', 2],
            [self::pack16le(self::VERSION_TO_EXTRACT), 'version needed to extract', 2],
            [self::pack32le(0), 'number of this disk', 4],
            [self::pack32le(0), 'number of the disk with the start of the central directory', 4],
            [self::pack64le($cdRecCount), 'total number of entries in the central directory on this disk', 8],
            [self::pack64le($cdRecCount), 'total number of entries in the central directory', 8],
            [self::pack64le($cdRecLength), 'size of the central directory', 8],
            [self::pack64le($cdStartOffset), 'offset of start of central directory with respect to the starting disk number', 8],
            ['', 'zip64 extensible data sector', 'v']
        ];

        return self::generateHeader($fields, 'ZIP64 END of CENTRAL DIRECTORY RECORD');
    }


    protected static function buildZip64EndOfCentralDirectoryLocator($cdStartOffset, $cdRecLength) {
        $zip64RecStart = $cdStartOffset + $cdRecLength;

        $fields = [
            [self::pack32le(self::ZIP64_END_OF_CENTRAL_DIR_LOCATOR), 'zip64 end of central dir locator signature', 4],
            [self::pack32le(0), 'number of the disk with the start of the zip64 end of central directory', 4],
            [self::pack64le($zip64RecStart), 'relative offset of the zip64 end of central directory record', 8],
            [self::pack32le(1), 'total number of disks', 4],
        ];

        return self::generateHeader($fields, 'ZIP64 END of CENTRAL DIRECTORY LOCATOR');
    }


    protected static function buildEndOfCentralDirectoryRecord($cdStartOffset, $cdRecCount, $cdRecLength) {
        if (self::$isZip64) {
            $diskNumber    = -1;
            $cdRecCount    = -1;
            $cdRecLength   = -1;
            $cdStartOffset = -1;
        }
        else {
            $diskNumber = 0;
        }

        $fields = [
            [self::pack32le(self::ZIP_END_OF_CENTRAL_DIRECTORY), 'end of central dir signature', 4],
            [self::pack16le($diskNumber), 'number of this disk', 2],
            [self::pack16le($diskNumber), 'number of the disk with the start of the central directory', 2],
            [self::pack16le($cdRecCount), 'total number of entries in the central directory on this disk', 2],
            [self::pack16le($cdRecCount), 'total number of entries in the central directory', 2],
            [self::pack32le($cdRecLength), 'size of the central directory', 4],
            [self::pack32le($cdStartOffset), 'offset of start of central directory with respect to the starting disk number', 4],
            [self::pack16le(0), 'ZIP file comment length', 2],
            ['', 'ZIP file comment', 'v']
        ];

        return self::generateHeader($fields, 'END of CENTRAL DIRECTORY RECORD');
    }


    protected static function getDosTime($timestamp = 0) {
        $timestamp = (int)$timestamp;
        $oldTZ     = date_default_timezone_get();
        date_default_timezone_set('UTC');
        $date      = ($timestamp == 0 ? getdate() : getdate($timestamp));
        date_default_timezone_set($oldTZ);
        if ($date['year'] >= 1980) {
            return (($date['mday'] + ($date['mon'] << 5) + (($date['year'] - 1980) << 9)) << 16) | (($date['seconds'] >> 1) + ($date['minutes'] << 5) + ($date['hours'] << 11));
        }
        return 0x0000;
    }


}
