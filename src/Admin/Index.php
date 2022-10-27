<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\SimpleGallery\Admin;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ObjectRepository;
use EnjoysCMS\Core\Components\Pagination\Pagination;
use EnjoysCMS\Core\Exception\NotFoundException;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\SimpleGallery\Config;
use EnjoysCMS\Module\SimpleGallery\Entities\Image;
use EnjoysCMS\Module\SimpleGallery\Entities\ImageRepository;
use Psr\Http\Message\ServerRequestInterface;

final class Index implements ModelInterface
{
    private ImageRepository|ObjectRepository|EntityRepository $repository;

    public function __construct(
        private EntityManager $em,
        private Config $config,
        private ServerRequestInterface $request
    ) {
        $this->repository = $this->em->getRepository(Image::class);
    }

    /**
     * @throws NotFoundException
     * @throws \Exception
     */
    public function getContext(): array
    {
        $pagination = new Pagination(
            $this->request->getAttribute('page', 1),
            $this->config->getModuleConfig()->get('perPageLimit', 12)
        );
        $paginator = new Paginator(
            $this->repository->getOffsetItemsQueryBuilder(
                $pagination,
                $this->config->getModuleConfig()->get('order')[0] ?? 'id',
                $this->config->getModuleConfig()->get('order')[1] ?? 'asc'
            )
        );
        $pagination->setTotalItems($paginator->count());
        return [
            'config' => $this->config,
            'images' => $paginator->getIterator(),
            'pagination' => $pagination,
        ];
    }
}
