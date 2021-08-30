<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\SimpleGallery\Admin;


use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Enjoys\Forms\Elements\File;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\Bootstrap4\Bootstrap4;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Components\Modules\ModuleConfig;
use EnjoysCMS\Module\SimpleGallery\Config;
use EnjoysCMS\Module\SimpleGallery\Entities\Image;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Delete implements ModelInterface
{

    private Image $image;
    private ModuleConfig $config;

    public function __construct(
        private EntityManager $entityManager,
        private ServerRequestInterface $serverRequest,
        private UrlGeneratorInterface $urlGenerator
    ) {
        $image = $this->entityManager->getRepository(Image::class)->findOneBy(
            ['id' => $this->serverRequest->get('id', 0)]
        );
        if ($image === null) {
            throw new \InvalidArgumentException('Нет изображения с таким id');
        }

        $this->image = $image;
        $this->config = Config::getConfig();
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

        $renderer = new Bootstrap4([], $form);

        return [
            'form' => $renderer->render(),
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

        $imagePath = pathinfo(
            realpath($_ENV['UPLOAD_DIR'] . $this->config->get('uploadDir') . $this->image->getFilename())
        );
        $images = glob(
            $imagePath['dirname'] . DIRECTORY_SEPARATOR . $imagePath['filename'] . '*.' . $imagePath['extension']
        );

        foreach ($images as $image) {
            unlink($image);
        }

        Redirect::http($this->urlGenerator->generate('admin/gallery'));
    }
}