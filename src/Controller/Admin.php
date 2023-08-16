<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\SimpleGallery\Controller;

use DI\Container;
use DI\DependencyException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Enjoys\Forms\Elements\File;
use Enjoys\Forms\Exception\ExceptionRule;
use EnjoysCMS\Core\Exception\NotFoundException;
use EnjoysCMS\Core\Pagination\Pagination;
use EnjoysCMS\Core\Routing\Annotation\Route;
use EnjoysCMS\Module\Admin\AdminController;
use EnjoysCMS\Module\SimpleGallery\Admin\Delete;
use EnjoysCMS\Module\SimpleGallery\Admin\Download;
use EnjoysCMS\Module\SimpleGallery\Admin\UpdateDescription;
use EnjoysCMS\Module\SimpleGallery\Admin\UpdateTitle;
use EnjoysCMS\Module\SimpleGallery\Admin\UploadForm;
use EnjoysCMS\Module\SimpleGallery\Admin\UploadHandler;
use EnjoysCMS\Module\SimpleGallery\Config;
use EnjoysCMS\Module\SimpleGallery\Entities\Image;
use Exception;
use InvalidArgumentException;
use League\Flysystem\FilesystemException;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[Route('/admin/gallery', '@gallery_')]
final class Admin extends AdminController
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->twig->getLoader()->addPath(__DIR__ . '/../../template', 'simple-gallery');
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     * @throws NotFoundException
     * @throws NotSupported
     */
    #[Route(
        path: '@{page}',
        name: 'index',
        requirements: [
            'page' => '\d+'
        ],
        defaults: [
            'page' => 1,
        ],
        comment: 'Просмотр всех изображений'
    )]
    public function index(Config $config, EntityManager $em): ResponseInterface
    {
        $repository = $em->getRepository(Image::class);

        $pagination = new Pagination(
            $this->request->getAttribute('page', 1),
            $config->get('perPageLimit', 12)
        );
        $paginator = new Paginator(
            $repository->getOffsetItemsQueryBuilder(
                $pagination,
                $config->get('order->0', 'id'),
                $config->get('order->1', 'asc')
            )
        );
        $pagination->setTotalItems($paginator->count());

        return $this->response(
            $this->twig->render(
                '@simple-gallery/admin/index.twig',
                [
                    'config' => $config,
                    'images' => $paginator->getIterator(),
                    'pagination' => $pagination,
                ]
            )
        );
    }

    /**
     * @throws SyntaxError
     * @throws ExceptionRule
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route(
        path: '/upload',
        name: 'upload',
        comment: 'Загрузка изображений'
    )]
    public function upload(
        UploadForm $uploadForm,
        UploadHandler $uploadHandler,
        \EnjoysCMS\Module\Admin\Config $adminConfig,
        Config $config,
    ): ResponseInterface {
        $form = $uploadForm->getForm();

        if ($form->isSubmitted()) {
            try {
                $uploadHandler->upload($this->request);
                return $this->redirect->toRoute('@gallery_index');
            } catch (Throwable $e) {
                /** @var File $image */
                $image = $form->getElement('image[]');
                $image->setRuleError(htmlspecialchars(sprintf('%s: %s', get_class($e), $e->getMessage())));
            }
        }

        $rendererForm = $adminConfig->getRendererForm($form);

        return $this->response(
            $this->twig->render(
                '@simple-gallery/admin/upload.twig',
                [
                    'form' => $rendererForm->output()
                ]
            )
        );
    }

    #[Route(
        path: '/upload-dropzone',
        name: 'upload-dropzone',
        comment: 'Загрузка изображений с помощью dropzone.js'
    )]
    public function uploadDropzone(UploadHandler $uploadHandler): ResponseInterface
    {
        try {
            $uploadHandler->upload($this->request);
        } catch (Throwable $e) {
            $errorMessage = htmlspecialchars(sprintf('%s: %s', get_class($e), $e->getMessage()));
            $code = 500;
        }
        return $this->json($errorMessage ?? 'uploaded', $code ?? 200);
    }


    /**
     * @throws SyntaxError
     * @throws ExceptionRule
     * @throws \DI\NotFoundException
     * @throws RuntimeError
     * @throws LoaderError
     * @throws DependencyException
     * @throws FilesystemException
     */
    #[Route(
        path: '/download',
        name: 'download',
        comment: 'Загрузка изображений из интернета'
    )]
    public function download(
        Download $download,
        \EnjoysCMS\Module\Admin\Config $adminConfig,
    ): ResponseInterface {
        $form = $download->getForm();

        if ($form->isSubmitted()) {
            try {
                $download->doAction($this->request);
                return $this->redirect->toRoute('@gallery_index');
            } catch (Exception $e) {
                /** @var File $image */
                $image = $form->getElement('image');
                $image->setRuleError(htmlspecialchars(sprintf('%s: %s', get_class($e), $e->getMessage())));
            }
        }

        $rendererForm = $adminConfig->getRendererForm($form);

        return $this->response(
            $this->twig->render(
                '@simple-gallery/admin/upload.twig',
                [
                    'form' => $rendererForm->output()
                ]
            )
        );
    }

    /**
     * @throws DependencyException
     * @throws FilesystemException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws \DI\NotFoundException
     */
    #[Route(
        path: '/delete',
        name: 'delete',
        comment: 'Удаление изображений'
    )]
    public function delete(
        Delete $delete,
        \EnjoysCMS\Module\Admin\Config $adminConfig,
        Config $config,
    ): ResponseInterface {
        $form = $delete->getForm();

        if ($form->isSubmitted()) {
            try {
                $delete->doAction();
                return $this->redirect->toRoute('@gallery_index');
            } catch (Exception $e) {
                /** @var File $image */
                $image = $form->getElement('image');
                $image->setRuleError($e->getMessage());
            }
        }

        $rendererForm = $adminConfig->getRendererForm($form);

        return $this->response(
            $this->twig->render(
                '@simple-gallery/admin/delete.twig',
                [
                    'form' => $rendererForm->output(),
                    'image' => $delete->getImage(),
                    'config' => $config,
                ]
            )
        );
    }

    #[Route(
        path: '/update-description',
        name: 'update_description',
        comment: 'Установка описания для изображений'
    )]
    public function updateDescription(EntityManager $em): ResponseInterface
    {
        try {
            $data = json_decode($this->request->getBody()->getContents());
            $image = $em->getRepository(Image::class)->find($data->id) ?? throw new InvalidArgumentException(
                'Не правильный id изображения'
            );

            $image->setDescription(trim($data->comment));
            $em->flush();
            $result = 'ok';
        } catch (Exception $e) {
            $code = 500;
            $result = $e->getMessage();
        } finally {
            return $this->json($result, $code ?? 200);
        }
    }


    #[Route(
        path: '/update-title',
        name: 'update_title',
        comment: 'Установка заголовка для изображений'
    )]
    public function updateTitle(EntityManager $em): ResponseInterface
    {
        try {
            $data = json_decode($this->request->getBody()->getContents());
            $image = $em->getRepository(Image::class)->find($data->id) ?? throw new InvalidArgumentException(
                'Не правильный id изображения'
            );

            $image->setTitle(trim($data->comment));
            $em->flush();
            $result = 'ok';
        } catch (Exception $e) {
            $code = 500;
            $result = $e->getMessage();
        } finally {
            return $this->json($result, $code ?? 200);
        }
    }
}
