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

use ApiPlatform\Core\Annotation\ApiResource;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use function sprintf;

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
 *         "get"={"security"="is_granted('ROLE_CAN_VIEW')"},
 *         "post"={"security"="is_granted('ROLE_CAN_EDIT')"}
 *     },
 *     itemOperations={
 *         "get"={"security"="is_granted('ROLE_CAN_VIEW')"},
 *         "patch"={"security"="is_granted('ROLE_CAN_EDIT')"},
 *         "put"={"security"="is_granted('ROLE_CAN_EDIT')"}
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\MovementRepository")
 */
class Movement
{
    public const TYPE_LEND = 0;

    public const TYPE_BORROW = 1;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer", name="id")
     *
     * @var int
     */
    private $movementId;

    /**
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    private $type = self::TYPE_LEND;

    /**
     * @ORM\Column(type="datetime")
     *
     * @var null|DateTimeInterface
     */
    private $startAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @var null|DateTimeInterface
     */
    private $endAt;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @var string
     */
    private $person;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Book", inversedBy="movements")
     * @ORM\JoinColumn(nullable=false)
     *
     * @var null|Book
     */
    private $book;

    public function __toString()
    {
        return sprintf(
            '%s: %s %s %s',
            $this->getPerson() ?? 'nobody',
            $this->getFormattedStartAt(),
            "\u{2192}",
            $this->getFormattedEndAt()
        );
    }

    public function getMovementId(): ?int
    {
        return $this->movementId;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getStartAt(): ?DateTimeInterface
    {
        return $this->startAt;
    }

    public function setStartAt(DateTimeInterface $startAt): self
    {
        $this->startAt = $startAt;

        return $this;
    }

    public function getEndAt(): ?DateTimeInterface
    {
        return $this->endAt;
    }

    public function setEndAt(?DateTimeInterface $endAt): self
    {
        $this->endAt = $endAt;

        return $this;
    }

    public function getPerson(): ?string
    {
        return $this->person;
    }

    public function setPerson(string $person): self
    {
        $this->person = $person;

        return $this;
    }

    public function getBook(): ?Book
    {
        return $this->book;
    }

    public function setBook(?Book $book): self
    {
        $this->book = $book;

        return $this;
    }

    public function isEnded(): bool
    {
        return $this->endAt instanceof DateTimeInterface;
    }

    public function endNow(): self
    {
        $this->endAt = new DateTime();

        return $this;
    }

    private function getFormattedStartAt(): string
    {
        if ($this->startAt instanceof DateTimeInterface) {
            return $this->startAt->format('Y-m-d');
        }

        return '?';
    }

    private function getFormattedEndAt(): string
    {
        if ($this->endAt instanceof DateTimeInterface) {
            return $this->endAt->format('Y-m-d');
        }

        return '?';
    }
}
