<?php
/**
 * @author  MacFJA
 * @license MIT
 */
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as Orm;
use Ivory\Serializer\Mapping\Annotation as Serializer;
use IntlDateFormatter;
use Nicebooks\Isbn\Isbn;
use Nicebooks\Isbn\IsbnTools;

/**
 * Class Book
 *
 * @Orm\Entity()
 * @SuppressWarnings(PHPMD.TooManyFields) -- Data holder
 */
class Book extends Base
{
    const ARRAY_INJECT_OTHERS = 100;
    const ARRAY_ADD_STATUS = 101;
    /**
     * @Orm\Id()
     * @Orm\GeneratedValue(strategy="NONE")
     * @Orm\Column(length=20, type="string", name="id")
     * @Serializer\Readable(false)
     * @var string
     */
    protected $computedId;
    /**
     * @Orm\Column(type="string", length=20)
     * @var string
     */
    protected $isbn;
    /**
     * @Orm\Column(type="string")
     * @var string
     */
    protected $title;
    /**
     * @Orm\Column(type="json_array")
     * @var string[]
     */
    protected $author = [];
    /**
     * @Orm\Column(type="smallint", nullable=true)
     * @var int|null
     */
    protected $pages;
    /**
     * @Orm\Column(type="string", nullable=true)
     * @var string|null
     */
    protected $serie;
    /**
     * @Orm\Column(type="string", nullable=true)
     * @var string
     */
    protected $sortTitle;
    /**
     * @Orm\Column(type="string", nullable=true)
     * @var string|null
     */
    protected $publisher;
    /**
     * @Orm\Column(type="string", nullable=true)
     * @var string|null
     */
    protected $owner;
    /**
     * @Orm\Column(type="json_array", nullable=true)
     * @var string[]
     */
    protected $illustrator;
    /**
     * @Orm\Column(type="json_array", nullable=true)
     * @var string[]
     */
    protected $translator;
    /**
     * @Orm\Column(type="json_array", nullable=true)
     * @var string[]
     */
    protected $genre;
    /**
     * @Orm\Column(type="date", nullable=true)
     * @var \DateTimeInterface
     */
    protected $publicationDate;
    /**
     * @Orm\Column(type="string", nullable=true)
     * @var string|null
     */
    protected $edition;
    /**
     * @Orm\Column(type="string", nullable=true)
     * @var string|null
     */
    protected $editor;
    /**
     * @Orm\Column(type="string", nullable=true)
     * @var string|null
     */
    protected $format;
    /**
     * @Orm\Column(type="string", nullable=true)
     * @var string|null
     */
    protected $dimension;
    /**
     * @Orm\Column(type="simple_array", nullable=true)
     * @var string[]
     */
    protected $keywords;
    /**
     * @Orm\Column(type="date")
     * @var \DateTimeInterface
     */
    protected $addedAt;
    /**
     * @Orm\Column(type="string", nullable=true)
     * @var string|null
     */
    protected $cover;
    /**
     * @Orm\Column(type="json_array", nullable=true)
     * @var mixed|null
     */
    protected $others = [];

    /**
     * @return string[]
     */
    public function getAuthor()
    {
        return $this->author;
    }
    /**
     * @Orm\OneToMany(targetEntity="App\Entity\Movement", mappedBy="book")
     * @Serializer\Accessor("getMovements")
     * @var Movement[]|ArrayCollection
     */
    protected $movements = [];
    /**
     * @Orm\Column(type="string", length=40, nullable=true)
     * @var string|null
     */
    protected $storage;

    public function __construct()
    {
        $this->movements = new ArrayCollection();
    }

    /**
     * @param string             $isbn
     * @param string             $title
     * @param \DateTimeInterface $addedAt
     * @param array              $author
     * @param string             $storage
     * @param string|null        $sortTitle
     * @param int|null           $pages
     * @param string|null        $serie
     * @param string|null        $publisher
     * @param string|null        $owner
     * @param array              $illustrator
     * @param array              $translator
     * @param array              $genre
     * @param \DateTimeInterface $publicationDate
     * @param string|null        $edition
     * @param string|null        $editor
     * @param string|null        $format
     * @param string|null        $dimension
     * @param array              $keywords
     * @param array|null         $others
     * @return Book
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter) -- Used by compact function
     */
    public static function create(
        string $isbn,
        string $title,
        \DateTimeInterface $addedAt,
        array $author = [],
        string $storage = null,
        string $sortTitle = null,
        int $pages = null,
        string $serie = null,
        string $publisher = null,
        string $owner = null,
        array $illustrator = [],
        array $translator = [],
        array $genre = [],
        \DateTimeInterface $publicationDate = null,
        string $edition = null,
        string $editor = null,
        string $format = null,
        string $dimension = null,
        array $keywords = [],
        array $others = null
    ) {
        $parametersName = array_map(
            function (\ReflectionParameter $item): string {
                return $item->getName();
            },
            (new \ReflectionMethod(static::class, __FUNCTION__))->getParameters()
        );

        return static::createFromArray(compact(
            $parametersName
        ));
    }

    /**
     * @param array $with
     * @return Book
     */
    public static function createFromArray(array $with)
    {
        /** @var Book $book */
        $book = parent::createFromArray($with);
        list(, $invalid) = self::filterCreationArray($with);
        $book->others += $invalid;

        $book->validateFieldData();

        return $book;
    }

