<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\SimpleGallery\Controller;

use Doctrine\ORM\EntityManager;
use EnjoysCMS\Module\SimpleGallery\Config;
use EnjoysCMS\Module\SimpleGallery\Entities\Image;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

#[Route(
    path: 'gallery',
    name: 'gallery',
    options: [
        'aclComment' => '[Public] Просмотр изображений'
    ]
)]
final class ViewGallery
{
    public function __invoke(ContainerInterface $container)
    {
        /** @var Environment $twig */
        $twig = $container->get(Environment::class);
        $images = $container->get(EntityManager::class)->getRepository(Image::class)->findAll();

        $template_path = '@m/simple-gallery/view_gallery.twig';

        if (!$twig->getLoader()->exists($template_path)) {
            $template_path = __DIR__ . '/../../template/view_gallery.twig';
        }

        return $twig->render($template_path, [
            'images' => $images,
            'config' => Config::getConfig()->getAll()
        ]);
    }
}