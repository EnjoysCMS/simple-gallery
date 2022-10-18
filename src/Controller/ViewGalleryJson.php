<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\SimpleGallery\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;
use EnjoysCMS\Core\BaseController;
use EnjoysCMS\Core\Components\Helpers\Setting;
use EnjoysCMS\Core\Components\Pagination\Pagination;
use EnjoysCMS\Core\Exception\NotFoundException;
use EnjoysCMS\Module\SimpleGallery\Config;
use EnjoysCMS\Module\SimpleGallery\Entities\Image;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[Route(
    path: 'gallery.json',
    name: 'gallery/json',
    options: [
        'comment' => '[Public] Просмотр изображений JSON'
    ],
)]
final class ViewGalleryJson extends BaseController
{
    /**
     * @param Environment $twig
     * @param EntityManager $em
     * @param Config $config
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws NotFoundException
     */
    public function __invoke(
        Environment $twig,
        EntityManager $em,
        Config $config,
        ServerRequestInterface $request
    ): ResponseInterface {

        $page = (int)($request->getParsedBody()['page']
            ?? json_decode($request->getBody()->getContents(), true)['page']
            ?? $request->getQueryParams()['page']
            ?? 1);

        $pagination = new Pagination(
            $page,
            $config->getModuleConfig()->get('perPageLimit', false)
        );

        $qb = $em->createQueryBuilder()
            ->select('i')
            ->from(Image::class, 'i')
            ->setFirstResult($pagination->getOffset())
            ->setMaxResults(
                $pagination->getLimitItems()
            )
        ;
        $paginator = new Paginator($qb);
        $pagination->setTotalItems($paginator->count());

        return $this->responseJson([
            'images' => array_map(function ($image) use ($config) {
                /** @var Image $image */
                return [
                    'src' => $config->getStorageUpload($image->getStorage())->getUrl($image->getFilename()),
                    'thumb' => $config->getStorageUpload($image->getStorage())->getUrl(
                        str_replace('.', '_thumb.', $image->getFilename())
                    ),
                    'description' => $image->getDescription(),
                    'caption' => $image->getTitle(),
                ];
            }, $paginator->getIterator()->getArrayCopy()),
            'currentPage' => $pagination->getCurrentPage(),
            'nextPage' => $pagination->getNextPage(),
        ]);
    }
}
