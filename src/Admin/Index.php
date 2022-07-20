<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\SimpleGallery\Admin;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\SimpleGallery\Config;
use EnjoysCMS\Module\SimpleGallery\Entities\Image;

final class Index implements ModelInterface
{
    private ObjectRepository|EntityRepository $repository;

    public function __construct(private EntityManager $em, private Config $config)
    {
        $this->repository = $this->em->getRepository(Image::class);
    }

    public function getContext(): array
    {
        return [
            'config' => $this->config,
            'images' => $this->repository->findBy([], ['id' => 'desc'])
        ];
    }
}
