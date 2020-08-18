<?php

declare(strict_types=1);

/*
 * Copyright MacFJA
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
 * Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
 * OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use function array_filter;
use function array_values;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * @ApiResource(
 *     attributes={
 *          "pagination_enabled"=true,
 *          "pagination_items_per_page"=10,
 *          "maximum_items_per_page"=30,
 *          "pagination_client_items_per_page"=true,
 *          "pagination_client_enabled"=false
 *     },
 *     collectionOperations={
 *         "get"={"security"="is_granted('ROLE_CAN_VIEW')"}
 *     },
 *     itemOperations={
 *         "get"={"security"="is_granted('ROLE_CAN_VIEW')"},
 *         "delete"={"security"="is_granted('ROLE_CAN_DELETE')"}
 *     }
 * )
 * @ApiFilter(\ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter::class, properties={"title":"partial","owner","series","keywords":"partial"})
 * @ApiFilter(\App\ApiPlatform\SerializeArrayFilter::class, properties={"genres","authors","illustrators"})
 * @ApiFilter(\App\ApiPlatform\RedisearchFilter::class, properties={"query"})
 * @ApiFilter(\App\ApiPlatform\InMovementFilter::class, properties={"in_movement"})
 * @ApiFilter(\ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\RangeFilter::class, properties={"pages"})
 * @ApiFilter(\App\ApiPlatform\AppOrderFilter::class)
 *
 * @ORM\Entity(repositoryClass="App\Repository\BookRepository")
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class Book implements JsonSerializable
{
    public const DATA_TYPE_ARRAY = 'array';

    public const DATA_TYPE_BARCODE = 'barcode';

    public const DATA_TYPE_DATE = 'date';

    public const DATA_TYPE_NUMBER = 'number';

    public const DATA_TYPE_TEXT = 'text';

    public const DATA_TYPE_IMAGE = 'image';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer", name="id")
     *
     * @var int
     */
    private $bookId = 0;

    /**
     * @ORM\Column(type="string", length=20)
     *
     * @var string
     */
    private $isbn = '';

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @var string
     */
    private $title = 'no-title';

    /**
     * @ORM\Column(type="array")
     *
     * @var array<string>
     */
    private $authors = [];

    /**
     * @ORM\Column(type="integer", nullable=true)
     *
     * @var null|int
     */
    private $pages;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @var null|string
     */
    private $series;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @var string
     */
    private $sortTitle = '';

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @var string
     */
    private $owner = 'nobody';

    /**
     * @ORM\Column(type="array")
     *
     * @var array<string>
     */
    private $illustrators = [];

    /**
     * @ORM\Column(type="array")
     *
     * @var string[]
     */
    private $genres = [];

    /**
     * @ORM\Column(type="date", nullable=true)
     *
     * @var null|DateTimeInterface
     */
    private $publicationDate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @var null|string
     */
    private $format;

    /** @ORM\Column(type="string", length=20, nullable=true)
     * @var null|string
     */
    private $dimension;

    /**
     * @ORM\Column(type="simple_array", nullable=true)
     *
     * @var null|array<string>
     */
    private $keywords = [];

    /**
     * @ORM\Column(type="datetime")
     *
     * @var DateTimeInterface
     */
    private $addedAt;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @var null|string
     */
    private $cover;

    /**
     * @ORM\Column(type="json")
     *
     * @var array<array<mixed>>
     */
    private $additional = [];

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @var null|string
     */
    private $storage;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Movement", mappedBy="book", orphanRemoval=true)
     * @ApiSubresource()
     *
     * @var Collection<int,Movement>
     */
    private $movements;

    public function __construct()
    {
        $this->movements = new ArrayCollection();
        $this->addedAt = new DateTime();
    }

    public function getSortTitle(): string
    {
        return $this->sortTitle;
    }

    public function getBookId(): ?int
    {
        return $this->bookId;
    }

    public function getAddedAt(): DateTimeInterface
    {
        return $this->addedAt;
    }

    public function getIsbn(): string
    {
        return $this->isbn;
    }

    public function setIsbn(string $isbn): self
    {
        $this->isbn = $isbn;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string[]
     *
     * @psalm-return array<array-key, string>
     */
    public function getAuthors(): array
    {
        return $this->authors;
    }

    /**
     * @param array<string> $authors
     *
     * @return $this
     */
    public function setAuthors(array $authors): self
    {
        $this->authors = $authors;

        return $this;
    }

    public function getPages(): ?int
    {
        return $this->pages;
    }

    public function setPages(int $pages): self
    {
        $this->pages = $pages;

        return $this;
    }

    public function getSeries(): ?string
    {
        return $this->series;
    }

    public function setSortTitle(string $sortTitle): self
    {
        $this->sortTitle = $sortTitle;

        return $this;
    }

    public function getOwner(): string
    {
        return $this->owner;
    }

    public function setOwner(string $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return string[]
     *
     * @psalm-return array<array-key, string>
     */
    public function getIllustrators(): array
    {
        return $this->illustrators;
    }

    /**
     * @param array<string> $illustrators
     *
     * @return $this
     */
    public function setIllustrators(array $illustrators): self
    {
        $this->illustrators = $illustrators;

        return $this;
    }

    /**
     * @return string[]
     *
     * @psalm-return array<array-key, string>
     */
    public function getGenres(): array
    {
        return $this->genres;
    }

    /**
     * @param array<string> $genres
     *
     * @return $this
     */
    public function setGenres(array $genres): self
    {
        $this->genres = $genres;

        return $this;
    }

    public function getPublicationDate(): ?DateTimeInterface
    {
        return $this->publicationDate;
    }

    public function setPublicationDate(DateTimeInterface $publicationDate): self
    {
        $this->publicationDate = $publicationDate;

        return $this;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function setFormat(?string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function getDimension(): ?string
    {
        return $this->dimension;
    }

    public function setDimension(?string $dimension): self
    {
        $this->dimension = $dimension;

        return $this;
    }

    /**
     * @return array<string>
     */
    public function getKeywords(): array
    {
        return $this->keywords ?? [];
    }

    /**
     * @param array<string> $keywords
     *
     * @return $this
     */
    public function setKeywords(array $keywords): self
    {
        $this->keywords = $keywords;

        return $this;
    }

    public function setAddedAt(DateTimeInterface $addedAt): self
    {
        $this->addedAt = $addedAt;

        return $this;
    }

    public function getCover(): ?string
    {
        return $this->cover;
    }

    public function setCover(?string $cover): self
    {
        $this->cover = $cover;

        return $this;
    }

    /**
     * @return array[]
     *
     * @psalm-return array<array-key, array>
     */
    public function getAdditional(): array
    {
        return $this->additional;
    }

    /**
     * @param array<array<mixed>> $additional
     *
     * @return $this
     */
    public function setAdditional(array $additional): self
    {
        $this->additional = $additional;

        return $this;
    }

    public function getStorage(): ?string
    {
        return $this->storage;
    }

    public function setStorage(?string $storage): self
    {
        $this->storage = $storage;

        return $this;
    }

    /**
     * @return Collection<int,Movement>
     */
    public function getMovements(): Collection
    {
        return $this->movements;
    }

    public function inMovement(): bool
    {
        $inMovement = $this->movements->filter(function (Movement $movement) {
            return !$movement->isEnded();
        });

        return $inMovement->count() > 0;
    }

    /**
     * @return array<array<mixed>>
     *
     * @phan-return array<array{label: string, value:mixed, type:string}>
     *
     * @psalm-return list<array{label: array-key, value: mixed, type: string}>
     */
    public function getDetails(): array
    {
        $details = [
            ['key' => 'title', 'label' => 'Title', 'value' => $this->getTitle(), 'type' => self::DATA_TYPE_TEXT],
            ['key' => 'sortTitle', 'label' => 'Sort title', 'value' => $this->getSortTitle(), 'type' => self::DATA_TYPE_TEXT],
            ['key' => 'series', 'label' => 'Series', 'value' => $this->getSeries(), 'type' => self::DATA_TYPE_TEXT],
            ['key' => 'authors', 'label' => 'Authors', 'value' => $this->getAuthors(), 'type' => self::DATA_TYPE_ARRAY],
            ['key' => 'illustrators', 'label' => 'Illustrators', 'value' => $this->getIllustrators(), 'type' => self::DATA_TYPE_ARRAY],
            ['key' => 'owner', 'label' => 'Owner', 'value' => $this->getOwner(), 'type' => self::DATA_TYPE_TEXT],
            ['key' => 'dimension', 'label' => 'Dimension', 'value' => $this->getDimension(), 'type' => self::DATA_TYPE_TEXT],
            ['key' => 'format', 'label' => 'Format', 'value' => $this->getFormat(), 'type' => self::DATA_TYPE_TEXT],
            ['key' => 'isbn', 'label' => 'Isbn', 'value' => $this->getIsbn(), 'type' => self::DATA_TYPE_BARCODE],
            ['key' => 'genres', 'label' => 'Genres', 'value' => $this->getGenres(), 'type' => self::DATA_TYPE_ARRAY],
            ['key' => 'keywords', 'label' => 'Keywords', 'value' => $this->getKeywords(), 'type' => self::DATA_TYPE_ARRAY],
            ['key' => 'storage', 'label' => 'Storage', 'value' => $this->getStorage(), 'type' => self::DATA_TYPE_TEXT],
            ['key' => 'pages', 'label' => 'Pages', 'value' => $this->getPages(), 'type' => self::DATA_TYPE_NUMBER],
            ['key' => 'publicationDate', 'label' => 'Publication date', 'value' => $this->getPublicationDate(), 'type' => self::DATA_TYPE_DATE],
        ];
        foreach ($this->additional as $name => $value) {
            $details[] = ['key' => $name, 'label' => $name, 'value' => $value, 'type' => self::DATA_TYPE_ARRAY];
        }

        return array_values(array_filter($details, function (array $row) {
            return !empty($row['value']);
        }));
    }

    /**
     * @return array<mixed>
     */
    public function jsonSerialize()
    {
        return [
            'title' => $this->getTitle(),
            'series' => $this->getSeries(),
            'authors' => $this->getAuthors(),
            'illustrators' => $this->getIllustrators(),
            'owner' => $this->getOwner(),
            'dimension' => $this->getDimension(),
            'format' => $this->getFormat(),
            'isbn' => $this->getIsbn(),
            'genres' => $this->getGenres(),
            'keywords' => $this->getKeywords(),
            'storage' => $this->getStorage(),
            'pages' => $this->getPages(),
            'publicationDate' => $this->getPublicationDate(),
            'additional' => $this->getAdditional(),
        ];
    }
}
