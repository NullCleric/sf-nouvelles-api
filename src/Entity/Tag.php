<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Story;

#[ORM\Entity]
#[ORM\Table(name: 'tag')]
#[ORM\UniqueConstraint(name: 'uniq_tag_slug', fields: ['slug'])]
class Tag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $title;

    #[ORM\Column(length: 100)]
    private string $slug;

    #[ORM\ManyToMany(targetEntity: Story::class, mappedBy: 'tags')]
    private Collection $stories;

    public function __construct()
    {
        $this->stories = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function getSlug(): string { return $this->slug; }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }
}
