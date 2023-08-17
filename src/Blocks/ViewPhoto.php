<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\SimpleGallery\Blocks;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use DoctrineExtensions\Query\Mysql\Rand;
use EnjoysCMS\Core\Block\AbstractBlock;
use EnjoysCMS\Core\Block\Annotation\Block;
use EnjoysCMS\Module\SimpleGallery\Config;
use EnjoysCMS\Module\SimpleGallery\Entities\Image;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[Block(
    name: 'Блок модуля галерея (simple-gallery)',
    options: [
        'template' => [
            'value' => '../modules/simple-gallery/template/block_sample.twig',
            'name' => 'Путь до template',
            'description' => 'Обязательно'
        ],
        'cntItems' => [
            'value' => 3,
            'name' => 'Кол-во записей (изображений)',
            'description' => 'Какое кол-во записей выводить в блок'
        ],
        'cacheTime' => [
            'value' => 0,
            'name' => 'Время кэширования в сек'
        ],
        'orderItems' => [
            'value' => 'desc',
            'name' => 'Сортировка',
            'form' => [
                'type' => 'select',
                'data' => [
                    'asc' => 'Первые старые',
                    'desc' => 'Первые новые',
                    'random' => 'В случайном порядке'
                ]
            ]
        ]
    ]
)]
class ViewPhoto extends AbstractBlock
{

    public function __construct(
        private readonly EntityManager $em,
        private readonly Environment $twig,
        private readonly Config $config,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function view(): string
    {
        $template = $this->getBlockOptions()->getValue('template');
        $this->em->getConfiguration()->addCustomStringFunction('RAND', Rand::class);

        /** @var Environment $twig */

        $cache = new FilesystemAdapter('blocks', directory: $_ENV['TEMP_DIR'] . '/cache');

        $cacheId = md5(
            sprintf(
                "%s%s",
                $this->getEntity()?->getAlias() ?? '',
                json_encode($this->getBlockOptions())
            )
        );

        $result = $cache->get($cacheId, function (ItemInterface $item): mixed {
            $item->expiresAfter((int)($this->getBlockOptions()->getValue('cacheTime') ?? 0));
            return $this->getImages($this->em->getRepository(Image::class));
        });


        return $this->twig->render(
            $template,
            [
                'images' => $result,
                'options' => $this->getBlockOptions(),
                'config' => $this->config
            ]
        );
    }

    private function getImages(EntityRepository $repository): mixed
    {
        $qb = $repository->createQueryBuilder('i');

        $qb = match ($this->getBlockOptions()->getValue('orderItems')) {
            default => $qb->orderBy('i.id', 'ASC'),
            'desc' => $qb->orderBy('i.id', 'DESC'),
            'random' => $qb->orderBy('RAND()'),
        };

        return $qb->getQuery()
            ->setMaxResults($this->getBlockOptions()->getValue('cntItems') ?? 10)
            ->getResult();
    }


}
