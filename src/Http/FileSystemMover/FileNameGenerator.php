<?php

namespace SimplyCodedSoftware\Http\FileSystemMover;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Interface FileNameGenerator
 * @package SimplyCodedSoftware\Http\FileSystemMover
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface FileNameGenerator
{
    /**
     * @param UploadedFileInterface $uploadedFile
     * @return string
     */
    public function generateFor(UploadedFileInterface $uploadedFile) : string;
}