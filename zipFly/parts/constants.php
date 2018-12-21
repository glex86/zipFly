<?php
/**
 * This file is a part of G-Lex's zipFly compression library
 */

namespace zipFly\parts;

/**
 * Base constants of the zipFly compressor
 */
class constants {
    /**************************************************************************
     * COMPRESSION METHODS
     */
    /** @const Compression method: STORE */
    const METHOD_STORE      = 0;

    /** @const Compression method: DEFALTE */
    const METHOD_DEFLATE    = 8;

    /** @const Compression method: BZIP2 */
    const METHOD_BZIP2      = 12;


    /**************************************************************************
     * COMPRESSION LEVELS
     */
    /** @const Compression level: MINIMUM */
    const LEVEL_MIN     = 0x10000;

    /** @const Compression level: NORMAL */
    const LEVEL_NORMAL  = 0x20000;

    /** @const Compression method: MAXIMUM */
    const LEVEL_MAX     = 0x30000;


    /**************************************************************************
     * DUPLICATE ENTRY FILTER
     */
    /** @const Duplicate entry filter: none */
    const DE_NONE       = 0;

    /** @const Duplicate entry filter: based on the md5 hash of the inArchive file path */
    const DE_MD5        = 1;

    /** @const Duplicate entry filter: based on the full inArchive file path */
    const DE_FULLPATH   = 2;


    /**************************************************************************
     * GENERAL PURPOSE BIT FLAGS
     */
    /** @const no flags set */
    const GPFLAG_NONE   = 0x0000;

    /** @const compression flag 1 (compression settings, see APPNOTE for details)  // 0000 0000 0000 0010  // BIT1 */
    const GPFLAG_COMP1  = 0x0002;

    /** @const compression flag 2 (compression settings, see APPNOTE for details)  // 0000 0000 0000 0100  // BIT2 */
    const GPFLAG_COMP2  = 0x0004;

    /** @const ADD flag (sizes and crc32 are append in data descriptor) // 0000 0000 0000 1000  // BIT3 */
    const GPFLAG_ADD    = 0x0008;

    /** @const EFS flag (UTF-8 encoded filename and/or comment) // 0000 1000 0000 0000  // BIT11 */
    const GPFLAG_EFS    = 0x0800;


    /**************************************************************************
     * ZIP HEADER SIGNATURES
     */
    /** @const zip - local file header signature */
    const ZIP_LOCAL_FILE_HEADER                  = 0x04034b50;

    /** @const zip - central file header signature */
    const ZIP_CENTRAL_FILE_HEADER                = 0x02014b50;

    /** @const zip - end of central directory record */
    const ZIP_END_OF_CENTRAL_DIRECTORY           = 0x06054b50;

    /** @const zip - data descriptor header */
    const ZIP_STREAM_DATA_DESCRIPTOR             = 0x08074b50;

    /** @const zip64 - end of central directory record */
    const ZIP64_END_OF_CENTRAL_DIRECTORY         = 0x06064b50;

    /** @const zip64 - end of central directory locator */
    const ZIP64_END_OF_CENTRAL_DIR_LOCATOR       = 0x07064b50;

    /** @const Zip64 - extended information extra field */
    const ZIP64_EXTENDED_INFORMATION_EXTRA_FIELD = 0x0001;


    /**************************************************************************
     * OTHER CONSTANTS
     */
    /** @const Stream buffer size 16 * 65535 = almost 1mb chunks, for best deflate performance */
    const STREAM_CHUNK_SIZE     = 1048576;

    /** @const made by version  (upper byte: UNIX, lower byte v4.5) */
    const ATTR_MADE_BY_VERSION  = 63; //

    /** @const Version need to extract */
    const VERSION_TO_EXTRACT    = 46;


    /** @var array Compressor parameters */
    protected static $compressorParams = [
        self::METHOD_DEFLATE => [
            self::LEVEL_MIN    => ['level' => 1],
            self::LEVEL_NORMAL => ['level' => 6],
            self::LEVEL_MAX    => ['level' => 9],
        ]
    ];


}
