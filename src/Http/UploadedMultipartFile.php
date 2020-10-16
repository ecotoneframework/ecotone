<?php
declare(strict_types=1);

namespace Ecotone\Http;

/**
 * Class UploadedMultipartFile
 * @package Ecotone\Http
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class UploadedMultipartFile
{
    private ?string $originalFileName;
    private ?string $mediaType;
    private int $bytes;
    private string $fileAccessPath;

    /**
     * UploadedMultipartFile constructor.
     * @param string $fileAccessPath
     * @param string $originalFileName
     * @param string $mediaType
     * @param int $bytes
     */
    private function __construct(string $fileAccessPath, string $originalFileName, string $mediaType, int $bytes)
    {
        $this->originalFileName = $originalFileName;
        $this->mediaType = $mediaType;
        $this->bytes = $bytes;
        $this->fileAccessPath = $fileAccessPath;
    }

    /**
     * @param string $filePath
     * @param string $originalFileName
     * @param string $mediaType
     * @param int $bytes
     * @return UploadedMultipartFile
     */
    public static function createWith(string $filePath, string $originalFileName, string $mediaType, int $bytes) : self
    {
        return new self($filePath, $originalFileName, $mediaType, $bytes);
    }

    /**
     * @return string
     */
    public function getFileAccessPath(): string
    {
        return $this->fileAccessPath;
    }

    /**
     * @return int
     */
    public function getSizeInBytes() : int
    {
        return $this->bytes;
    }

    /**
     * @return null|string
     */
    public function getOriginalFilename() : ?string
    {
        return $this->originalFileName;
    }

    /**
     * @return null|string
     */
    public function getMediaType() : ?string
    {
        return $this->mediaType;
    }

    /**
     * @return bool
     */
    public function isEmpty() : bool
    {
        return $this->getSizeInBytes() === 0;
    }
}