<?php


use PHPUnit\Framework\TestCase;

class RedisStreamWrapperTest extends TestCase
{
    private Redis $redis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->redis = new Redis([
            'host' => 'redis',
            'port' => 6379,
            'connectTimeout' => 2.5,
            'backoff' => [
                'algorithm' => Redis::BACKOFF_ALGORITHM_DECORRELATED_JITTER,
                'base' => 500,
                'cap' => 750,
            ],
        ]);

        stream_context_set_default([
            'redis' => [
                'client' => function (): Redis {
                    return $this->redis;
                },
                'configurations' => [
                    'key_prefix' => 'streams:',
                ],
            ],
        ]);

    }

    public function testWriteToStream()
    {
        $key = 'streams:mytest.txt';
        $this->redis->del($key);
        $this->assertEquals(0, $this->redis->exists($key));

        $handle = fopen("redis://mytest.txt", "a");

        fwrite($handle, "test-data");

        fclose($handle);

        $this->assertEquals(1, $this->redis->exists($key));
    }

    public function testReadAndWriteToStream()
    {
        $key = 'streams:mytest.txt';
        $this->redis->del($key);
        $this->assertEquals(0, $this->redis->exists($key));

        $handle = fopen("redis://mytest.txt", "a");

        fwrite($handle, "test-data");

        fclose($handle);

        $handle = fopen("redis://mytest.txt", "r");

        $data = stream_get_contents($handle);

        $this->assertEquals("test-data", $data);
    }

    public function testOnCloseEvent()
    {
        $key = 'streams:mytest.txt';
        $this->redis->del($key);
        $this->assertEquals(0, $this->redis->exists($key));

        $handle = fopen("redis://mytest.txt", "a", context: stream_context_create([
            'redis' => [
                'client' => function (): Redis {
                    static $client = new Redis([
                        'host' => 'redis',
                        'port' => 6379,
                        'connectTimeout' => 2.5,
                        'backoff' => [
                            'algorithm' => Redis::BACKOFF_ALGORITHM_DECORRELATED_JITTER,
                            'base' => 500,
                            'cap' => 750,
                        ],
                    ]);

                    return $client;
                },
                'configurations' => [
                    'key_prefix' => 'streams:'
                ],
                'events' => [
                    'before_stream_close' => function (string $redisKeyToStream, Redis $redis) {
                        $this->redis->del($redisKeyToStream);
                    }
                ]
            ],
        ]));

        fwrite($handle, "test-data");

        fclose($handle);

        // Delete on close, so the key will not exist
        $this->assertEquals(0, $this->redis->exists($key));
    }

    public function testAfterRead()
    {
        $key = 'streams:mytest.txt';
        $this->redis->del($key);
        $this->assertEquals(0, $this->redis->exists($key));

        $streamCurrentPosition = "0";

        $handle = fopen("redis://mytest.txt", "a", context: stream_context_create([
            'redis' => [
                'client' => function (): Redis {
                    static $client = new Redis([
                        'host' => 'redis',
                        'port' => 6379,
                        'connectTimeout' => 2.5,
                        'backoff' => [
                            'algorithm' => Redis::BACKOFF_ALGORITHM_DECORRELATED_JITTER,
                            'base' => 500,
                            'cap' => 750,
                        ],
                    ]);

                    return $client;
                },
                'configurations' => [
                    'key_prefix' => 'streams:'
                ]
            ],
        ]));

        fwrite($handle, "test-data");

        fclose($handle);

        $handle = fopen("redis://mytest.txt", "r", context: stream_context_create([
            'redis' => [
                'client' => function (): Redis {
                    static $client = new Redis([
                        'host' => 'redis',
                        'port' => 6379,
                        'connectTimeout' => 2.5,
                        'backoff' => [
                            'algorithm' => Redis::BACKOFF_ALGORITHM_DECORRELATED_JITTER,
                            'base' => 500,
                            'cap' => 750,
                        ],
                    ]);

                    return $client;
                },
                'configurations' => [
                    'key_prefix' => 'streams:'
                ],
                'events' => [
                    'after_read' => function (string $lastMessageId, string $redisKeyToStream, Redis $redis) use (&$streamCurrentPosition) {
                        $streamCurrentPosition = $lastMessageId;
                    }
                ]
            ],
        ]));

        $data = stream_get_contents($handle);

        $this->assertEquals("test-data", $data);

        $this->assertNotEquals("0", $streamCurrentPosition);
    }

    public function testStartId()
    {
        $key = 'streams:mytest.txt';
        $this->redis->del($key);
        $this->assertEquals(0, $this->redis->exists($key));

        $streamCurrentPosition = "0";

        // Write some data
        $handle = fopen("redis://mytest.txt", "a", context: stream_context_create([
            'redis' => [
                'client' => function (): Redis {
                    static $client = new Redis([
                        'host' => 'redis',
                        'port' => 6379,
                        'connectTimeout' => 2.5,
                        'backoff' => [
                            'algorithm' => Redis::BACKOFF_ALGORITHM_DECORRELATED_JITTER,
                            'base' => 500,
                            'cap' => 750,
                        ],
                    ]);

                    return $client;
                },
                'configurations' => [
                    'key_prefix' => 'streams:'
                ]
            ],
        ]));

        fwrite($handle, "test-data");
        fclose($handle);

        // Read the data and get the last message id
        $handle = fopen("redis://mytest.txt", "r", context: stream_context_create([
            'redis' => [
                'client' => function (): Redis {
                    static $client = new Redis([
                        'host' => 'redis',
                        'port' => 6379,
                        'connectTimeout' => 2.5,
                        'backoff' => [
                            'algorithm' => Redis::BACKOFF_ALGORITHM_DECORRELATED_JITTER,
                            'base' => 500,
                            'cap' => 750,
                        ],
                    ]);

                    return $client;
                },
                'configurations' => [
                    'key_prefix' => 'streams:'
                ],
                'events' => [
                    'after_read' => function (string $lastMessageId, string $redisKeyToStream, Redis $redis) use (&$streamCurrentPosition) {
                        $streamCurrentPosition = $lastMessageId;
                    }
                ]
            ],
        ]));

        $data = stream_get_contents($handle);
        fclose($handle);

        // Write some more data
        $handle = fopen("redis://mytest.txt", "a", context: stream_context_create([
            'redis' => [
                'client' => function (): Redis {
                    static $client = new Redis([
                        'host' => 'redis',
                        'port' => 6379,
                        'connectTimeout' => 2.5,
                        'backoff' => [
                            'algorithm' => Redis::BACKOFF_ALGORITHM_DECORRELATED_JITTER,
                            'base' => 500,
                            'cap' => 750,
                        ],
                    ]);

                    return $client;
                },
                'configurations' => [
                    'key_prefix' => 'streams:'
                ]
            ],
        ]));
        fwrite($handle, "some-more-data");
        fclose($handle);

        // Read the data and get the last message id
        $handle = fopen("redis://mytest.txt", "r", context: stream_context_create([
            'redis' => [
                'client' => function (): Redis {
                    static $client = new Redis([
                        'host' => 'redis',
                        'port' => 6379,
                        'connectTimeout' => 2.5,
                        'backoff' => [
                            'algorithm' => Redis::BACKOFF_ALGORITHM_DECORRELATED_JITTER,
                            'base' => 500,
                            'cap' => 750,
                        ],
                    ]);

                    return $client;
                },
                'configurations' => [
                    'key_prefix' => 'streams:',
                    'start_id' => $streamCurrentPosition
                ]
            ],
        ]));

        $data = stream_get_contents($handle);
        fclose($handle);

        $this->assertEquals("some-more-data", $data);

        $this->assertNotEquals("0", $streamCurrentPosition);
    }
}
