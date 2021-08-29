<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\SimpleGallery;

use DI\FactoryInterface;
use EnjoysCMS\Core\Components\Modules\ModuleConfig;
use Psr\Container\ContainerInterface;

final class Config
{

    public static function getConfig(ContainerInterface $container): ModuleConfig
    {
        $composer = \json_decode(\file_get_contents(__DIR__ . '/../composer.json'));
        return $container
            ->get(FactoryInterface::class)
            ->make(ModuleConfig::class, ['moduleName' => $composer->name]);
    }
}