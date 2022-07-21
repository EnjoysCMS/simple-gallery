<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\SimpleGallery\Admin;


use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Elements\File;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Upload implements ModelInterface
{

    public function __construct(
        private UploadHandler $uploadHandler,
        private RendererInterface $renderer,
        private UrlGeneratorInterface $urlGenerator
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
                $this->uploadHandler->upload();
                Redirect::http($this->urlGenerator->generate('admin/gallery'));
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

        $form->submit('upload', 'Загрузить');
        return $form;
    }

}
