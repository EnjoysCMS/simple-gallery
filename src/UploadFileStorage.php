<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\SimpleGallery;

use Psr\Http\Message\UploadedFileInterface;

interface UploadFileStorage
{
    public function upload(UploadedFileInterface $file, string $newFilename = null): void;

    public function getFilename(): ?string;

    public function getTargetPath(): string;
}