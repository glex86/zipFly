<?php

/**
 * This file is a part of G-Lex's zipFly compression library
 */
class hashStream extends \php_user_filter {

    private $hash;

    public function onCreate() {
        $this->hash      = hash_init('crc32b');
        $this->params[1] = 0;
        return true;
    }


    public function onClose() {
        $this->params[0] = unpack('N', hash_final($this->hash, true))[1];
    }


    function filter($in, $out, &$consumed, $closing) {
        while ($bucket = stream_bucket_make_writeable($in)) {
            $consumed        += $bucket->datalen;
            $this->params[1] += $bucket->datalen;
            hash_update($this->hash, $bucket->data);
            stream_bucket_prepend($out, $bucket);
        }

        return PSFS_PASS_ON;
    }


}

stream_filter_register("hash-stream", "hashStream");
