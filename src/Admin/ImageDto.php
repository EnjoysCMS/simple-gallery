<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\SimpleGallery\Admin;


final class ImageDto
{
    public string $storage;

    public function __construct(
        public string $filename,
        public string $hash,
        public ?string $title = null,
        public ?string $description = null,
    ) {
    }
}
