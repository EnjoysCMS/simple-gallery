<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\SimpleGallery\Admin;


use Intervention\Image\Constraint;
use Intervention\Image\ImageManagerStatic;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;

final class Thumbnail implements ThumbnailServiceInterface
{
    /**
     * @throws FilesystemException
     */
    public static function create(string $thumbFilename, string $content, FilesystemOperator $filesystem): void
    {
        $image = ImageManagerStatic::make($content);
        $image->resize(
            300,
            300,
            function (Constraint $constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            }
        );

        $filesystem->write($thumbFilename, $image->encode()->getEncoded());
    }

}
