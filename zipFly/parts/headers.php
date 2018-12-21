<?php
/**
 * This file is a part of G-Lex's zipFly compression library
 */

namespace zipFly\parts;

trait headers {

    private static function pack16le($data) {
        return pack('v', $data);
    }


    private static function pack32le($data) {
        return pack('V', $data);
    }


    private static function pack64le($data) {
        return pack('P', $data);
    }


    private static function buildZip64ExtendedInformationField($startOffset, $uncompressedSize = 0, $compressedSize = 0) {
        $fields = self::pack16le(self::ZIP64_EXTENDED_INFORMATION_EXTRA_FIELD)  // 2 - tag for this "extra" block type (ZIP64)
                .self::pack16le(28)                                             // 2 - size of this "extra" block
                .self::pack64le($uncompressedSize)                              // 8 - original uncompressed file size
                .self::pack64le($compressedSize)                                // 8 - size of compressed data
                .self::pack64le($startOffset)                                   // 8 - offset of local header record
                .self::pack32le(0);                                             // 4 - number of the disk on which this file starts

        if (self::$isDebugMode) {
            debugger::debugHeader($fields);
        }

        return $fields;
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

        $fields = self::pack32le(self::ZIP_LOCAL_FILE_HEADER)   // 4 - local file header signature
                .self::pack16le(self::VERSION_TO_EXTRACT)       // 2 - version needed to extract
                .self::pack16le($gpFlags)                       // 2 - general purpose bit flag
                .self::pack16le($compressionMethod)             // 2 - compression method
                .self::pack32le($dosTime)                       // 4 - last mod file time + last mod file date
                .self::pack32le($dataCRC32)                     // 4 - crc-32
                .self::pack32le($compressedSize)                // 4 - compressed size
                .self::pack32le($uncompressedSize)              // 4 - uncompressed size
                .self::pack16le(strlen($fileName))              // 2 - file name length
                .self::pack16le(strlen($zip64Ext))              // 2 - extra field length
                .$fileName                                      // 'v' - file name
                .$zip64Ext;                                     // 'v' - extra field

        if (self::$isDebugMode) {
            debugger::debugHeader($fields);
        }

        return $fields;
    }


    protected static function getDataDescriptor($uncompressedSize, $compressedSize, $dataCRC32) {
        if (self::$isZip64) {
            $packedCompressedSize   = self::pack64le($compressedSize);
            $packedUncompressedSize = self::pack64le($uncompressedSize);
        }
        else {
            $packedCompressedSize   = self::pack32le($compressedSize);
            $packedUncompressedSize = self::pack32le($uncompressedSize);
        }

        $fields = self::pack32le(self::ZIP_STREAM_DATA_DESCRIPTOR)  // 4 - Extended Local file header signature
                .self::pack32le($dataCRC32)                         // 4 - CRC-32
                .$packedCompressedSize                              // 4/8 - Compressed size
                .$packedUncompressedSize;                           // 4/8 - Uncompressed size

        if (self::$isDebugMode) {
            debugger::debugHeader($fields);
        }

        return $fields;
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
            $zip64Ext = '';
            $diskNo   = 0;
        }

        //hack
        $extFileAttr = 32;

        $fields = self::pack32le(self::ZIP_CENTRAL_FILE_HEADER) // 4 - central file header signature
                .self::pack16le(self::ATTR_MADE_BY_VERSION)     // 2 - version made by
                .self::pack16le(self::VERSION_TO_EXTRACT)       // 2 - version needed to extract
                .self::pack16le($gpFlags)                       // 2 - general purpose bit flag
                .self::pack16le($compressionMethod)             // 2 - compression method
                .self::pack32le($dosTime)                       // 4 - last mod file time + last mod file date
                .self::pack32le($dataCRC32)                     // 4 - crc-32
                .self::pack32le($compressedSize)                // 4 - compressed size
                .self::pack32le($uncompressedSize)              // 4 - uncompressed size
                .self::pack16le(strlen($fileName))              // 2 - file name length
                .self::pack16le(strlen($zip64Ext))              // 2 - extra field length
                .self::pack16le(0)                              // 2 - file comment length
                .self::pack16le($diskNo)                        // 2 - disk number start
                .self::pack16le(0)                              // 2 - internal file attributes
                .self::pack32le($extFileAttr)                   // 4 - external file attributes
                .self::pack32le($startOffset)                   // 4 - relative offset of local header
                .$fileName                                      // 'v' - file name
                .$zip64Ext                                      // 'v' - extra field
                .'';                                            // 'v' - file comment

        if (self::$isDebugMode) {
            debugger::debugHeader($fields);
        }

        return $fields;
    }


    protected static function buildZip64EndOfCentralDirectoryRecord($cdStartOffset, $cdRecCount, $cdRecLength) {
        $fields = self::pack32le(self::ZIP64_END_OF_CENTRAL_DIRECTORY)  // 4 - zip64 end of central dir signature
                .self::pack64le(44)                                     // 8 - size of zip64 end of central directory record
                .self::pack16le(self::ATTR_MADE_BY_VERSION)             // 2 - version made by
                .self::pack16le(self::VERSION_TO_EXTRACT)               // 2 - version needed to extract
                .self::pack32le(0)                                      // 4 - number of this disk
                .self::pack32le(0)                                      // 4 - number of the disk with the start of the central directory
                .self::pack64le($cdRecCount)                            // 8 - total number of entries in the central directory on this disk
                .self::pack64le($cdRecCount)                            // 8 - total number of entries in the central directory
                .self::pack64le($cdRecLength)                           // 8 - size of the central directory
                .self::pack64le($cdStartOffset)                         // 8 - offset of start of central directory with respect to the starting disk number
                .'';                                                    // 'v' - zip64 extensible data sector

        if (self::$isDebugMode) {
            debugger::debugHeader($fields);
        }

        return $fields;
    }


    protected static function buildZip64EndOfCentralDirectoryLocator($cdStartOffset, $cdRecLength) {
        $zip64RecStart = $cdStartOffset + $cdRecLength;

        $fields = self::pack32le(self::ZIP64_END_OF_CENTRAL_DIR_LOCATOR)    // 4 - zip64 end of central dir locator signature
                .self::pack32le(0)                                          // 4 - number of the disk with the start of the zip64 end of central directory
                .self::pack64le($zip64RecStart)                             // 8 - relative offset of the zip64 end of central directory record
                .self::pack32le(1);                                         // 4 - total number of disks

        if (self::$isDebugMode) {
            debugger::debugHeader($fields);
        }

        return $fields;
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

        $fields = self::pack32le(self::ZIP_END_OF_CENTRAL_DIRECTORY)    // 4 - end of central dir signature
                .self::pack16le($diskNumber)                            // 2 - number of this disk
                .self::pack16le($diskNumber)                            // 2 - number of the disk with the start of the central directory
                .self::pack16le($cdRecCount)                            // 2 - total number of entries in the central directory on this disk
                .self::pack16le($cdRecCount)                            // 2 - total number of entries in the central directory
                .self::pack32le($cdRecLength)                           // 4 - size of the central directory
                .self::pack32le($cdStartOffset)                         // 4 - offset of start of central directory with respect to the starting disk number
                .self::pack16le(0)                                      // 2 - ZIP file comment length
                .'';                                                    // 'v' - ZIP file comment

        if (self::$isDebugMode) {
            debugger::debugHeader($fields);
        }

        return $fields;
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
