<?php

namespace zipFly\parts;
use zipFly\parts\constants;

class debugger {
    const TYPE_INT = 1;
    const TYPE_STR = 2;
    const TYPE_BIT = 3;


    const FIELD_NAME     = 0;
    const FIELD_SIZE     = 1;
    const FIELD_TYPE     = 2;
    const FIELD_OFFSET   = 3;
    const FIELD_REALSIZE = 4;
    const FIELD_STRVAL   = 5;
    const FIELD_HEXVAL   = 6;
    const FIELD_INTVAL   = 7;

    public static $prefix = '';


    static $headers = [
        constants::ZIP_LOCAL_FILE_HEADER => [
            'name'   => 'ZIP - LOCAL FILE HEADER',
            'fields' => [
                ['local file header signature', 4],
                ['version needed to extract', 2, self::TYPE_INT],
                ['general purpose bit flag', 2, self::TYPE_BIT],
                ['compression method', 2, self::TYPE_INT],
                ['last mod file time + last mod file date ', 4],
                ['crc-32', 4, self::TYPE_INT],
                ['compressed size', 4, self::TYPE_INT],
                ['uncompressed size', 4, self::TYPE_INT],
                ['file name length', 2, self::TYPE_INT],
                ['extra field length', 2, self::TYPE_INT],
                ['file name', 'v', self::TYPE_STR],
                ['extra field', 'v']
            ]
        ],
        constants::ZIP64_EXTENDED_INFORMATION_EXTRA_FIELD => [
            'name'   => 'ZIP64 - EXTENDED INFORMATION EXTRA FIELD',
            'fields' => [
                ['tag for this "extra" block type (ZIP64)', 2],
                ['size of this "extra" block', 2, self::TYPE_INT],
                ['original uncompressed file size', 8, self::TYPE_INT],
                ['size of compressed data', 8, self::TYPE_INT],
                ['offset of local header record', 8, self::TYPE_INT],
                ['number of the disk on which this file starts', 4, self::TYPE_INT],
            ],
        ],
        constants::ZIP_CENTRAL_FILE_HEADER                => [
            'name'   => 'ZIP - CENTRAL FILE HEADER',
            'fields' => [
                ['central file header signature', 4],
                ['version made by', 2, self::TYPE_INT],
                ['version needed to extract', 2, self::TYPE_INT],
                ['general purpose bit flag', 2, self::TYPE_BIT],
                ['compression method', 2, self::TYPE_INT],
                ['last mod file time + last mod file date', 4],
                ['crc-32', 4, self::TYPE_INT],
                ['compressed size', 4, self::TYPE_INT],
                ['uncompressed size', 4, self::TYPE_INT],
                ['file name length', 2, self::TYPE_INT],
                ['extra field length', 2, self::TYPE_INT],
                ['file comment length', 2, self::TYPE_INT],
                ['disk number start', 2, self::TYPE_INT],
                ['internal file attributes', 2],
                ['external file attributes', 4],
                ['relative offset of local header', 4, self::TYPE_INT],
                ['file name', 'v', self::TYPE_STR],
                ['extra field', 'v'],
                ['file comment', 'v', self::TYPE_STR]
            ],
        ],
        constants::ZIP64_END_OF_CENTRAL_DIRECTORY         => [
            'name'   => 'ZIP64 - END OF CENTRAL DIRECTORY HEADER',
            'fields' => [
                ['zip64 end of central dir signature', 4],
                ['size of zip64 end of central directory record', 8, self::TYPE_INT],
                ['version made by', 2, self::TYPE_INT],
                ['version needed to extract', 2, self::TYPE_INT],
                ['number of this disk', 4, self::TYPE_INT],
                ['number of the disk with the start of the central directory', 4, self::TYPE_INT],
                ['total number of entries in the central directory on this disk', 8, self::TYPE_INT],
                ['total number of entries in the central directory', 8, self::TYPE_INT],
                ['size of the central directory', 8, self::TYPE_INT],
                ['offset of start of central directory with respect to the starting disk number', 8, self::TYPE_INT],
                ['zip64 extensible data sector', 'v']
            ],
        ],
        constants::ZIP64_END_OF_CENTRAL_DIR_LOCATOR       => [
            'name'   => 'ZIP64 - END OF CENTRAL DIRECTORY LOCATOR',
            'fields' => [
                ['zip64 end of central dir locator signature', 4],
                ['number of the disk with the start of the zip64 end of central directory', 4, self::TYPE_INT],
                ['relative offset of the zip64 end of central directory record', 8, self::TYPE_INT],
                ['total number of disks', 4, self::TYPE_INT],
            ],
        ],
        constants::ZIP_END_OF_CENTRAL_DIRECTORY           => [
            'name'   => 'ZIP - END OF CENTRAL DIRECTORY HEADER',
            'fields' => [
                ['end of central dir signature', 4],
                ['number of this disk', 2, self::TYPE_INT],
                ['number of the disk with the start of the central directory', 2, self::TYPE_INT],
                ['total number of entries in the central directory on this disk', 2, self::TYPE_INT],
                ['total number of entries in the central directory', 2, self::TYPE_INT],
                ['size of the central directory', 4, self::TYPE_INT],
                ['offset of start of central directory with respect to the starting disk number', 4, self::TYPE_INT],
                ['ZIP file comment length', 2, self::TYPE_INT],
                ['ZIP file comment', 'v', self::TYPE_STR]
            ]
        ],
        constants::ZIP_STREAM_DATA_DESCRIPTOR => [
            'name'=> 'ZIP - STREAM DATA DESCRIPTOR',
            'fields' => [
                ['Extended Local file header signature', 4],
                ['CRC-32', 4, self::TYPE_INT],
                ['Compressed size', 8, self::TYPE_INT],
                ['Uncompressed size', 8, self::TYPE_INT]
            ]
        ]
    ];


