<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\SimpleGallery\Admin;


use Doctrine\ORM\EntityManager;
use EnjoysCMS\Module\SimpleGallery\Entities\Image;
use HttpSoft\ServerRequest\PhpInputStream;

final class UpdateDescription
{

    private mixed $data;

    private Image $image;

    public function __construct(
        private EntityManager $em
    ) {
        $stream = new PhpInputStream();
        $this->data = \json_decode($stream->getContents());
        $this->image = $this->em->getRepository(Image::class)->find($this->data->id);

        if ($this->image === null) {
            throw new \InvalidArgumentException('Не правильный id изображения');
        }
    }


    public function update()
    {
        $this->image->setDescription($this->data->comment);
        $this->em->flush();
    }

}