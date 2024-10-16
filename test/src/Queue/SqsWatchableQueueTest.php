<?php

namespace test\eLife\Bus\Queue;

use Aws\Command;
use Aws\MockHandler;
use Aws\Result;
use Aws\Sqs\SqsClient;
use eLife\Bus\Queue\BusSqsMessage;
use eLife\Bus\Queue\SqsWatchableQueue;
use PHPUnit\Framework\TestCase;
use Traversable;

final class SqsWatchableQueueTest extends TestCase
{
    /** @var SqsClient */
    private $sqsClient;
    /** @var MockHandler */
    private $mockHandler;

    /**
     * @before
     */
    public function setUpSqsClient()
    {
        $this->sqsClient = new SqsClient(['region' => 'us-east-1', 'version' => '2012-11-05']);
        $this->mockHandler = new MockHandler();

        $this->sqsClient->getHandlerList()->setHandler($this->mockHandler);

        $this->mockHandler->append(new Result(['QueueUrl' => 'http://www.example.com/']));
    }

    /**
     * @test
     */
    public function it_adds_an_item_to_the_queue()
    {
        $queue = new SqsWatchableQueue($this->sqsClient, 'queue');

        $result = [];
        $this->mockHandler->append(function (Command $command) use (&$result) {
            $result = $command;

            return new Result();
        });

        $queue->enqueue(new BusSqsMessage('messageId', 'id', 'type', 'receipt', 0));

        $expected = [
            'QueueUrl' => 'http://www.example.com/',
            'MessageBody' => json_encode(['type' => 'type', 'id' => 'id']),
        ];

        $this->assertSame('SendMessage', $result->getName());
        $this->assertArraySubset($expected, $result->toArray());
    }

    public static function assertArraySubset($expected, $actual, $strict = false, $message = '') {
        foreach ($expected as $key => $value) {
            self::assertArrayHasKey($key, $actual);
            self::assertSame($value, $actual[$key]);
        }
    }

    /**
     * @test
     */
    public function it_takes_an_item_from_the_queue()
    {
        $queue = new SqsWatchableQueue($this->sqsClient, 'queue');

        $result = [];
        $this->mockHandler->append(function (Command $command) use (&$result) {
            $result = $command;

            $expected = [
                'AttributeNames' => ['ApproximateReceiveCount'],
                'QueueUrl' => 'http://www.example.com/',
                'WaitTimeSeconds' => 20,
                'VisibilityTimeout' => 10,
            ];

            $this->assertArraySubset($expected, $command->toArray());

            return new Result([
                'Messages' => [
                    [
                        'MessageId' => 'id',
                        'Body' => $body = json_encode(['type' => 'article', 'id' => 'foo']),
                        'MD5OfBody' => md5($body),
                        'ReceiptHandle' => 'ReceiptHandle',
                    ],
                ],
            ]);
        });

        $item = $queue->dequeue();

        $expected = [
            'AttributeNames' => ['ApproximateReceiveCount'],
            'QueueUrl' => 'http://www.example.com/',
            'WaitTimeSeconds' => 20,
            'VisibilityTimeout' => 10,
        ];

        $this->assertSame('ReceiveMessage', $result->getName());
        $this->assertArraySubset($expected, $result->toArray());
        $this->assertInstanceOf(BusSqsMessage::class, $item);
    }

    /**
     * @test
     */
    public function it_commits_an_item()
    {
        $queue = new SqsWatchableQueue($this->sqsClient, 'queue');

        $result = [];
        $this->mockHandler->append(function (Command $command) use (&$result) {
            $result = $command;

            $expected = [
                'ReceiptHandle' => 'receipt',
                'QueueUrl' => 'http://www.example.com/',
            ];

            $this->assertArraySubset($expected, $command->toArray());

            return new Result([
                'Messages' => [
                    [
                        'MessageId' => 'id',
                        'Body' => $body = json_encode(['type' => 'article', 'id' => 'foo']),
                        'MD5OfBody' => md5($body),
                        'ReceiptHandle' => 'ReceiptHandle',
                    ],
                ],
            ]);
        });

        $queue->commit(new BusSqsMessage('messageId', 'id', 'type', 'receipt'));

        $expected = [
            'QueueUrl' => 'http://www.example.com/',
            'ReceiptHandle' => 'receipt',
        ];

        $this->assertSame('DeleteMessage', $result->getName());
        $this->assertArraySubset($expected, $result->toArray());
    }

    /**
     * @test
     * @dataProvider releaseProvider
     */
    public function it_releases_an_item(int $attempts = null, int $expectedVisibilityTimeout)
    {
        $queue = new SqsWatchableQueue($this->sqsClient, 'queue');

        $result = [];
        $this->mockHandler->append(function (Command $command) use (&$result, $expectedVisibilityTimeout) {
            $result = $command;

            $expected = [
                'ReceiptHandle' => 'receipt',
                'QueueUrl' => 'http://www.example.com/',
                'VisibilityTimeout' => $expectedVisibilityTimeout,
            ];

            $this->assertArraySubset($expected, $command->toArray());

            return new Result([]);
        });

        $queue->release(new BusSqsMessage('messageId', 'id', 'type', 'receipt', $attempts ?? 0));

        $expected = [
            'QueueUrl' => 'http://www.example.com/',
            'ReceiptHandle' => 'receipt',
            'VisibilityTimeout' => $expectedVisibilityTimeout,
        ];

        $this->assertSame('ChangeMessageVisibility', $result->getName());
        $this->assertArraySubset($expected, $result->toArray());
    }

    public function releaseProvider() : Traversable
    {
        yield 'unknown' => [null, 10];
        yield 'first attempt' => [1, 20];
        yield 'second attempt' => [2, 30];
        yield 'third attempt' => [3, 40];
    }

    /**
     * @test
     */
    public function it_cleans_the_queue()
    {
        $queue = new SqsWatchableQueue($this->sqsClient, 'queue');

        $result = [];
        $this->mockHandler->append(function (Command $command) use (&$result) {
            $result = $command;

            $expected = [
                'QueueUrl' => 'http://www.example.com/',
            ];

            $this->assertArraySubset($expected, $command->toArray());

            return new Result([]);
        });

        $queue->clean();

        $expected = [
            'QueueUrl' => 'http://www.example.com/',
        ];

        $this->assertSame('PurgeQueue', $result->getName());
        $this->assertArraySubset($expected, $result->toArray());
    }

    /**
     * @test
     */
    public function it_counts_the_queue()
    {
        $queue = new SqsWatchableQueue($this->sqsClient, 'queue');

        $result = [];
        $this->mockHandler->append(function (Command $command) use (&$result) {
            $result = $command;

            $expected = [
                'AttributeNames' => [
                    'ApproximateNumberOfMessages',
                    'ApproximateNumberOfMessagesDelayed',
                    'ApproximateNumberOfMessagesNotVisible',
                ],
                'QueueUrl' => 'http://www.example.com/',
            ];

            $this->assertArraySubset($expected, $command->toArray());

            return new Result([
                'Attributes' => [
                    'ApproximateNumberOfMessages' => 1,
                    'ApproximateNumberOfMessagesDelayed' => 3,
                    'ApproximateNumberOfMessagesNotVisible' => 5,
                ],
            ]);
        });

        $count = $queue->count();

        $this->assertSame('GetQueueAttributes', $result->getName());
        $this->assertSame(9, $count);
    }

    /**
     * @test
     */
    public function it_has_a_name()
    {
        $queue = new SqsWatchableQueue($this->sqsClient, 'queue');

        $this->assertSame('queue', $queue->getName());
    }
}
