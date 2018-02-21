<?php
/**
  * @author  MacFJA
  * @license MIT
  */
namespace App\Worker\Query\Provider;

use App\Worker\Query\QueryResult;

class GoodReads extends BaseAggregate
{
    /** @var string */
    protected $apiKey;

    /**
     * GoodReads constructor.
     *
     * @param string $apiKey API Key
     */
    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    private function getResponse(string $field, string $value): array
    {
        $goodReads = new \Nicat\GoodReads\GoodReads($this->apiKey);

        ob_start();
        switch (strtolower($field)) {
            case 'title':
                $response = $goodReads->searchBookByName($value);
                break;
            case 'author':
                $response = $goodReads->searchBookByAuthorName($value);
                break;
            case 'isbn':
            case 'ean':
                return $this->getByISBN($value);
            case self::FIELD_INTERNAL:
            case 'id':
            default:
                $response = $goodReads->searchBook($value);
        }
        ob_clean();

        return is_array($response)? $response : [$response];
    }

    /**
     * @param string $field
     * @param string $value
     * @return QueryResult[]
     */
    public function search(string $field, string $value): array
    {
        $response = $this->getResponse($field, $value);

        $results = [];

        foreach ($response as $item) {
            if (!isset($item->results)) {
                continue;
            }
            /** @var \SimpleXMLElement $work */
            foreach ($item->results->children() as $work) {
                $results[] = QueryResult::createSimple($this, $field, $value, $response, [
                    'title'           => (string)$work->best_book->title,
                    'cover'           => (string)$work->best_book->image_url,
                    'books_count'     => $work->books_count,
                    'publicationDate' => \DateTime::createFromFormat(
                        'Y/m/d',
                        (int)$work->original_publication_year . '/' .
                        ((string)($work->original_publication_month??1)) . '/' .
                        ((string)($work->original_publication_day??1))
                    ),
                    'average_rating'  => $work->average_rating,
                    'author'          => (string)$work->best_book->author->name,
                ]);
            }
        }
        
        return $results;
    }
    
    protected function getByISBN(string $isbn) : array
    {
        $goodReads = new \Nicat\GoodReads\GoodReads($this->apiKey);
        /** @var \SimpleXMLElement $response */
        $response = $goodReads->getBookByISBN($isbn);
        
        if (!isset($response->id) || $response->id == null) {
            return [];
        }
        
        $authors = [
            'author' => [],
            'illustrator' => [],
            'translator' => []
        ];
        $authorMapping = [
            'traducteur' => 'translator',
            'translator' => 'translator',
            'illustrator' => 'illustrator',
            'illustrateur' => 'illustrator'
        ];
        /** @var \SimpleXMLElement $author */
        foreach ($response->authors->children() as $author) {
            $type = $authorMapping[strtolower((string) $author->role)]??'author';
            $authors[$type][] = (string) $author->name;
        }
        $genres = [];
        /** @var \SimpleXMLElement $author */
        foreach ($response->popular_shelves->children() as $genre) {
            $genres[] = (string) $genre['name'];
        }
        
        return [
            QueryResult::createSimple($this, 'isbn', $isbn, $response, [
                'title' => (string) $response->title,
                'isbn' => (string) $response->isbn13,
                'amazon_id' => (string) $response->asin,
                'cover' => (string) $response->image_url,
                'other_publication_date' => \DateTime::createFromFormat(
                    'Y/m/d',
                    (int) $response->publication_year . '/' .
                    ((string) ($response->publication_month??1)) . '/' .
                    ((string) ($response->publication_day??1))
                ),
                'publisher' => (string) $response->publisher,
                'language' => (string) $response->language_code,
                'description' => (string) $response->description,
                'books_count' => (int) $response->work->books_count,
                'publicationDate' => \DateTime::createFromFormat(
                    'Y/m/d',
                    (int) $response->work->original_publication_year . '/' .
                    ((string) ($response->work->original_publication_month??1)) . '/' .
                    ((string) ($response->work->original_publication_day??1))
                ),
                'average_rating' => (float) $response->average_rating,
                'pages' => (int) $response->num_pages,
                'format' => (string) $response->format,
                'goodreads_link' => (string) $response->url,
                'author' => $authors['author'],
                'illustrator' => $authors['illustrator'],
                'translator' => $authors['translator'],
                'genre' => $genres,
                'goodreads_id' => (string) $response->id
            ])
        ];
    }

    public function getCode(): string
    {
        return 'good-reads';
    }

    public static function getLabel(): string
    {
        return 'GoodReads';
    }

    protected function getSearchableField() : array
    {
        return ['isbn', 'ean', 'title', 'author', self::FIELD_INTERNAL, 'id'];
    }
}
