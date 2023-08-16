<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\SimpleGallery\Controller;

use DI\Container;
use DI\DependencyException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ObjectRepository;
use EnjoysCMS\Core\AbstractController;
use EnjoysCMS\Core\Exception\NotFoundException;
use EnjoysCMS\Core\Pagination\Pagination;
use EnjoysCMS\Core\Routing\Annotation\Route;
use EnjoysCMS\Module\SimpleGallery\Config;
use EnjoysCMS\Module\SimpleGallery\Entities\Image;
use EnjoysCMS\Module\SimpleGallery\Entities\ImageRepository;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[Route('gallery', 'gallery_')]
final class ViewGallery extends AbstractController
{

    private ImageRepository|ObjectRepository|EntityRepository $repository;

    /**
     * @throws NotSupported
     * @throws DependencyException
     * @throws \DI\NotFoundException
     */
    public function __construct(
        Container $container,
        private readonly EntityManager $em,
        private readonly Config $config,
    ) {
        parent::__construct($container);
        $this->repository = $this->em->getRepository(Image::class);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     * @throws NotFoundException
     * @throws Exception
     */
    #[Route(
        path: '@{page}',
        name: 'show',
        requirements: [
            'page' => '\d+'
        ],
        defaults: [
            'page' => 1,
        ],
        comment: 'Просмотр изображений'
    )]
    public function viewGallery(Environment $twig): ResponseInterface
    {
        $pagination = new Pagination(
            $this->request->getAttribute('page', 1),
            $this->config->get('perPageLimit', false)
        );

        $paginator = new Paginator(
            $this->repository->getOffsetItemsQueryBuilder(
                $pagination,
                $this->config->get('order->0', 'id'),
                $this->config->get('order->1', 'asc')
            )
        );
        $pagination->setTotalItems($paginator->count());

        $template_path = '@m/simple-gallery/view_gallery.twig';

        if (!$twig->getLoader()->exists($template_path)) {
            $template_path = __DIR__ . '/../../template/view_gallery.twig';
        }

        return $this->response(
            $twig->render($template_path, [
                '_title' => sprintf(
                    'Фотогалерея [стр. %2$s] - %1$s',
                    $this->setting->get('sitename'),
                    $pagination->getCurrentPage()
                ),
                'images' => $paginator->getIterator(),
                'pagination' => $pagination,
                'config' => $this->config
            ])
        );
    }

    /**
     * @throws NotFoundException
     * @throws Exception
     */
    #[Route(
        path: '.json',
        name: 'json',
        comment: 'Просмотр изображений JSON'
    )]
    public function viewGalleryJson(): ResponseInterface
    {
        $page = (int)($this->request->getParsedBody()['page']
            ?? json_decode($this->request->getBody()->getContents(), true)['page']
            ?? $this->request->getQueryParams()['page']
            ?? 1);

        $pagination = new Pagination(
            $page,
            $this->config->get('perPageLimit', false)
        );

        $paginator = new Paginator($this->repository->getOffsetItemsQueryBuilder($pagination));
        $pagination->setTotalItems($paginator->count());

        return $this->json([
            'images' => array_map(function ($image) {
                /** @var Image $image */
                return [
                    'id' => $image->getId(),
                    'src' => $this->config->getStorageUpload($image->getStorage())->getUrl($image->getFilename()),
                    'thumb' => $this->config->getStorageUpload($image->getStorage())->getUrl(
                        str_replace('.', '_thumb.', $image->getFilename())
                    ),
                    'description' => $image->getDescription(),
                    'caption' => $image->getTitle(),
                ];
            }, $paginator->getIterator()->getArrayCopy()),
            'currentPage' => $pagination->getCurrentPage(),
            'nextPage' => $pagination->getNextPage(),
            'prevPage' => $pagination->getPrevPage(),
            'totalItems' => $pagination->getTotalItems(),
            'totalPages' => $pagination->getTotalPages(),
            'offset' => $pagination->getOffset(),
        ]);
    }
}
