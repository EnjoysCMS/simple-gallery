<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\SimpleGallery\Admin;


use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\Bootstrap4\Bootstrap4;
use Enjoys\Forms\Rules;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Components\Modules\ModuleConfig;
use EnjoysCMS\Module\SimpleGallery\Config;
use EnjoysCMS\Module\SimpleGallery\Entities\Image;
use EnjoysCMS\Module\SimpleGallery\UploadFileStorage;
use Psr\Http\Message\UploadedFileInterface;

final class Upload implements ModelInterface
{

    private ModuleConfig $config;

    public function __construct(private ServerRequestInterface $serverRequest, private EntityManager $em)
    {
        $this->config = Config::getConfig();
    }

    /**
     * @throws ExceptionRule
     */
    public function getContext(): array
    {
        $form = $this->getForm();

        if ($form->isSubmitted()) {
            $this->doAction();
        }

        $renderer = new Bootstrap4([], $form);

        return [
            'form' => $renderer->render()
        ];
    }

    /**
     * @throws ExceptionRule
     */
    private function getForm(): Form
    {
        $form = new Form();
        $form->file('image', 'Изображение')
            ->addRule(
                Rules::UPLOAD,
                null,
                [
                    'required',
                    'maxsize' => 1024 * 1024 * 2,
                    'extensions' => 'jpg, png, jpeg',
                ]
            )
            ->setAttribute('accept', '.png, .jpg, .jpeg');

        $form->submit('upload');
        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function doAction()
    {
        /** @var UploadedFileInterface $file */
        $file = $this->serverRequest->files('image');


        $storage = $this->config->get('uploadStorage');


        /** @var UploadFileStorage $fileStorage */
        $fileStorage = new $storage(
            $_ENV['UPLOAD_DIR'] . '/' . trim($this->config->get('uploadDir'), '/\\') . '/' . $this->getUploadSubDir()
        );
        $fileStorage->upload($file, $this->getNewFilename());


        $image = new Image();
        $image->setOriginalName($file->getClientFilename());
        $image->setPath($this->getUploadSubDir() .'/'.$fileStorage->getFilename());
        $image->setHash(md5_file($fileStorage->getTargetPath()));
        $image->setFileName($fileStorage->getFilename());


        try {
            $this->em->persist($image);
            $this->em->flush();
        } catch (\Exception $e) {
            unlink($fileStorage->getTargetPath());
            throw $e;
        }


        Redirect::http();
    }



    private function getUploadSubDir(): string
    {
        return  date('Y') . '/' . date('m');
    }

    private function getNewFilename(): ?string
    {
        return uniqid('image');
    }


}