    private static function hexDump($value) {
        $res = unpack('H*', $value)[1];
        $res = str_split($res, 2);
        $res = implode(' ', $res);

        return $res;
    }

    public static function setZip64($isZip64) {
        if ($isZip64) {
            self::$headers[constants::ZIP_STREAM_DATA_DESCRIPTOR]['fields'][2][self::FIELD_SIZE] = 8;
            self::$headers[constants::ZIP_STREAM_DATA_DESCRIPTOR]['fields'][3][self::FIELD_SIZE] = 8;
        } else {
            self::$headers[constants::ZIP_STREAM_DATA_DESCRIPTOR]['fields'][2][self::FIELD_SIZE] = 4;
            self::$headers[constants::ZIP_STREAM_DATA_DESCRIPTOR]['fields'][3][self::FIELD_SIZE] = 4;
        }
    }


    public static function identifyHeader($header) {
        $signature16 = unpack('v', substr($header, 0, 2))[1];
        $signature32 = unpack('V', substr($header, 0, 4))[1];

        foreach (self::$headers as $signature=>$data) {
            if ($data['fields'][0][self::FIELD_SIZE] === 4 && $signature == $signature32) {
                return $signature;
            }

            if ($data['fields'][0][self::FIELD_SIZE] === 2 && $signature == $signature16) {
                return $signature;
            }
        }

        return false;
    }

