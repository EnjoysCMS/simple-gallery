<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\SimpleGallery\Admin;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Enjoys\Forms\Elements\File;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\ServerRequestWrapperInterface;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\SimpleGallery\Config;
use EnjoysCMS\Module\SimpleGallery\Entities\Image;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Delete implements ModelInterface
{

    private Image $image;

    public function __construct(
        private EntityManager $entityManager,
        private ServerRequestWrapperInterface $request,
        private RendererInterface $renderer,
        private UrlGeneratorInterface $urlGenerator,
        private Config $config
    ) {
        $image = $this->entityManager->getRepository(Image::class)->findOneBy(
            ['id' => $this->request->getQueryData('id', 0)]
        );
        if ($image === null) {
            throw new \InvalidArgumentException('Нет изображения с таким id');
        }

        $this->image = $image;
    }

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
            'form' => $this->renderer->output(),
            'image' => $this->image,
            'config' => $this->config,
        ];
    }

    private function getForm(): Form
    {
        $form = new Form();
        $form->setMethod('post');
        $form->submit('delete', 'Удалить');
        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function doAction()
    {
        $this->entityManager->remove($this->image);
        $this->entityManager->flush();

        $storage = $this->config->getStorageUpload($this->image->getStorage());
        $filesystem = $storage->getFileSystem();

        try {
            $filesystem->delete($this->image->getFilename());
            $filesystem->delete(
                str_replace(
                    '.',
                    '_thumb.',
                    $this->image->getFilename()
                )
            );
        } catch (\TypeError) {
        }


        Redirect::http($this->urlGenerator->generate('admin/gallery'));
    }
}
