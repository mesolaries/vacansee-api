<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Repository\VacancyRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource(collectionOperations={"get"}, itemOperations={"get"}, attributes={"order"={"createdAt": "DESC"}})
 * @ApiFilter(SearchFilter::class, properties={"title": "ipartial", "company": "ipartial", "salary": "iword_start",
 *                                 "url": "ipartial", "category.id": "exact", "category.slug": "exact"})
 * @ApiFilter(DateFilter::class, properties={"createdAt"})
 * @ApiFilter(OrderFilter::class, properties={"createdAt", "title"})
 * @ApiFilter(ExistsFilter::class, properties={"salary"})
 * @ApiFilter(RangeFilter::class, properties={"id"})
 * @ORM\Entity(repositoryClass=VacancyRepository::class)
 * @ORM\Table(name="vacancies")
 */
class Vacancy
{
    private const CATEGORIES = [
        'it' => 'IT',
        'design' => 'Dizayn',
        'service' => 'Xidmət',
        'marketing' => 'Marketinq',
        'administration' => 'İnzibati',
        'sales' => 'Satış',
        'finance' => 'Maliyyə',
        'medical' => 'Səhiyyə',
        'legal' => 'Hüquq',
        'education' => 'Təhsil',
        'jobsearch' => 'Jobsearch',
        'other' => 'Digər',
    ];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $company;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $salary;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $url;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $descriptionHtml;

    /**
     * @ORM\ManyToOne(targetEntity=Category::class, inversedBy="vacancies")
     * @ORM\JoinColumn(nullable=false)
     */
    private $category;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(string $company): self
    {
        $this->company = $company;

        return $this;
    }

    public function getSalary(): ?string
    {
        return $this->salary;
    }

    public function setSalary(?string $salary): self
    {
        $this->salary = $salary;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getDescriptionHtml(): ?string
    {
        return $this->descriptionHtml;
    }

    public function setDescriptionHtml(string $descriptionHtml): self
    {
        $this->descriptionHtml = $descriptionHtml;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public static function getCategories()
    {
        return self::CATEGORIES;
    }
}
