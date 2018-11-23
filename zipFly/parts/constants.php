<?php
/**
 * This file is a part of G-Lex's zipFly compression library
 */

namespace zipFly\parts;

/**
 * Base constants of the zipFly compressor
 */
class constants {
    /**/

    const METHOD_STORE   = 0;
    const METHOD_DEFLATE = 8;
    const METHOD_BZIP2   = 12;

    /**/
    const LEVEL_MIN    = 0;
    const LEVEL_NORMAL = 1;
    const LEVEL_MAX    = 2;

    /**/
    const GPFLAG_NONE  = 0x0000; // no flags set
    const GPFLAG_COMP1 = 0x0002; // compression flag 1 (compression settings, see APPNOTE for details)  // 0000 0000 0000 0010  // BIT1
    const GPFLAG_COMP2 = 0x0004; // compression flag 2 (compression settings, see APPNOTE for details)  // 0000 0000 0000 0100  // BIT2
    const GPFLAG_ADD   = 0x0008; // ADD flag (sizes and crc32 are append in data descriptor)            // 0000 0000 0000 1000  // BIT3
    const GPFLAG_EFS   = 0x0800; // EFS flag (UTF-8 encoded filename and/or comment)                    // 0000 1000 0000 0000  // BIT11

    /**/
    const ZIP_LOCAL_FILE_HEADER            = 0x04034b50; // local file header signature
    const ZIP_CENTRAL_FILE_HEADER          = 0x02014b50; // central file header signature
    const ZIP_END_OF_CENTRAL_DIRECTORY     = 0x06054b50;  // end of central directory record
    const ZIP64_END_OF_CENTRAL_DIRECTORY   = 0x06064b50;  // zip64 end of central directory record
    const ZIP64_END_OF_CENTRAL_DIR_LOCATOR = 0x07064b50;  // zip64 end of central directory locator
    const ZIP_STREAM_DATA_DESCRIPTOR       = 0x08074b50;  // data descriptor header

    /**/
    const STREAM_CHUNK_SIZE = 1048576; // 16 * 65535 = almost 1mb chunks, for best deflate performance //1048576
    const ATTR_MADE_BY_VERSION = 63; // made by version  (upper byte: UNIX, lower byte v4.5)


}
