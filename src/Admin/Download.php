<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\SimpleGallery\Admin;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Rules;
use EnjoysCMS\Module\SimpleGallery\Config;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use League\Flysystem\FilesystemException;
use Psr\Http\Message\ServerRequestInterface;

final class Download
{

    public function __construct(
        private readonly EntityManager $em,
        private readonly Config $config
    ) {
    }


    /**
     * @throws ExceptionRule
     */
    public function getForm(): Form
    {
        $form = new Form();
        $form->url('image', 'Изображение')->addRule(Rules::REQUIRED);
        $form->submit('upload', 'Download');
        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws \Doctrine\ORM\ORMException
     * @throws FilesystemException
     * @throws GuzzleException
     */
    public function doAction(ServerRequestInterface $request): void
    {
        $storage = $this->config->getStorageUpload();
        $filesystem = $storage->getFileSystem();
        /** @var class-string<ThumbnailServiceInterface> $thumbnailService */
        $thumbnailService = $this->config->get('thumbnailService');


        $client = new Client(
            [
                'verify' => false,
                RequestOptions::IDN_CONVERSION => true
            ]
        );
        $response = $client->get((string)($request->getParsedBody()['image'] ?? null));
        $data = $response->getBody()->getContents();
        $extension = $this->getExt($response->getHeaderLine('Content-Type'));
        $targetPath = $this->getUploadSubDir() . '/' . $this->getNewFilename() . '.' . ($extension ?? '');

        $filesystem->write($targetPath, $data);

        $fileContent = $filesystem->read($targetPath);
        $hash = md5($fileContent);

        $imageDto = new ImageDto(
            $targetPath,
            $hash
        );
        $imageDto->storage = (string)$this->config->get('uploadStorage');

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
            $this->em->flush();
        } catch (Exception $e) {
            $filesystem->delete($targetPath);
            throw $e;
        }
    }

    private function getExt(string $content_type): ?string
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

    private function getNewFilename(): string
    {
        return uniqid('image');
    }


}
