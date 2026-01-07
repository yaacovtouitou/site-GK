<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $pseudo = null;

    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    // Infos Parent
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $zipCode = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $city = null;

    // Relation Enfants
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: Child::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $children;

    // Champs Gamification
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private ?int $totalPoints = 0;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $currentRank = null;

    #[ORM\Column(type: 'integer', options: ['default' => 100])]
    private ?int $energy = 100;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private ?int $loginStreak = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastLoginDate = null;

    #[ORM\ManyToMany(targetEntity: Badge::class)]
    private Collection $badges;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Completion::class, orphanRemoval: true)]
    private Collection $completions;

    public function __construct()
    {
        $this->completions = new ArrayCollection();
        $this->children = new ArrayCollection();
        $this->badges = new ArrayCollection();
        $this->currentRank = 'Soldat';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(string $pseudo): static
    {
        $this->pseudo = $pseudo;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // $this->plainPassword = null;
    }

    // Getters/Setters Parent Info
    public function getLastName(): ?string { return $this->lastName; }
    public function setLastName(?string $lastName): static { $this->lastName = $lastName; return $this; }

    public function getFirstName(): ?string { return $this->firstName; }
    public function setFirstName(?string $firstName): static { $this->firstName = $firstName; return $this; }

    public function getAddress(): ?string { return $this->address; }
    public function setAddress(?string $address): static { $this->address = $address; return $this; }

    public function getZipCode(): ?string { return $this->zipCode; }
    public function setZipCode(?string $zipCode): static { $this->zipCode = $zipCode; return $this; }

    public function getCity(): ?string { return $this->city; }
    public function setCity(?string $city): static { $this->city = $city; return $this; }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(Child $child): static
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setParent($this);
        }
        return $this;
    }

    public function removeChild(Child $child): static
    {
        if ($this->children->removeElement($child)) {
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }
        return $this;
    }

    // Gamification Getters/Setters
    public function getAvatar(): ?string { return $this->avatar; }
    public function setAvatar(?string $avatar): static { $this->avatar = $avatar; return $this; }
    public function getTotalPoints(): ?int { return $this->totalPoints; }
    public function setTotalPoints(int $totalPoints): static { $this->totalPoints = $totalPoints; return $this; }
    public function getCurrentRank(): ?string { return $this->currentRank; }
    public function setCurrentRank(?string $currentRank): static { $this->currentRank = $currentRank; return $this; }
    public function getEnergy(): ?int { return $this->energy; }
    public function setEnergy(int $energy): static { $this->energy = $energy; return $this; }

    public function getLoginStreak(): ?int { return $this->loginStreak; }
    public function setLoginStreak(int $loginStreak): static { $this->loginStreak = $loginStreak; return $this; }

    public function getLastLoginDate(): ?\DateTimeInterface { return $this->lastLoginDate; }
    public function setLastLoginDate(?\DateTimeInterface $lastLoginDate): static { $this->lastLoginDate = $lastLoginDate; return $this; }

    /**
     * @return Collection<int, Badge>
     */
    public function getBadges(): Collection
    {
        return $this->badges;
    }

    public function addBadge(Badge $badge): static
    {
        if (!$this->badges->contains($badge)) {
            $this->badges->add($badge);
        }
        return $this;
    }

    public function removeBadge(Badge $badge): static
    {
        $this->badges->removeElement($badge);
        return $this;
    }

    public function getCompletions(): Collection { return $this->completions; }
}
