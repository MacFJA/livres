<?php
/**
  * @author  MacFJA
  * @license MIT
  */
namespace App\Entity;

use Doctrine\ORM\Mapping as Orm;
use Ivory\Serializer\Mapping\Annotation as Serializer;

/**
 * Class Movement
 *
 * @Orm\Entity()
 * @package App\Entity
 */
class Movement extends Base
{
    const TYPE_LEND = 0;
    const TYPE_BORROW = 1;
    /**
     * @Orm\Id()
     * @Orm\Column(type="integer", nullable=false, unique=true)
     * @Orm\GeneratedValue()
     * @Serializer\Readable(false)
     * @var int
     */
    protected $id;
    /**
     * @Orm\Column(type="date")
     * @var \DateTimeInterface
     */
    protected $startAt;
    /**
     * @Orm\Column(type="date", nullable=true)
     * @var \DateTimeInterface
     */
    protected $endAt;
    /**
     * @Orm\Column(type="string", length=40)
     * @var string
     */
    protected $person;
    /**
     * @Orm\Column(type="smallint")
     * @var int
     */
    protected $type;
    /**
     * @Orm\ManyToOne(targetEntity="App\Entity\Book", inversedBy="movements")
     * @Orm\JoinColumn(name="book_isbn", referencedColumnName="id")
     * @Serializer\Readable(false)
     * @var Book
     */
    protected $book;

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    public function isCurrent(): bool
    {
        if ($this->endAt === null) {
            return true;
        }
        return false;
    }

    /**
     * @return string
     */
    public function getPerson()
    {
        return $this->person;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getStartAt()
    {
        return $this->startAt;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getEndAt()
    {
        return $this->endAt;
    }

    public function isLend(): bool
    {
        return $this->type === static::TYPE_LEND;
    }

    /**
     * @param \DateTimeInterface $startAt
     * @return void
     */
    public function setStartAt($startAt)
    {
        $this->startAt = $startAt;
    }

    /**
     * @param \DateTimeInterface $endAt
     * @return void
     */
    public function setEndAt($endAt)
    {
        $this->endAt = $endAt;
    }

    /**
     * @param string $person
     * @return void
     */
    public function setPerson($person)
    {
        $this->person = $person;
    }

    /**
     * @param int $type
     * @return void
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Book
     */
    public function getBook()
    {
        return $this->book;
    }


    /**
     * Specify data which should be serialized to JSON
     *
     * @link  http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *        which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return $this->toArray(['book'], [self::ARRAY_DATE_FORMAT=>'r', self::ARRAY_KEEP_EMPTY => true]);
    }

    /**
     * {@inheritDoc}
     */
    public function serialize()
    {
        return serialize($this->jsonSerialize());
    }
}
