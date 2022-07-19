<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\SimpleGallery;

use DI\DependencyException;
use DI\FactoryInterface;
use DI\NotFoundException;
use EnjoysCMS\Core\Components\Modules\ModuleConfig;

final class Config
{

    private ModuleConfig $config;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function __construct(FactoryInterface $factory)
    {
        $this->config = $factory->make(ModuleConfig::class, ['moduleName' => 'enjoyscms/simple-gallery']);
    }

    public function getModuleConfig(): ModuleConfig
    {
        return $this->config;
    }


}
