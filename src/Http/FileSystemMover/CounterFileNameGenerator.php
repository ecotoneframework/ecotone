<?php

namespace SimplyCodedSoftware\Http\FileSystemMover;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Class CustomFileNameGenerator
 * @package SimplyCodedSoftware\Http\FileSystemMover
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CounterFileNameGenerator implements FileNameGenerator
{

    /**
     * @var int
     */
    private $counter = 0;

    /**
     * @inheritDoc
     */
    public function generateFor(UploadedFileInterface $uploadedFile): string
    {
        $this->counter++;

        return $this->counter;
    }
}