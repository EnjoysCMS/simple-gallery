<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\SimpleGallery\Blocks;


use EnjoysCMS\Core\Components\Blocks\AbstractBlock;
use Twig\Environment;

class ViewPhoto extends AbstractBlock
{

    public static function getBlockDefinitionFile(): string
    {
        return __DIR__ .'/../../blocks.yml';
    }

    public function view()
    {
        $twig = $this->container->get(Environment::class);
        return $twig->render(
            (string)$this->getOption('template'),
            [
                'options' => $this->getOptions()
            ]
        );
    }


}