    /**
     * @return void
     */
    public function validateFieldData()
    {
        $this->convertStringToDate();
        $this->computeId();
    }

    /**
     * @return void
     */
    protected function computeId()
    {
        $this->computedId = $this->isbn;
        if (!empty($this->sortTitle)) {
            $this->computedId .= '-' . $this->sortTitle;
        }
    }

    /**
     * @return string[]
     */
    public static function getSearchableFields(): array
    {
        $propertiesName = array_map(
            function (\ReflectionProperty $item): string {
                return $item->getName();
            },
            (new \ReflectionClass(static::class))->getProperties()
        );

        return array_filter($propertiesName, function (string $field): bool {
            return !in_array($field, ['computedId', 'others', 'movements', 'cover'], true);
        });
    }

    public static function generateFakeIsbn(): string
    {
        return 'G' . str_pad(substr((string) (random_int(0, time()) * time()), 0, 13), 13, '0', STR_PAD_LEFT);
    }

    public function inMovement(): bool
    {
        if (count($this->movements) === 0) {
            return false;
        }

        $lends = array_filter($this->getMovements(), function (Movement $item): bool {
            return $item->getType() == Movement::TYPE_LEND && $item->isCurrent();
        });

        return count($lends) > 0;
    }

    /**
     * @param bool $onlyCurrent
     * @return false|Movement
     */
    public function getLastMovement(bool $onlyCurrent = true)
    {
        $movements = $onlyCurrent ? $this->getCurrentMovements() : $this->getMovements();

        usort($movements, function (Movement $itemA, Movement $itemB): int {
            return $itemA->getStartAt()->getTimestamp() - $itemB->getStartAt()->getTimestamp();
        });

        return reset($movements);
    }

    /**
     * @return Movement[]|null
     */
    public function getCurrentMovements()
    {
        if (count($this->movements) == 0) {
            return null;
        }

        /** @var Movement[] $currents */
        $currents = array_filter($this->getMovements(), function (Movement $item): bool {
            return $item->isCurrent();
        });

        return $currents;
    }

    /**
     * @return string
     */
    public function getIsbn(bool $withSeparator = false): string
    {
        $isbnTool = new IsbnTools();
        if (!$isbnTool->isValidIsbn($this->isbn)) {
            return $this->isbn;
        }

        return $withSeparator ? Isbn::of($this->isbn)->format() : $this->isbn;
    }

    public function toArray(array $excludes = [], array $options = []): array
    {
        $injectOthers = $options[self::ARRAY_INJECT_OTHERS] ?? true;
        $excludes[] = 'computedId';
        $excludes[] = 'movements';
        if ($injectOthers) {
            $excludes[] = 'others';
        }

        $array = parent::toArray($excludes, $options);

        if ($injectOthers) {
            $array += $this->others;
        }

        if ($options[self::ARRAY_ADD_STATUS] ?? false) {
            $array['status'] = 'At Home';
            if ($this->inMovement()) {
                /** @var Movement $lastMovement */
                $lastMovement = $this->getLastMovement();
                $array['status'] = ($lastMovement->getType() == Movement::TYPE_LEND ? 'Lend to: ' : 'Borrow from: ')
                    . $lastMovement->getPerson();
            }
        }

        $array['ean'] = $this->getEAN();

        if ($options[self::ARRAY_FLATTEN_OPTION] ?? false) {
            $array = $this->flattenArray($array);
        }

        return ($options[static::ARRAY_KEEP_EMPTY]??false) ? $array : array_filter($array);
    }

    public function getEAN(bool $withSeparator = false): string
    {
        return static::convertISBNToEAN($this->isbn, $withSeparator);
    }

    public static function convertISBNToEAN(string $isbn, bool $withSeparator = false): string
    {
        $isbnTool = new IsbnTools();
        if (!$isbnTool->isValidIsbn($isbn)) {
            return '';
        }

        $isbn = Isbn::of($isbn);

        if ($isbn->is10()) {
            $isbn = $isbn->to13();
        }

        return $withSeparator ? $isbn->format() : (string)$isbn;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getAddedAt()
    {
        return $this->addedAt;
    }

    /**
     * @return string|null
     */
    public function getStorage()
    {
        return $this->storage;
    }

    public function getTitle(): string
    {
        return $this->title ?? 'No Title';
    }

    public function getCover(): string
    {
        return $this->cover ?? 'placeholder.png';
    }

    /**
     * @return Movement[]
     */
    public function getMovements()
    {
        return ($this->movements instanceof ArrayCollection || $this->movements instanceof Collection)
            ? $this->movements->toArray()
            : $this->movements;
    }

    /**
     * @return null|string
     */
    public function getSerie()
    {
        return $this->serie;
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
        $json = $this->toArray([], [
            self::ARRAY_INJECT_OTHERS  => false,
            self::ARRAY_KEEP_EMPTY     => true,
            self::ARRAY_DATE_FORMAT    => 'r',
            self::ARRAY_FLATTEN_OPTION => false
        ]);
        $json['movements'] = $this->getMovements();

        return $json;
    }

    public function getId() : string
    {
        return $this->computedId;
    }

    /**
     * {@inheritDoc}
     */
    public function serialize()
    {
        $data = $this->toArray();
        $data['movements'] = $this->getMovements();

        return serialize($data);
    }
}
