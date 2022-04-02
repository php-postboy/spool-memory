<?php

declare(strict_types=1);

namespace Postboy\SpoolMemory;

use Postboy\Contract\Message\MessageInterface;
use Postboy\Contract\Spool\SpoolInterface;

class SpoolMemory implements SpoolInterface
{
    /**
     * @var MessageInterface[][]
     */
    private array $spool = [];

    /**
     * @inheritDoc
     */
    public function push(MessageInterface $message, int $queue): void
    {
        $this->spool[$queue][] = $message;
    }

    /**
     * @inheritDoc
     */
    public function pull(int $queue): ?MessageInterface
    {
        if (!array_key_exists($queue, $this->spool) || empty($this->spool[$queue])) {
            return null;
        }
        return array_shift($this->spool[$queue]);
    }
}
