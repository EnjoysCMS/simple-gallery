<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\SimpleGallery;


use EnjoysCMS\Core\Modules\AbstractModuleConfig;
use EnjoysCMS\Core\StorageUpload\StorageUploadInterface;
use RuntimeException;

final class Config extends AbstractModuleConfig
{


    public function getModulePackageName(): string
    {
        return 'enjoyscms/simple-gallery';
    }

    public function getStorageUpload(string $key = null): StorageUploadInterface
    {
        /** @var string $key */
        $key = $key ?? $this->get('uploadStorage');

        /** @var array $config */
        $config = $this->get(sprintf('storageList->%s', $key)) ?? throw new RuntimeException(
            sprintf('Not set config `storageList->%s`', $key)
        );
        /** @var class-string<StorageUploadInterface> $storageUploadClass */
        $storageUploadClass = key($config);
        return new $storageUploadClass(...current($config));
    }


}
