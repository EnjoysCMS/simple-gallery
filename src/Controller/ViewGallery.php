<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\SimpleGallery\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;
use EnjoysCMS\Core\BaseController;
use EnjoysCMS\Core\Components\Helpers\Setting;
use EnjoysCMS\Core\Components\Pagination\Pagination;
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
    path: 'gallery/{page}',
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
final class ViewGallery extends BaseController
{
    /**
     * @throws NotFoundExceptionInterface
     * @throws SyntaxError
     * @throws ContainerExceptionInterface
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function __invoke(
        Environment $twig,
        EntityManager $em,
        Config $config,
        ServerRequestInterface $request
    ): ResponseInterface {
        $pagination = new Pagination(
            $request->getAttribute('page', 1),
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
                'config' => $config
            ])
        );
    }
}
