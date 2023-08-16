<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\SimpleGallery\Admin;


use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Rules;
use EnjoysCMS\Module\SimpleGallery\Config;

final class UploadForm
{

    public function __construct(private readonly Config $config)
    {
    }


    /**
     * @throws ExceptionRule
     */
    public function getForm(): Form
    {
        $extensionWithoutDot = implode(
            ', ',
            (array)($this->config->get('uploadRules->allowedExtensions') ?? ['jpg', 'png', 'jpeg'])
        );
        $extensionWithDot = implode(
            ', ',
            array_map(function ($ext) {
                return '.' . $ext;
            }, explode(', ', $extensionWithoutDot))
        );

        $form = new Form();
        $form->file('image', 'Изображение')
            ->setMultiple()
            ->addRule(
                Rules::UPLOAD,
                [
                    'required',
                    'maxsize' => $this->config->get('uploadRules->maxSize') ?? 1024 * 1024,
                    'extensions' => $extensionWithoutDot,
                ]
            )
            ->setAttribute(AttributeFactory::create('accept', $extensionWithDot));

        $form->submit('upload', 'Загрузить');
        return $form;
    }

}
