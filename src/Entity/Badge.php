<?php

namespace App\Entity;

use App\Repository\BadgeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BadgeRepository::class)]
class Badge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $imageColor = null;

    #[ORM\Column(length: 255)]
    private ?string $imageGray = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null; // Permanent or Seasonal/Hassidique

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getImageColor(): ?string
    {
        return $this->imageColor;
    }

    public function setImageColor(string $imageColor): static
    {
        $this->imageColor = $imageColor;

        return $this;
    }

    public function getImageGray(): ?string
    {
        return $this->imageGray;
    }

    public function setImageGray(string $imageGray): static
    {
        $this->imageGray = $imageGray;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }
}
