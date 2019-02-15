<?php

namespace SimplyCodedSoftware\Http;

use Psr\Http\Message\UploadedFileInterface;

/**
 * Interface FileMover
 * @package SimplyCodedSoftware\Http
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface UploadedFileMover
{
    /**
     * @param UploadedFileInterface $uploadedFile
     * @return string resource path to access file
     */
    public function move(UploadedFileInterface $uploadedFile) : string;
}