<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\SimpleGallery\Admin;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Elements\File;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use Enjoys\ServerRequestWrapperInterface;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\SimpleGallery\Config;
use Psr\Http\Message\UploadedFileInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class UploadDropzone implements ModelInterface
{

    public function __construct(
        private ServerRequestWrapperInterface $request,
        private RendererInterface $renderer,
        private EntityManager $em,
        private UrlGeneratorInterface $urlGenerator,
        private Config $config
    ) {
        //  dd($this->config->getStorageUpload()->getFileSystem());
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
            } catch (\Throwable $e) {
                /** @var File $image */
                $image = $form->getElement('image[]');
                $image->setRuleError(htmlspecialchars(sprintf('%s: %s', get_class($e), $e->getMessage())));
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
        $form->file('image', 'Изображение')
            ->setMultiple()
            ->addRule(
                Rules::UPLOAD,
                [
                    'required',
                    'maxsize' => 1024 * 1024 * 10,
                    'extensions' => 'jpg, png, jpeg',
                ]
            )
            ->setAttribute(AttributeFactory::create('accept', '.png, .jpg, .jpeg'))
        ;

        $form->submit('upload');
        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws \Exception
     */
    private function doAction()
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

        Redirect::http($this->urlGenerator->generate('admin/gallery'));
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function uploadFile(UploadedFileInterface $uploadedFile): void
    {
        $storage = $this->config->getStorageUpload();
        $filesystem = $storage->getFileSystem();
        /** @var class-string<ThumbnailServiceInterface> $thumbnailService */
        $thumbnailService = $this->config->getModuleConfig()->get('thumbnailService');


        try {
            $file = new \Enjoys\Upload\File($uploadedFile, $filesystem);
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
            $filesystem->delete($file->getTargetPath());
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

    private function checkMemory(string $fileContent)
    {
        $memoryLimit = (int)ini_get('memory_limit') * pow(1024, 2);
        $imageInfo = getimagesizefromstring($fileContent);
        $memoryNeeded = round(
            ($imageInfo[0] * $imageInfo[1] * $imageInfo['bits'] * $imageInfo['channels'] / 8 + Pow(2, 16)) * 1.65
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
