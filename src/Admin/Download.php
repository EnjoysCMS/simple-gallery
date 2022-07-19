<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\SimpleGallery\Admin;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Enjoys\Forms\Elements\File;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use Enjoys\ServerRequestWrapperInterface;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\SimpleGallery\Config;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use function Enjoys\FileSystem\createDirectory;

final class Download implements ModelInterface
{

    public function __construct(
        private ServerRequestWrapperInterface $request,
        private EntityManager $em,
        private RendererInterface $renderer,
        private UrlGeneratorInterface $urlGenerator,
        private Config $config
    ) {
    }

    /**
     * @throws ExceptionRule
     */
    public function getContext(): array
    {
        $form = $this->getForm();

        if ($form->isSubmitted()) {
            try {
                $this->doAction();
            } catch (\Exception $e) {
                /** @var File $image */
                $image = $form->getElement('image');
                $image->setRuleError($e->getMessage());
            }
        }

        $this->renderer->setForm($form);

        return [
            'form' => $this->renderer->output()
        ];
    }

    /**
     * @throws ExceptionRule
     */
    private function getForm(): Form
    {
        $form = new Form();
        $form->url('image', 'Изображение')->addRule(Rules::REQUIRED);
        $form->submit('upload', 'Download');
        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws \Exception
     */
    private function doAction()
    {
//        /** @var UploadedFileInterface $file */
//        $file = $this->serverRequest->files('image');
//
//
//        $storage = $this->config->get('uploadStorage');
//
//
//        /** @var UploadFileStorage $fileStorage */
//        $uploadDirectory = $_ENV['UPLOAD_DIR'] . '/' . trim($this->config->get('uploadDir'), '/\\');
//        $fileStorage = new $storage(
//            $uploadDirectory . '/' . $this->getUploadSubDir()
//        );
//        $fileStorage->upload($file, $this->getNewFilename());
//
//        $hash = md5_file($fileStorage->getTargetPath());
//
//        $imageDto = new ImageDto(
//            str_replace($uploadDirectory, '', $fileStorage->getTargetPath()),
//            $hash,
//            pathinfo($file->getClientFilename(), PATHINFO_FILENAME)
//        );
//
//        try {
//            new WriteImage($this->em, $imageDto);
//            Thumbnail::create($fileStorage->getTargetPath());
//        } catch (\Exception $e) {
//            if (file_exists($fileStorage->getTargetPath())) {
//                unlink($fileStorage->getTargetPath());
//            }
//            throw $e;
//        }

        $uploadDirectory = $_ENV['UPLOAD_DIR'] . '/' . trim(
                $this->config->getModuleConfig()->get('uploadDir'),
                '/\\'
            ) . '/' . $this->getUploadSubDir();

        createDirectory($uploadDirectory);

        $client = new Client(
            [
                'verify' => false,
                RequestOptions::IDN_CONVERSION => true
            ]
        );
        $response = $client->get($this->request->getPostData('image'));
        $data = $response->getBody()->getContents();
        $ext = $this->getExt($response->getHeaderLine('Content-Type'));
        $targetPath = $uploadDirectory . '/' . $this->getNewFilename() . '.' . $ext;

        file_put_contents($targetPath, $data);

        $hash = md5_file($targetPath);

        $imageDto = new ImageDto(
            str_replace($uploadDirectory, '/' . $this->getUploadSubDir(), $targetPath),
            $hash
        );

        try {
            new WriteImage($this->em, $imageDto);
            Thumbnail::create($targetPath);
        } catch (\Exception $e) {
            if (file_exists($targetPath)) {
                unlink($targetPath);
            }
            throw $e;
        }

        Redirect::http($this->urlGenerator->generate('admin/gallery'));
    }

    private function getExt($content_type)
    {
        $mime_types = array(
            // images
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/gif' => 'gif',
            'image/bmp' => 'bmp',
            'image/vnd.microsoft.icon' => 'ico',
            'image/tiff' => 'tiff',
            'image/svg+xml' => 'svg',
//            'image/svg+xml' => 'svgz',
        );

        if (array_key_exists($content_type, $mime_types)) {
            return $mime_types[$content_type];
        } else {
            return null;
        }
    }

    private function getUploadSubDir(): string
    {
        return date('Y') . '/' . date('m');
    }

    private function getNewFilename(): ?string
    {
        return uniqid('image');
    }


}
