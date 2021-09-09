<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\SimpleGallery\Controller;

use App\Module\Admin\BaseController;
use Doctrine\ORM\EntityManager;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Module\SimpleGallery\Admin\Delete;
use EnjoysCMS\Module\SimpleGallery\Admin\Download;
use EnjoysCMS\Module\SimpleGallery\Admin\Index;
use EnjoysCMS\Module\SimpleGallery\Admin\UpdateDescription;
use EnjoysCMS\Module\SimpleGallery\Admin\UpdateTitle;
use EnjoysCMS\Module\SimpleGallery\Admin\Upload;
use EnjoysCMS\Module\SimpleGallery\Config;
use HttpSoft\Emitter\EmitterInterface;
use HttpSoft\Message\Response;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

use function DI\get;

final class Admin extends BaseController
{
    public function __construct(private ContainerInterface $container) {
        parent::__construct($this->container);
        $this->getTwig()->getLoader()->addPath(__DIR__ . '/../../template', 'simple-gallery');
    }

    #[Route(
        path: '/admin/gallery',
        name: 'admin/gallery',
        options: [
            'aclComment' => '[Admin][Simple Gallery] Просмотр всех изображений'
        ]
    )]
    public function index(): string
    {
        return $this->view(
            '@simple-gallery/admin/index.twig',
            $this->getContext($this->container->get(Index::class))
        );
    }

    #[Route(
        path: '/admin/gallery/upload',
        name: 'admin/gallery/upload',
        options: [
            'aclComment' => '[Admin][Simple Gallery] Загрузка изображений'
        ]
    )]
    public function upload(): string
    {
        return $this->view(
            '@simple-gallery/admin/upload.twig',
            $this->getContext($this->container->get(Upload::class))
        );
    }

    #[Route(
        path: '/admin/gallery/download',
        name: 'admin/gallery/download',
        options: [
            'aclComment' => '[Admin][Simple Gallery] Загрузка изображений из интернета'
        ]
    )]
    public function download(): string
    {
        return $this->view(
            '@simple-gallery/admin/upload.twig',
            $this->getContext($this->container->get(Download::class))
        );
    }

    #[Route(
        path: '/admin/gallery/delete',
        name: 'admin/gallery/delete',
        options: [
            'aclComment' => '[Admin][Simple Gallery] Удаление изображений'
        ]
    )]
    public function delete(): string
    {
        return $this->view(
            '@simple-gallery/admin/delete.twig',
            $this->getContext($this->container->get(Delete::class))
        );
    }

    #[Route(
        path: '/admin/gallery/update-description',
        name: 'admin/gallery/updateDescription',
        options: [
            'aclComment' => '[Admin][Simple Gallery] Установка описания для изображений'
        ]
    )]
    public function updateDescription(Response $response, EmitterInterface $emitter)
    {

        try {
            $this->container->get(UpdateDescription::class)->update();
            $result = 'ok';
            $code = 200;
        } catch (\Exception $e) {
            $code = 500;
            $result = $e->getMessage();
        } finally {
            $response =
                $response
                    ->withStatus($code)
                    ->withHeader(
                        'Content-Type',
                        'application/json',
                    );

            $response->getBody()->write(
                json_encode($result)
            );

            $emitter->emit($response);
        }
    }


    #[Route(
        path: '/admin/gallery/update-title',
        name: 'admin/gallery/updateTitle',
        options: [
            'aclComment' => '[Admin][Simple Gallery] Установка заголовка для изображений'
        ]
    )]
    public function updateTitle(Response $response, EmitterInterface $emitter)
    {

        try {
            $this->container->get(UpdateTitle::class)->update();
            $result = 'ok';
            $code = 200;
        } catch (\Exception $e) {
            $code = 500;
            $result = $e->getMessage();
        } finally {
            $response =
                $response
                    ->withStatus($code)
                    ->withHeader(
                        'Content-Type',
                        'application/json',
                    );

            $response->getBody()->write(
                json_encode($result)
            );

            $emitter->emit($response);
        }
    }
}