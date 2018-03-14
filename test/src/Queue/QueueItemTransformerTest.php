<?php

namespace test\eLife\Bus\Queue;

use eLife\Bus\Queue\SqsMessageTransformer;
use LogicException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @covers \eLife\Bus\Queue\QueueItemTransformer
 */
final class QueueItemTransformerTest extends TestCase
{
    /** @var SqsMessageTransformer */
    private $transformer;

    /**
     * @before
     */
    public function setup_queue_transformer()
    {
        $ref = new ReflectionClass(SqsMessageTransformer::class);
        $this->transformer = $ref->newInstanceWithoutConstructor();
    }

    /**
     * @test
     */
    public function it_can_instantiate_with_mock()
    {
        $this->assertInstanceOf(SqsMessageTransformer::class, $this->transformer);
    }

    /**
     * @test
     */
    public function it_can_transform_sqs_message()
    {
        $message = SqsMessageTransformer::fromMessage([
            'Messages' => [
                [
                    'MessageId' => 'id-1234',
                    'Body' => $body = '{"id": "1234", "type": "blog-article"}',
                    'MD5OfBody' => md5($body),
                    'ReceiptHandle' => 'very-long-string-thing',
                ],
            ],
        ]);

        $this->assertEquals($message->getId(), '1234');
        $this->assertEquals($message->getType(), 'blog-article');
        $this->assertEquals($message->getReceipt(), 'very-long-string-thing');
    }

    /**
     * @test
     */
    public function it_can_transform_sqs_message_failure()
    {
        $this->expectException(LogicException::class);
        SqsMessageTransformer::fromMessage([
            'Messages' => [
                [
                    'MessageId' => 'id-1234',
                    'Body' => $body = '{"id": "1234", "type": "blog-article"}',
                    'MD5OfBody' => md5($body.'-md5-mismatch'),
                    'ReceiptHandle' => 'very-long-string-thing',
                ],
            ],
        ]);
    }
}
