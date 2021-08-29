<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\SimpleGallery\Controller;

use App\Module\Admin\BaseController;
use Doctrine\ORM\EntityManager;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Module\SimpleGallery\Admin\Index;
use EnjoysCMS\Module\SimpleGallery\Config;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final class Admin extends BaseController
{
    public function __construct(
        Environment $twig,
        ServerRequestInterface $serverRequest,
        EntityManager $entityManager,
        UrlGeneratorInterface $urlGenerator,
        RendererInterface $renderer
    ) {
        parent::__construct($twig, $serverRequest, $entityManager, $urlGenerator, $renderer);
        $this->twigLoader->addPath(__DIR__ . '/../../template', 'simple-gallery');
    }

    #[Route(
        path: '/admin/gallery',
        name: 'admin/gallery',
        options: [
            'aclComment' => '[Admin][Simple Gallery] Просмотр всех изображений'
        ]
    )]
    public function index(ContainerInterface $container)
    {
        return $this->view(
            '@simple-gallery/admin/index.twig',
            $this->getContext($container->get(Index::class))
        );
    }
}