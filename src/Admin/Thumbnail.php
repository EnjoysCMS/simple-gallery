<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\SimpleGallery\Admin;


use Intervention\Image\ImageManagerStatic;

final class Thumbnail
{
    public static function create(string $originalPath)
    {
        $file = pathinfo($originalPath);
        $imgSmall = ImageManagerStatic::make($originalPath);
        $imgSmall->resize(
            300,
            300,
            function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            }
        );


        $imgSmall->save($file['dirname'] . '/' . $file['filename'] . '_thumb' . '.' . $file['extension']);
    }
}