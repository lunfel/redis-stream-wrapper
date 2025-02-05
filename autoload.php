<?php

use Lunfel\RedisStreamWrapper\RedisStreamWrapper;

include __DIR__ . '/vendor/autoload.php';

if (! in_array('redis_stream', stream_get_wrappers())) {
    stream_wrapper_register('redis', RedisStreamWrapper::class);
}