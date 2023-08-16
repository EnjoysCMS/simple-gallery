<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\SimpleGallery\Admin;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Form;
use EnjoysCMS\Module\SimpleGallery\Config;
use EnjoysCMS\Module\SimpleGallery\Entities\Image;
use InvalidArgumentException;
use League\Flysystem\FilesystemException;
use Psr\Http\Message\ServerRequestInterface;
use TypeError;

final class Delete
{

    private Image $image;

    /**
     * @throws NotSupported
     */
    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
        private readonly Config $config
    ) {
        $this->image = $this->em->getRepository(Image::class)->findOneBy(
            ['id' => $this->request->getQueryParams()['id'] ?? 0]
        ) ?? throw new InvalidArgumentException('Нет изображения с таким id');
    }


    public function getForm(): Form
    {
        $form = new Form();
        $form->submit('delete', 'Удалить');
        return $form;
    }


    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws FilesystemException
     */
    public function doAction(): void
    {
        $this->em->remove($this->image);
        $this->em->flush();

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
        } catch (TypeError) {
        }
    }

    public function getImage(): Image
    {
        return $this->image;
    }
}
