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
use EnjoysCMS\Core\Components\Modules\ModuleConfig;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\SimpleGallery\Config;
use EnjoysCMS\Module\SimpleGallery\UploadFileStorage;
use Psr\Http\Message\UploadedFileInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Upload implements ModelInterface
{

    private ModuleConfig $config;

    public function __construct(
        private ServerRequestWrapperInterface $request,
        private RendererInterface $renderer,
        private EntityManager $em,
        private UrlGeneratorInterface $urlGenerator
    ) {
        $this->config = Config::getConfig();
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
        $form->file('image', 'Изображение')
            ->setMultiple()
            ->addRule(
                Rules::UPLOAD,
                [
                    'required',
                    'maxsize' => 1024 * 1024 * 2,
                    'extensions' => 'jpg, png, jpeg',
                ]
            )
            ->setAttribute(AttributeFactory::create('accept', '.png, .jpg, .jpeg'));

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
    private function uploadFile(UploadedFileInterface $file): void
    {
        $storage = $this->config->get('uploadStorage');


        /** @var UploadFileStorage $fileStorage */
        $uploadDirectory = $_ENV['UPLOAD_DIR'] . '/' . trim($this->config->get('uploadDir'), '/\\');
        $fileStorage = new $storage(
            $uploadDirectory . '/' . $this->getUploadSubDir()
        );
        $fileStorage->upload($file, $this->getNewFilename());

        $hash = md5_file($fileStorage->getTargetPath());

        $imageDto = new ImageDto(
            str_replace($uploadDirectory, '', $fileStorage->getTargetPath()),
            $hash,
            pathinfo($file->getClientFilename(), PATHINFO_FILENAME)
        );

        try {
            new WriteImage($this->em, $imageDto);
            Thumbnail::create($fileStorage->getTargetPath());
        } catch (\Throwable $e) {
            if (file_exists($fileStorage->getTargetPath())) {
                unlink($fileStorage->getTargetPath());
            }
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


}
