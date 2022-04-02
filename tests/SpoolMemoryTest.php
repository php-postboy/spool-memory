<?php

declare(strict_types=1);

namespace Tests\Postboy\SpoolMemory;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Postboy\Contract\Message\Body\AttachmentInterface;
use Postboy\Contract\Message\Body\BodyInterface;
use Postboy\Contract\Message\Body\BodyPartInterface;
use Postboy\Contract\Message\Body\Collection\BodyCollectionInterface;
use Postboy\Contract\Message\Body\ContentInterface;
use Postboy\Contract\Message\Body\MultipartBodyInterface;
use Postboy\Contract\Message\Body\Stream\StreamInterface;
use Postboy\Contract\Message\MessageInterface;
use Postboy\SpoolMemory\SpoolMemory;
use Postboy\Message\Body\BodyPart;
use Postboy\Message\Body\Stream\StringStream;
use Postboy\Message\Message;

class SpoolMemoryTest extends TestCase
{
    public function testPushAndPool()
    {
        $spool = new SpoolMemory();

        $messages = [];
        for ($queue = 1; $queue <= 5; $queue++) {
            for ($number = 1; $number <= 20; $number++) {
                $text = sprintf('body-%d.%d', $queue, $number);
                $subject = sprintf('subject-%d.%d', $queue, $number);
                $messages[$queue][$number] = $this->createMessage($text, $subject);
            }
        }

        foreach ($messages as $queue => $list) {
            foreach ($list as $message) {
                $spool->push(
                    $message,
                    $queue
                );
            }
        }

        foreach ($messages as $queue => $list) {
            foreach ($list as $message) {
                $this->assertMessage($message, $spool->pull($queue));
            }
            Assert::assertNull($spool->pull($queue));
        }
    }

    private function createMessage(string $text, string $subject): MessageInterface
    {
        $body = new BodyPart(new StringStream($text), 'text/plain');
        return new Message($body, $subject);
    }

    private function assertMessage(MessageInterface $expected, MessageInterface $actual): void
    {
        Assert::assertSame($expected->getHeader('subject'), $actual->getHeader('subject'));
        $this->assertBody($expected->getBody(), $actual->getBody());
    }

    private function assertBody(BodyInterface $expected, BodyInterface $actual): void
    {
        Assert::assertSame($expected->getContentType(), $actual->getContentType());
        if ($expected instanceof AttachmentInterface) {
            Assert::assertInstanceOf(AttachmentInterface::class, $actual);
            Assert::assertSame((string)$expected->getFilename(), (string)$actual->getFilename());
        }
        if ($expected instanceof ContentInterface) {
            Assert::assertInstanceOf(ContentInterface::class, $actual);
            Assert::assertSame((string)$expected->getContentId(), (string)$actual->getContentId());
        }
        if ($expected instanceof BodyPartInterface) {
            Assert::assertInstanceOf(BodyPartInterface::class, $actual);
            $this->assertStream($expected->getStream(), $actual->getStream());
        }

        if ($expected instanceof MultipartBodyInterface) {
            Assert::assertInstanceOf(MultipartBodyInterface::class, $actual);
            Assert::assertSame($expected->getBoundary(), $actual->getBoundary());
            $this->assertBodyCollection($expected->getParts(), $actual->getParts());
        }
    }

    private function assertBodyCollection(BodyCollectionInterface $expected, BodyCollectionInterface $actual): void
    {
        Assert::assertSame($expected->count(), $actual->count());
        foreach ($expected as $expectedPart) {
            $actualPart = $actual->current();
            $actual->next();
            $this->assertBody($expectedPart, $actualPart);
        }
    }

    private function assertStream(StreamInterface $expected, StreamInterface $actual): void
    {
        Assert::assertSame($this->readStream($expected), $this->readStream($actual));
    }

    private function readStream(StreamInterface $stream): string
    {
        $result = '';
        $stream->rewind();
        while (!$stream->eof()) {
            $result .= $stream->read(4096);
        }
        return $result;
    }
}
