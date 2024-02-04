<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\SimpleGallery\Admin;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Upload\Rule\Extension;
use Enjoys\Upload\Rule\Size;
use Enjoys\Upload\UploadProcessing;
use EnjoysCMS\Module\SimpleGallery\Config;
use League\Flysystem\FilesystemException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Throwable;

final class UploadHandler
{
    public function __construct(
        private readonly EntityManager $em,
        private readonly Config $config
    ) {
    }

    /**
     * @throws OptimisticLockException
     * @throws Throwable
     * @throws \Doctrine\ORM\ORMException
     * @throws FilesystemException
     */
    public function upload(ServerRequestInterface $request): void
    {
        /** @var UploadedFileInterface|UploadedFileInterface[] $file */
        $file = $request->getUploadedFiles()['image'] ?? null;

        if (is_array($file)) {
            foreach ($file as $item) {
                $this->uploadFile($item);
            }
        } else {
            $this->uploadFile($file);
        }
    }


    /**
     * @throws OptimisticLockException
     * @throws Throwable
     * @throws \Doctrine\ORM\ORMException
     * @throws ORMException
     * @throws FilesystemException
     */
    private function uploadFile(UploadedFileInterface $uploadedFile): void
    {
        $storage = $this->config->getStorageUpload();
        $filesystem = $storage->getFileSystem();
        /** @var class-string<ThumbnailServiceInterface> $thumbnailService */
        $thumbnailService = $this->config->get('thumbnailService');

        $sizeRule = new Size();
        $sizeRule->setMaxSize((int)($this->config->get('uploadRules->maxSize') ?? 1024 * 1024));

        $extensionRule = new Extension();
        /** @var string[]|string|null $allowedExtensions */
        $allowedExtensions = $this->config->get('uploadRules->allowedExtensions');
        $extensionRule->allow(
            $allowedExtensions ?? 'jpg, png, jpeg'
        );

        $file = new UploadProcessing($uploadedFile, $filesystem);

        try {
            $file->setFilename($this->getNewFilename());

            $file->addRules([$sizeRule, $extensionRule]);

            $file->upload($this->getUploadSubDir());

            $targetPath = $file->getTargetPath() ?? throw new \RuntimeException('$targetPath not set');

            $fileContent = $filesystem->read($targetPath);

            $this->checkMemory($fileContent);

            $hash = md5($fileContent);

            $imageDto = new ImageDto(
                $targetPath,
                $hash
            );
            $imageDto->title = rtrim(
                $file->getFileInfo()->getOriginalFilename(),
                $file->getFileInfo()->getExtensionWithDot()
            );
            $imageDto->storage = (string)($this->config->get('uploadStorage') ?? throw new \RuntimeException(
                'config->uploadStorage not set'
            ));

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
        } catch (Throwable $e) {
            $filesystem->delete($file->getTargetPath() ?? throw $e);
            throw $e;
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

    private function checkMemory(string $fileContent): void
    {
        $memoryLimit = (int)ini_get('memory_limit') * pow(1024, 2);
        $imageInfo = getimagesizefromstring($fileContent);
        $memoryNeeded = round(
            ($imageInfo[0] * $imageInfo[1] * ($imageInfo['bits'] ?? 1) * ($imageInfo['channels'] ?? 1) / 8 + Pow(
                    2,
                    16
                )) * 1.65
        );
        if (function_exists('memory_get_usage') && memory_get_usage() + $memoryNeeded > $memoryLimit) {
            if (!$this->config->get('allocatedMemoryDynamically')) {
                throw new RuntimeException(
                    sprintf(
                        'The allocated memory (%s MiB) is not enough for image processing. Needed: %s MiB',
                        $memoryLimit / pow(1024, 2),
                        ceil((memory_get_usage() + $memoryNeeded) / pow(1024, 2))
                    )
                );
            }

            ini_set(
                'memory_limit',
                (integer)ini_get('memory_limit') + ceil(
                    (memory_get_usage() + $memoryNeeded) / pow(1024, 2)
                ) . 'M'
            );
        }
    }
}
