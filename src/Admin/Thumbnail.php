<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\SimpleGallery\Admin;


use Intervention\Image\ImageManagerStatic;
use League\Flysystem\FilesystemOperator;

final class Thumbnail implements ThumbnailServiceInterface
{
    public static function create(string $thumbFilename, string $content, FilesystemOperator $filesystem)
    {
        $image = ImageManagerStatic::make($content);
        $image->resize(
            300,
            300,
            function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            }
        );

        $filesystem->write($thumbFilename, $image->encode()->getEncoded());
    }

}
