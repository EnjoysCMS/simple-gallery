<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\SimpleGallery\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="gallery_images")
 */
final class Image
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private int $id;

    public function getId(): int
    {
        return $this->id;
    }


    /**
     * @ORM\Column(type="string", unique=true)
     */
    private string $hash;

    public function getHash(): string
    {
        return $this->hash;
    }

    public function setHash(string $hash): void
    {
        $this->hash = $hash;
    }

    /**
     * @ORM\Column(type="string")
     */
    private string $filename;

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private ?string $description = null;


    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }


    public function getExtension(): string
    {
        return pathinfo($this->getFileName(), PATHINFO_EXTENSION);
    }



}