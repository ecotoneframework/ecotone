<?php

namespace Ecotone\Http\KeepAsTemporaryMover;

use Psr\Http\Message\UploadedFileInterface;
use Ecotone\Http\UploadedFileMover;

/**
 * Class KeepAsTemporaryFileMover
 * @package Ecotone\IntegrationMessaging\Http\KeepAsTemporaryMover
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class KeepAsTemporaryFileMover implements UploadedFileMover
{
    const WRAPPER_TYPE_META_DATA_KEY = 'wrapper_type';
    const URI_META_DATA_KEY = 'uri';

    /**
     * @inheritDoc
     */
    public function move(UploadedFileInterface $uploadedFile): string
    {
        $metadata = $uploadedFile->getStream()->getMetadata();

        if (!isset($metadata[self::WRAPPER_TYPE_META_DATA_KEY])) {
            throw new \InvalidArgumentException("Unknown wrapper type for uploaded file");
        }
        if (!isset($metadata[self::URI_META_DATA_KEY])) {
            throw new \InvalidArgumentException("Unknown uri for uploaded file");
        }

        return "file:/{$metadata[self::URI_META_DATA_KEY]}";
    }
}