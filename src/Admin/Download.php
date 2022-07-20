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
                $image->setRuleError(htmlspecialchars($e->getMessage()));
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
        $storage = $this->config->getStorageUpload();
        $filesystem = $storage->getFileSystem();
        $thumbnailService = $this->config->getModuleConfig()->get('thumbnailService');


        $client = new Client(
            [
                'verify' => false,
                RequestOptions::IDN_CONVERSION => true
            ]
        );
        $response = $client->get($this->request->getPostData('image'));
        $data = $response->getBody()->getContents();
        $extension = $this->getExt($response->getHeaderLine('Content-Type'));
        $targetPath = $this->getUploadSubDir() . '/' . $this->getNewFilename() . '.' . $extension;

        $filesystem->write($targetPath, $data);

        $fileContent = $filesystem->read($targetPath);
        $hash = md5($fileContent);

        $imageDto = new ImageDto(
            $targetPath,
            $hash
        );
        $imageDto->storage = $this->config->getModuleConfig()->get('uploadStorage');

        try {
            new WriteImage($this->em, $imageDto);
            $thumbnailService::create(
                str_replace(
                    '.',
                    '_thumb.',
                    $targetPath
                ),
                $fileContent,
                $filesystem
            );
        } catch (\Exception $e) {
            $filesystem->delete($targetPath);
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
