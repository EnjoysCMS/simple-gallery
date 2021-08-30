<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\SimpleGallery\Admin;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use EnjoysCMS\Module\SimpleGallery\Entities\Image;

final class WriteImage
{

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws \Exception
     */
    public function __construct(private EntityManager $entityManager, private ImageDto $imageDto)
    {
        if (null !== $this->entityManager->getRepository(Image::class)->findOneBy(['hash' => $this->imageDto->hash])) {
            throw new \Exception('Такое изображение уже есть');
        }

        $image = new Image();
        $image->setFilename($this->imageDto->filename);
        $image->setHash($this->imageDto->hash);
        $image->setDescription($this->imageDto->description);


        $this->entityManager->persist($image);
        $this->entityManager->flush();
    }
}