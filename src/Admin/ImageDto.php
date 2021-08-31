<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\SimpleGallery\Admin;


final class ImageDto
{
    public function __construct(public string $filename, public string $hash, public ?string $description = null)
    {
    }
}