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
     * @ORM\Column(type="string")
     */
    private string $originalName;

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function setOriginalName(string $originalName): void
    {
        $this->originalName = $originalName;
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
    private string $filePath;

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): void
    {
        $this->filePath = $filePath;
    }

    /**
     * @ORM\Column(type="text", nullable=true)
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