<?php
/**
 * @author  MacFJA
 * @license MIT
 */
namespace App\Worker\Import;

use App\Entity\Book;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;

class Csv
{
    /**
     * @var array{
     *     isbn:string,
     *     serie:string,
     *     sortTitle:string,
     *     publisher:string,
     *     author:string,
     *     title:string,
     *     owner:string,
     *     illustrator:string,
     *     translator:string,
     *     genre:string,
     *     publicationDate:string,
     *     edition:string,
     *     editor:string,
     *     count:string,
     *     format:string,
     *     pages:string,
     *     dimension:string,
     *     list_names:string,
     *     keywords:string
     * }
     */
    protected static $headerMapping = [
        'isbn'            => 'ISBN',
        'serie'           => 'Série',
        'sortTitle'       => 'Titre de tri',
        'publisher'       => 'Maison d\'édition',
        'author'          => 'Auteur',
        'title'           => 'Titre',
        'owner'           => 'A qui',
        'illustrator'     => 'Illustrateur',
        'translator'      => 'Traducteur',
        'genre'           => 'Genre',
        'publicationDate' => 'Date de parution',
        'edition'         => 'Édition',
        'editor'          => 'Éditeur',
        'count'           => 'Compteur',
        'format'          => 'Format',
        'pages'           => 'Pages',
        'dimension'       => 'Dimensions',
        'list_names'      => 'Noms des listes',
        'keywords'        => 'Mots-clés',
    ];

    /**
     * @return array
     */
    public static function getHeaderMapping()
    {
        return self::$headerMapping;
    }

    /** @var  EntityManagerInterface */
    protected $entityManager;

    /**
     * Csv constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Import a CSV file into the database
     *
     * @param string $dataPath
     * @return void
     * @throws \League\Csv\Exception
     * @throws \TypeError
     */
    public function importDb(string $dataPath)
    {
        /** @var Reader $csv */
        $csv = Reader::createFromPath($dataPath);
        $csv->setDelimiter(',')->setEnclosure('"');
        $csv->setHeaderOffset(0);

        $csv->count();

        foreach ($csv->getRecords($csv->getHeader()) as $record) {
            $json = [];
            foreach ($this->headerMapping as $result => $source) {
                if (array_key_exists($source, $record) && !empty($record[$source])) {
                    $json[$result] = $record[$source];
                }
            }
            $json['addedAt'] = new \DateTimeImmutable();
            if (empty($json['isbn'])) {
                $json['isbn'] = Book::generateFakeIsbn();
            }
            $book = Book::createFromArray($json);
            echo 'Adding isbn: ' . $book->getIsbn() . PHP_EOL;
            $this->entityManager->persist($book);
        }
        $this->entityManager->flush();
    }
}
