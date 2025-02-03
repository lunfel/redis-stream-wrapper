# Redis Stream Wrapper for PHP

## Overview

This package provides a PHP stream wrapper for Redis, allowing seamless integration of Redis streams with PHP's native file handling functions (`fopen`, `fwrite`, `fread`, etc.).

## Features

- Read and write to Redis streams using PHP's built-in stream functions.
- Support for `fopen`, `fread`, `fwrite`, `fclose`, and other standard PHP file operations.
- Handles Redis streams (`XADD`, `XREAD`, etc.) in a transparent way.
- Supports blocking and non-blocking reads.

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

### Todo

Complete readme...