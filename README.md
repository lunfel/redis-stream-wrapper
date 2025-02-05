# Redis Stream Wrapper for PHP

## Overview

This package provides a PHP stream wrapper for Redis, allowing seamless integration of Redis streams with PHP's native file handling functions (`fopen`, `fwrite`, `fread`, etc.).

## Features

- Read and write to Redis streams using PHP's built-in stream functions.
- Support for `fopen`, `fread`, `fwrite`, `fclose`, and other standard PHP file operations.
- Handles Redis streams (`XADD`, `XREAD`, etc.) in a transparent way.
- Supports blocking and non-blocking reads.

## Requirements

- PHP 8.1+
- Redis 5+ (Only tested Redis 7)
- PHP Redis extension (`ext-redis`)

## Installation

You can install the package using Composer:

```sh
composer lunfel/redis-stream-wrapper
```

## Usage

### Register the Stream Wrapper

Before using the stream wrapper, you need to register it:

```php
stream_wrapper_register("redis", \Lunfel\RedisStreamWrapper\RedisStreamWrapper::class);
```

### Configuring the connection

#### Configuration reference

```php
$options = [
    'redis' => [
        // If not provided, defaults to `new Redis()`
        // Supports only phpredis redis client
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
            // Prefix to add to stream key
            // For example, with prefix set to "mystreams:"
            // The path redis://my/important/file.log would be
            // translated into redis key "mystreams:my/important/file.log"
            // default is empty string ''
            'key_prefix' => 'mystreams:',
            // The wrapper uses an internal buffer to
            // manage the data locally. This tells the wrapper
            // what kind of buffer to operate with.
            // Defaults to 'php://temp'
            'internal_stream_uri' => 'php://temp'
        ],
        'events' => [
            // If provided, will execute this callback before
            // fclose is called on the stream.
            // You do not need to close the connection to redis
            // the wrapper will take care of it.
            'before_stream_close' => function (Redis $redis, string $redisKeyToStream) {
                // You can do whatever you want before closing the stream. You
                // can set an expiration time on the key or delete the stream
                //
                // Ex: Expire the key after 60 seconds
                // $redis->expire($redisKeyToStream, 60);
            }
        ]
    ],
]
```

#### With Laravel

```php

// Default Redis connection
$options = [
    'redis' => [
        'client' => function (): Redis {
            return Redis::client();
        },
        // ...
    ],
];

// Specific Redis connection
$options = [
    'redis' => [
        'client' => function (): Redis {
            return Redis::connection('cache')
                ->client();
        },
        // ...
    ],
];
```

#### Passing the options to the stream

```php
// Global configuration

stream_context_set_default($options);

$handle = fopen('redis://important/streams/magic.txt', 'w');

// Per wrapper configuration

$context = stream_context_create($options)

$handle = fopen('redis://important/streams/magic.txt', 'w', $context);
```

### Writing to a Redis Stream

```php
$stream = fopen("redis://test", "w");
fwrite($stream, 'Hello, Redis!'));
fclose($stream);
```

### Reading from a Redis Stream

```php
$stream = fopen("redis://test", "r");
while (($data = fgets($stream)) !== false) {
    echo "Received: " . $data;
}
fclose($stream);
```

### License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.