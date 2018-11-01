<?php
/**
 * This file is a part of G-Lex's zipFly compression library
 */

class hashStream extends \php_user_filter {

    private $callback;
    private $hash;
    private $length;


    public function onCreate() {
        if (!is_callable($this->params)) {
            throw new InvalidArgumentException('No valid callback parameter given to stream_filter_(append|prepend)');
        }

        $this->callback = $this->params;
        $this->hash     = hash_init('crc32b');
        $this->length   = 0;
        return true;
    }


    public function onClose() {
        $hashData       = unpack('N', hash_final($this->hash, true));
        call_user_func($this->callback, $hashData, $this->length);
        $this->callback = null;
    }


    function filter($in, $out, &$consumed, $closing) {
        $localBuffer = '';
        while ($bucket      = stream_bucket_make_writeable($in)) {
            $consumed    += $bucket->datalen;
            $localBuffer .= $bucket->data;
            stream_bucket_prepend($out, $bucket);
        }

        $this->length += strlen($localBuffer);
        hash_update($this->hash, $localBuffer);

        return PSFS_PASS_ON;
    }


}

stream_filter_register("hash-stream", "hashStream");
