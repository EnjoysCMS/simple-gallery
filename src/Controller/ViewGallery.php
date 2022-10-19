<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\SimpleGallery\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ObjectRepository;
use EnjoysCMS\Core\BaseController;
use EnjoysCMS\Core\Components\Helpers\Setting;
use EnjoysCMS\Core\Components\Pagination\Pagination;
use EnjoysCMS\Core\Exception\NotFoundException;
use EnjoysCMS\Module\SimpleGallery\Config;
use EnjoysCMS\Module\SimpleGallery\Entities\Image;
use EnjoysCMS\Module\SimpleGallery\Entities\ImageRepository;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;


final class ViewGallery extends BaseController
{

    private ImageRepository|ObjectRepository|EntityRepository $repository;

    public function __construct(
        private EntityManager $em,
        private Config $config,
        private ServerRequestInterface $request,
        ResponseInterface $response = null
    ) {
        parent::__construct($response);
        $this->repository = $this->em->getRepository(Image::class);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     * @throws NotFoundException
     * @throws \Exception
     */
    #[Route(
        path: 'gallery@{page}',
        name: 'gallery',
        requirements: [
            'page' => '\d+'
        ],
        options: [
            'aclComment' => '[Public] Просмотр изображений'
        ],
        defaults: [
            'page' => 1,
        ]
    )]
    public function viewGallery(Environment $twig): ResponseInterface
    {
        $pagination = new Pagination(
            $this->request->getAttribute('page', 1),
            $this->config->getModuleConfig()->get('perPageLimit', false)
        );

        $paginator = new Paginator($this->repository->getOffsetItemsQueryBuilder($pagination));
        $pagination->setTotalItems($paginator->count());

        $template_path = '@m/simple-gallery/view_gallery.twig';

        if (!$twig->getLoader()->exists($template_path)) {
            $template_path = __DIR__ . '/../../template/view_gallery.twig';
        }

        return $this->responseText(
            $twig->render($template_path, [
                '_title' => sprintf(
                    'Фотогалерея [стр. %2$s] - %1$s',
                    Setting::get('sitename'),
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
     * @throws \Exception
     */
    #[Route(
        path: 'gallery.json',
        name: 'gallery/json',
        options: [
            'comment' => '[Public] Просмотр изображений JSON'
        ],
    )]
    public function viewGalleryJson(): ResponseInterface
    {
        $page = (int)($this->request->getParsedBody()['page']
            ?? json_decode($this->request->getBody()->getContents(), true)['page']
            ?? $this->request->getQueryParams()['page']
            ?? 1);

        $pagination = new Pagination(
            $page,
            $this->config->getModuleConfig()->get('perPageLimit', false)
        );

        $paginator = new Paginator($this->repository->getOffsetItemsQueryBuilder($pagination));
        $pagination->setTotalItems($paginator->count());

        return $this->responseJson([
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
