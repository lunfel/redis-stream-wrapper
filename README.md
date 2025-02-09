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
            // Prefix added to the Redis stream key.
            // Example: If the prefix is "mystreams:", the path "redis://my/important/file.log"
            // becomes the Redis key "mystreams:my/important/file.log".
            // Default: empty string ('').
            'key_prefix' => 'mystreams:',
            // Specifies the type of internal buffer used by the wrapper to manage data locally.
            // Default: 'php://temp'.
            'internal_stream_uri' => 'php://temp',
            // If set, the Redis stream starts reading from this ID.  
            // Useful for resuming from where you left off.  
            // The `after_read` event provides `lastMessageId`,  
            // which can be used as `start_id` to continue reading.
            'start_id' => '0'
        ],
        'events' => [
            // If set, this callback executes before `fclose` is called on the stream.  
            // No need to close the Redis connection manually—the wrapper handles it.
            'before_stream_close' => function (string $redisKeyToStream, Redis $redis) {
                // Perform any actions before closing the stream, such as setting an expiration 
                // or deleting the stream.
                //
                // Example: Set the key to expire after 60 seconds
                // $redis->expire($redisKeyToStream, 60);
            },
            // If set, this callback executes after reading from the stream.  
            // Useful for retrieving the last message ID, which is needed  
            // to continue reading from that point onward.
            'after_read' => function (string $lastMessageId, string $redisKeyToStream, Redis $redis) {
                //
            }
        ]
    ],
]
```

#### With Laravel

```php
use Illuminate\Support\Facades\Redis as LaravelRedisFacade;

// Default Redis connection
$options = [
    'redis' => [
        'client' => function (): Redis {
            return LaravelRedisFacade::client();
        },
        // ...
    ],
];

// Specific Redis connection
$options = [
    'redis' => [
        'client' => function (): Redis {
            return LaravelRedisFacade::connection('cache')
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