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
            # 'auth' => ['phpredis', 'phpredis'],
            'backoff' => [
                'algorithm' => Redis::BACKOFF_ALGORITHM_DECORRELATED_JITTER,
                'base' => 500,
                'cap' => 750,
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
                        // 'auth' => ['phpredis', 'phpredis'],
                        'backoff' => [
                            'algorithm' => Redis::BACKOFF_ALGORITHM_DECORRELATED_JITTER,
                            'base' => 500,
                            'cap' => 750,
                        ],
                    ]);

                    return $client;
                },
                'configuration' => [
                    // prefix to add to stream key
                    // For example, with prefix set to "mystreams:"
                    // The path redis://my/important/file.log would be
                    // translated into redis key "mystreams:my/important/file.log"
                    'key_prefix' => 'streams:'
                ],
                'events' => [
                    'before_stream_close' => function (Redis $redis, string $redisKeyToStream) {
                        // You can do whatever you want before closing the stream. You
                        // can set an expiration time on the key or delete the stream

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