    function parseHeaderData($header, $signature) {
        $headerData = self::$headers[$signature];

        $offset = 0;
        foreach ($headerData['fields'] as $key=>&$field) {
            $size = $field[self::FIELD_SIZE];
            if ($size === 'v') {
                switch (true) {
                    case ($signature == constants::ZIP_LOCAL_FILE_HEADER && $key == 10):
                        $size = $headerData['fields'][8][self::FIELD_INTVAL];
                        break;

                    case ($signature == constants::ZIP_LOCAL_FILE_HEADER && $key == 11):
                        $size = $headerData['fields'][9][self::FIELD_INTVAL];
                        break;

                    case ($signature == constants::ZIP_CENTRAL_FILE_HEADER && $key == 16):
                        $size = $headerData['fields'][9][self::FIELD_INTVAL];
                        break;

                    case ($signature == constants::ZIP_CENTRAL_FILE_HEADER && $key == 17):
                        $size = $headerData['fields'][10][self::FIELD_INTVAL];
                        break;

                    case ($signature == constants::ZIP_CENTRAL_FILE_HEADER && $key == 18):
                        $size = $headerData['fields'][11][self::FIELD_INTVAL];
                        break;

                    case ($signature == constants::ZIP64_END_OF_CENTRAL_DIRECTORY && $key == 10):
                        $size = $headerData['fields'][1][self::FIELD_INTVAL]-44;
                        break;

                    case ($signature == constants::ZIP_END_OF_CENTRAL_DIRECTORY && $key == 8):
                        $size = $headerData['fields'][7][self::FIELD_INTVAL];
                        break;

                    default:
                        echo "\n--UNKNOWN V FIELD--\n";
                }

                $field[self::FIELD_SIZE] = '? '.$size;
            }

            $field[self::FIELD_OFFSET] = $offset;
            $field[self::FIELD_REALSIZE] = $size;
            $field[self::FIELD_STRVAL] = substr($header, $offset, $size);
            $field[self::FIELD_HEXVAL] = self::hexDump($field[self::FIELD_STRVAL]);
            $offset += $size;

            switch ($field[self::FIELD_SIZE]) {
                case 2:
                    $field[self::FIELD_INTVAL] = unpack('s', $field[self::FIELD_STRVAL])[1];
                    break;

                case 4:
                    $field[self::FIELD_INTVAL] = unpack('l', $field[self::FIELD_STRVAL])[1];
                    break;

                case 8:
                    $field[self::FIELD_INTVAL] = unpack('q', $field[self::FIELD_STRVAL])[1];
                    break;
            }
        }


        return $headerData;
    }


    public static function debugHeader($header) {
        $signature = self::identifyHeader($header);

        if ($signature === false) {
            return false;
        }

        $headerInfo = self::parseHeaderData($header, $signature);

        echo self::$prefix, "\n";
        printf(self::$prefix."[ %s ] (Total size: %d bytes)\n", $headerInfo['name'], strlen($header));
        echo $separator = self::$prefix.'+'.str_repeat('-', 18).'+'.str_repeat('-', 45).'+'.str_repeat('-', 100)."\n";

        foreach ($headerInfo['fields'] as $field) {
            if (isset($field[self::FIELD_TYPE]) && $field[self::FIELD_TYPE] == self::TYPE_INT) {
                $data = sprintf('%-23.23s %19s', $field[self::FIELD_HEXVAL], '('.$field[self::FIELD_INTVAL].')');
            }
            elseif (isset($field[self::FIELD_TYPE]) && $field[self::FIELD_TYPE] == self::TYPE_STR) {
                $data = sprintf('%-43.43s', $field[self::FIELD_STRVAL]);
            }
            elseif (isset($field[self::FIELD_TYPE]) && $field[self::FIELD_TYPE] == self::TYPE_BIT) {
                $res = implode(' ', str_split(sprintf('%016b', $field[self::FIELD_INTVAL]), 8));
                $data = sprintf('%-23.23s %s', $field[self::FIELD_HEXVAL], '('.$res.')');
            }
            else {
                $data = sprintf('%-23.23s %19s', $field[self::FIELD_HEXVAL], ' ');
            }

            printf(self::$prefix."| %3d + %-4s bytes | %s | %s\n",
                    $field[self::FIELD_OFFSET], $field[self::FIELD_SIZE], $data, $field[self::FIELD_NAME]);
        }
        echo $separator, self::$prefix, "\n";
    }


    public static function addBlock($title) {
        $titleLength = strlen($title);

        echo self::$prefix."\n";
        echo self::$prefix." +".str_repeat('-', $titleLength)."-+\n";
        echo self::$prefix." | {$title} \\\n";
        echo self::$prefix." +--".str_repeat('-', $titleLength).'-+'.str_repeat('-', 164 - $titleLength)."-\n";
        self::$prefix .= ' |   ';
        echo self::$prefix."\n";
    }


    public static function endBlock() {
        self::$prefix = substr(self::$prefix, 0, -6);
        echo self::$prefix.' +'.str_repeat('=', 169)."\n";
        echo self::$prefix."\n";
        echo self::$prefix."\n";
    }
}
