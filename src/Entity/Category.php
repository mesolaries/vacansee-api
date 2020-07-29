<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

/**
 * @ORM\Entity(repositoryClass=CategoryRepository::class)
 * @ORM\Table(name="categories")
 * @ORM\HasLifecycleCallbacks()
 */
class Category
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isDefault;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $url;

    /**
     * @ORM\ManyToMany(targetEntity=Provider::class, inversedBy="categories")
     */
    private $provider;

    /**
     * @ORM\OneToMany(targetEntity=Vacancy::class, mappedBy="category")
     */
    private $vacancies;

    public function __construct()
    {
        $this->provider = new ArrayCollection();
        $this->vacancies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getIsDefault(): ?bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): self
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return Collection|Provider[]
     */
    public function getProvider(): Collection
    {
        return $this->provider;
    }

    public function addProvider(Provider $provider): self
    {
        if (!$this->provider->contains($provider)) {
            $this->provider[] = $provider;
        }

        return $this;
    }

    public function removeProvider(Provider $provider): self
    {
        if ($this->provider->contains($provider)) {
            $this->provider->removeElement($provider);
        }

        return $this;
    }

    /**
     * @return Collection|Vacancy[]
     */
    public function getVacancies(): Collection
    {
        return $this->vacancies;
    }

    public function addVacancy(Vacancy $vacancy): self
    {
        if (!$this->vacancies->contains($vacancy)) {
            $this->vacancies[] = $vacancy;
            $vacancy->setCategory($this);
        }

        return $this;
    }

    public function removeVacancy(Vacancy $vacancy): self
    {
        if ($this->vacancies->contains($vacancy)) {
            $this->vacancies->removeElement($vacancy);
            // set the owning side to null (unless already changed)
            if ($vacancy->getCategory() === $this) {
                $vacancy->setCategory(null);
            }
        }

        return $this;
    }

    /**
     * @ORM\PrePersist()
     * @param LifecycleEventArgs $event
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function prePersist(LifecycleEventArgs $event)
    {
        $entityManager = $event->getEntityManager();
        $repository = $entityManager->getRepository(get_class($this));

        $categories = $repository->findBy(['isDefault' => true]);
        foreach ($categories as $category) {
            $category->setIsDefault(false);
            $entityManager->persist($category);
        }
        $entityManager->flush();
    }

    /**
     * @ORM\PreUpdate()
     * @param LifecycleEventArgs $event
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function preUpdate(LifecycleEventArgs $event)
    {
        $this->prePersist($event);
    }
}
