<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\SimpleGallery;

use DI\DependencyException;
use DI\FactoryInterface;
use DI\NotFoundException;
use EnjoysCMS\Core\Components\Modules\ModuleConfig;
use EnjoysCMS\Core\StorageUpload\StorageUploadInterface;

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


    public function getStorageUpload(string $key = null): StorageUploadInterface
    {
        $key = $key ?? $this->config->get('uploadStorage');

        $config = $this->config->get('storageList')[$key] ?? throw new \RuntimeException(
                sprintf('Not set config `storageList.%s`', $key)
            );
        /** @var class-string<StorageUploadInterface> $storageUploadClass */
        $storageUploadClass = key($config);
        return new $storageUploadClass(...current($config));
    }

}
