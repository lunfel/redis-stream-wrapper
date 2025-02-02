<?php

use Lunfel\RedisStreamWrapper\RedisStreamWrapper;

include __DIR__ . '/vendor/autoload.php';

if (! in_array('redis_stream', stream_get_wrappers())) {
    stream_wrapper_register('redis', RedisStreamWrapper::class);
}

stream_context_set_default([
    'redis' => [
        'client' => function (): Redis {
            static $client = new Redis([
                'host' => 'redis',
                'port' => 6379,
                'connectTimeout' => 2.5,
                // 'auth' => ['phpredis', 'phpredis'],
                'backoff' => [
                    'algorithm' => Redis::BACKOFF_ALGORITHM_DECORRELATED_JITTER,
                    'base' => 500,
                    'cap' => 750,
                ],
            ]);

            return $client;
        },
        'configurations' => [
            // prefix to add to stream key
            // For example, with prefix set to "mystreams:"
            // The path redis://my/important/file.log would be
            // translated into redis key "mystreams:my/important/file.log"
            'key_prefix' => 'streams:',
            'internal_stream_uri' => 'php://temp'
        ],
        'events' => [
            'before_stream_close' => function (Redis $redis, string $redisKeyToStream) {
                // You can do whatever you want before closing the stream. You
                // can set an expiration time on the key or delete the stream
                //
                // Ex: Expire the key after 60 seconds
                // $redis->expire($redisKeyToStream, 60);
            }
        ]
    ],
]);