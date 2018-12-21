<?php

/**
 * This file is a part of G-Lex's zipFly compression library
 */
class hashStream extends \php_user_filter {

    private $hash;


    /**
     * Called when creating the filter
     * <p>This method is called during instantiation of the filter class object. If your filter allocates or initializes any other resources (such as a buffer), this is the place to do it.</p><p>When your filter is first instantiated, and <i>yourfilter-&gt;onCreate()</i> is called, a number of properties will be available as shown in the table below.</p><p></p>
     * @return bool <p>Your implementation of this method should return <b><code>FALSE</code></b> on failure, or <b><code>TRUE</code></b> on success.</p>
     * @link http://php.net/manual/en/php-user-filter.oncreate.php
     */
    public function onCreate() {
        $this->hash      = hash_init('crc32b');
        $this->params[1] = 0;
        return true;
    }


    /**
     * Called when closing the filter
     * <p>This method is called upon filter shutdown (typically, this is also during stream shutdown), and is executed <i>after</i> the <i>flush</i> method is called. If any resources were allocated or initialized during <i>onCreate()</i> this would be the time to destroy or dispose of them.</p>
     * @return void <p>Return value is ignored.</p>
     * @link http://php.net/manual/en/php-user-filter.onclose.php
     */
    public function onClose() {
        $this->params[0] = unpack('N', hash_final($this->hash, true))[1];
    }


    /**
     * Called when applying the filter
     * <p>This method is called whenever data is read from or written to the attached stream (such as with <code>fread()</code> or <code>fwrite()</code>).</p>
     * @param resource $in <p><code>in</code> is a resource pointing to a <i>bucket brigade</i> which contains one or more <i>bucket</i> objects containing data to be filtered.</p>
     * @param resource $out <p><code>out</code> is a resource pointing to a second <i>bucket brigade</i> into which your modified buckets should be placed.</p>
     * @param int $consumed <p><code>consumed</code>, which must <i>always</i> be declared by reference, should be incremented by the length of the data which your filter reads in and alters. In most cases this means you will increment <code>consumed</code> by <i>$bucket-&gt;datalen</i> for each <i>$bucket</i>.</p>
     * @param bool $closing <p>If the stream is in the process of closing (and therefore this is the last pass through the filterchain), the <code>closing</code> parameter will be set to <b><code>TRUE</code></b>.</p>
     * @return int <p>The <b>filter()</b> method must return one of three values upon completion.</p>   Return Value Meaning     <b><code>PSFS_PASS_ON</code></b>  Filter processed successfully with data available in the <code>out</code> <i>bucket brigade</i>.    <b><code>PSFS_FEED_ME</code></b>  Filter processed successfully, however no data was available to return. More data is required from the stream or prior filter.    <b><code>PSFS_ERR_FATAL</code></b> (default)  The filter experienced an unrecoverable error and cannot continue.
     * @link http://php.net/manual/en/php-user-filter.filter.php
     */
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
