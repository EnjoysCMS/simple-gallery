<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\SimpleGallery\Admin;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\ServerRequestWrapperInterface;
use Enjoys\Upload\File;
use EnjoysCMS\Module\SimpleGallery\Config;
use League\Flysystem\FilesystemException;
use Psr\Http\Message\UploadedFileInterface;

final class UploadHandler
{
    public function __construct(
        private ServerRequestWrapperInterface $request,
        private EntityManager $em,
        private Config $config
    ) {
    }

    /**
     * @throws OptimisticLockException
     * @throws \Throwable
     * @throws \Doctrine\ORM\ORMException
     * @throws FilesystemException
     */
    public function upload(): void
    {
        /** @var UploadedFileInterface|UploadedFileInterface[] $file */
        $file = $this->request->getFilesData('image');

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
     * @throws \Throwable
     * @throws \Doctrine\ORM\ORMException
     * @throws ORMException
     * @throws FilesystemException
     */
    private function uploadFile(UploadedFileInterface $uploadedFile): void
    {
        $storage = $this->config->getStorageUpload();
        $filesystem = $storage->getFileSystem();
        /** @var class-string<ThumbnailServiceInterface> $thumbnailService */
        $thumbnailService = $this->config->getModuleConfig()->get('thumbnailService');


        try {
            $file = new File($uploadedFile, $filesystem);
            $file->setFilename($this->getNewFilename());
            $file->upload($this->getUploadSubDir());

            $fileContent = $filesystem->read($file->getTargetPath());

            $this->checkMemory($fileContent);

            $hash = md5($fileContent);

            $imageDto = new ImageDto(
                $file->getTargetPath(),
                $hash
            );
            $imageDto->title = rtrim($file->getOriginalFilename(), $file->getExtensionWithDot());
            $imageDto->storage = $this->config->getModuleConfig()->get('uploadStorage');

            new WriteImage($this->em, $imageDto);

            $thumbnailService::create(
                str_replace(
                    '.',
                    '_thumb.',
                    $file->getTargetPath()
                ),
                $fileContent,
                $filesystem
            );

            $this->em->flush();
        } catch (\Throwable $e) {
            $filesystem->delete((string)$file->getTargetPath());
            throw $e;
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
            if (!$this->config->getModuleConfig()->get('allocatedMemoryDynamically')) {
                throw new \RuntimeException(
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
