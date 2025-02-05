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

        $handle = fopen("redis://mytest.txt", "w");

        fwrite($handle, "test-data");

        fclose($handle);

        $this->assertEquals(1, $this->redis->exists($key));
    }

    public function testReadAndWriteToStream()
    {
        $key = 'streams:mytest.txt';
        $this->redis->del($key);
        $this->assertEquals(0, $this->redis->exists($key));

        $handle = fopen("redis://mytest.txt", "w");

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

        $handle = fopen("redis://mytest.txt", "w", context: stream_context_create([
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
                'configuration' => [
                    'key_prefix' => 'streams:'
                ],
                'events' => [
                    'before_stream_close' => function (Redis $redis, string $redisKeyToStream) {
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
}
