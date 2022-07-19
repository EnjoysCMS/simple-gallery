<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\SimpleGallery\Controller;

use Doctrine\ORM\EntityManager;
use EnjoysCMS\Core\BaseController;
use EnjoysCMS\Module\SimpleGallery\Config;
use EnjoysCMS\Module\SimpleGallery\Entities\Image;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[Route(
    path: 'gallery',
    name: 'gallery',
    options: [
        'aclComment' => '[Public] Просмотр изображений'
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
    public function __invoke(Environment $twig, EntityManager $em, Config $config): ResponseInterface
    {
        $images = $em->getRepository(Image::class)->findAll();


        $template_path = '@m/simple-gallery/view_gallery.twig';

        if (!$twig->getLoader()->exists($template_path)) {
            $template_path = __DIR__ . '/../../template/view_gallery.twig';
        }

        return $this->responseText($twig->render($template_path, [
            'images' => $images,
            'config' => $config->getModuleConfig()->asArray()
        ]));
    }
}
