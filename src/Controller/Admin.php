<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\SimpleGallery\Controller;

use EnjoysCMS\Module\Admin\AdminBaseController;
use EnjoysCMS\Module\SimpleGallery\Admin\Delete;
use EnjoysCMS\Module\SimpleGallery\Admin\Download;
use EnjoysCMS\Module\SimpleGallery\Admin\Index;
use EnjoysCMS\Module\SimpleGallery\Admin\UpdateDescription;
use EnjoysCMS\Module\SimpleGallery\Admin\UpdateTitle;
use EnjoysCMS\Module\SimpleGallery\Admin\Upload;
use EnjoysCMS\Module\SimpleGallery\Admin\UploadDropzone;
use EnjoysCMS\Module\SimpleGallery\Admin\UploadHandler;
use Exception;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;

final class Admin extends AdminBaseController
{
    public function __construct(private ContainerInterface $container)
    {
        parent::__construct($this->container);
        $this->getTwig()->getLoader()->addPath(__DIR__ . '/../../template', 'simple-gallery');
    }

    #[Route(
        path: '/admin/gallery@{page}',
        name: 'admin/gallery',
        requirements: [
            'page' => '\d+'
        ],
        options: [
            'aclComment' => '[Admin][Simple Gallery] Просмотр всех изображений'
        ],
        defaults: [
            'page' => 1,
        ]
    )]
    public function index(): ResponseInterface
    {
        return $this->responseText(
            $this->view(
                '@simple-gallery/admin/index.twig',
                $this->getContext($this->container->get(Index::class))
            )
        );
    }

    #[Route(
        path: '/admin/gallery/upload',
        name: 'admin/gallery/upload',
        options: [
            'aclComment' => '[Admin][Simple Gallery] Загрузка изображений'
        ]
    )]
    public function upload(): ResponseInterface
    {
        return $this->responseText(
            $this->view(
                '@simple-gallery/admin/upload.twig',
                $this->getContext($this->container->get(Upload::class))
            )
        );
    }

    #[Route(
        path: '/admin/gallery/upload-dropzone',
        name: 'admin/gallery/upload-dropzone',
        options: [
            'aclComment' => '[Admin][Simple Gallery] Загрузка изображений с помощью dropzone.js'
        ]
    )]
    public function uploadDropzone(UploadHandler $uploadHandler): ResponseInterface
    {
        try {
            $uploadHandler->upload();
        } catch (\Throwable $e) {
            $this->response = $this->response->withStatus(500);
            $errorMessage = htmlspecialchars(sprintf('%s: %s', get_class($e), $e->getMessage()));
        }
        return $this->responseJson($errorMessage ?? 'uploaded');
    }

    #[Route(
        path: '/admin/gallery/download',
        name: 'admin/gallery/download',
        options: [
            'aclComment' => '[Admin][Simple Gallery] Загрузка изображений из интернета'
        ]
    )]
    public function download(): ResponseInterface
    {
        return $this->responseText(
            $this->view(
                '@simple-gallery/admin/upload.twig',
                $this->getContext($this->container->get(Download::class))
            )
        );
    }

    #[Route(
        path: '/admin/gallery/delete',
        name: 'admin/gallery/delete',
        options: [
            'aclComment' => '[Admin][Simple Gallery] Удаление изображений'
        ]
    )]
    public function delete(): ResponseInterface
    {
        return $this->responseText(
            $this->view(
                '@simple-gallery/admin/delete.twig',
                $this->getContext($this->container->get(Delete::class))
            )
        );
    }

    #[Route(
        path: '/admin/gallery/update-description',
        name: 'admin/gallery/updateDescription',
        options: [
            'aclComment' => '[Admin][Simple Gallery] Установка описания для изображений'
        ]
    )]
    public function updateDescription(): ResponseInterface
    {
        try {
            $this->container->get(UpdateDescription::class)->update();
            $result = 'ok';
            $code = 200;
        } catch (Exception $e) {
            $code = 500;
            $result = $e->getMessage();
        } finally {
            $this->response =
                $this->response
                    ->withStatus($code)
            ;

            return $this->responseJson($result);
        }
    }


    #[Route(
        path: '/admin/gallery/update-title',
        name: 'admin/gallery/updateTitle',
        options: [
            'aclComment' => '[Admin][Simple Gallery] Установка заголовка для изображений'
        ]
    )]
    public function updateTitle(): ResponseInterface
    {
        try {
            $this->container->get(UpdateTitle::class)->update();
            $result = 'ok';
            $code = 200;
        } catch (Exception $e) {
            $code = 500;
            $result = $e->getMessage();
        } finally {
            $this->response =
                $this->response
                    ->withStatus($code)
            ;

            return $this->responseJson($result);
        }
    }
}
