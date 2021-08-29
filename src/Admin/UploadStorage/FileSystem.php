<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\SimpleGallery\Admin\UploadStorage;

use EnjoysCMS\Module\SimpleGallery\UploadFileStorage;
use Psr\Http\Message\UploadedFileInterface;

use function Enjoys\FileSystem\createDirectory;

final class FileSystem implements UploadFileStorage
{
    private string $directory;
    private string $targetPath;
    private ?string $filename = null;

    /**
     * @throws \Exception
     */
    public function __construct(string $directory)
    {
        $this->directory = rtrim($directory, '/') . '/';
        createDirectory($this->directory);
    }


    public function upload(UploadedFileInterface $file, string $newFilename = null): void
    {
        $this->filename = $file->getClientFilename();
        if($newFilename !== null) {
            $this->filename = strpos($newFilename, '.') ? $newFilename : $newFilename . '.' . pathinfo(
                    $file->getClientFilename(),
                    PATHINFO_EXTENSION
                );
        }

        $this->targetPath = $this->directory . $this->filename;
        $file->moveTo($this->targetPath);
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function getTargetPath(): string
    {
        return $this->targetPath;
    }
}