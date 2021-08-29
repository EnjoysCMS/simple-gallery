<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\SimpleGallery\Admin;


use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use EnjoysCMS\Module\SimpleGallery\Entities\Image;

final class Index implements ModelInterface
{
    private ObjectRepository|EntityRepository $repository;

    public function __construct(private EntityManager $em)
{
    $this->repository = $this->em->getRepository(Image::class);
}

    public function getContext(): array
    {
        return [
            'images' => $this->repository->findAll()
        ];
    }
}