<?php

namespace SimplyCodedSoftware\Http\FileSystemMover;
use Psr\Http\Message\UploadedFileInterface;
use SimplyCodedSoftware\Http\UploadedFileMover;

/**
 * Class FileSystemFileMover
 * @package SimplyCodedSoftware\Http
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class FileSystemFileMover implements UploadedFileMover
{
    /**
     * @var string
     */
    private $rootDir;
    /**
     * @var FileNameGenerator
     */
    private $fileNameGenerator;

    /**
     * FileSystemFileMover constructor.
     * @param string $rootDir
     * @param FileNameGenerator $fileNameGenerator
     */
    public function __construct(string $rootDir, FileNameGenerator $fileNameGenerator)
    {
        $this->rootDir = $rootDir;
        $this->fileNameGenerator = $fileNameGenerator;
    }

    /**
     * @return FileSystemFileMover
     */
    public static function createInTemporaryCatalog() : self
    {
        return new self(sys_get_temp_dir(), new UuidFileNameGenerator());
    }

    /**
     * @param string $rootDir
     * @param FileNameGenerator $fileNameGenerator
     * @return FileSystemFileMover
     */
    public static function createFor(string $rootDir, FileNameGenerator $fileNameGenerator) : self
    {
        return new self($rootDir, $fileNameGenerator);
    }

    /**
     * @inheritDoc
     */
    public function move(UploadedFileInterface $uploadedFile): string
    {
        $targetFilePath = $this->rootDir . DIRECTORY_SEPARATOR . $this->fileNameGenerator->generateFor($uploadedFile);

        $uploadedFile->moveTo($targetFilePath);

        return $targetFilePath;
    }
}