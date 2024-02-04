<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\SimpleGallery\Admin;


use League\Flysystem\FilesystemOperator;

interface ThumbnailServiceInterface
{
    public static function create(string $thumbFilename, string $content, FilesystemOperator $filesystem): void;
}
