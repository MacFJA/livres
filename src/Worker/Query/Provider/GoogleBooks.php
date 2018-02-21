<?php
/**
 * @author  MacFJA
 * @license MIT
 */
namespace App\Worker\Query\Provider;

use App\Worker\Query\QueryResult;
use Scriptotek\GoogleBooks\Volume;

class GoogleBooks extends BaseIsbn
{
    public function search(string $field, string $value): array
    {
        $client = new \Scriptotek\GoogleBooks\Volumes(new \Scriptotek\GoogleBooks\GoogleBooks());

        try {
            $isbn = $client->byIsbn($value);
            $ean = null;
            if (strlen($value) == 13) {
                $ean = $client->firstOrNull('ean:' . $value);
            }

            $volumes = array_filter([$isbn, $ean]);

            $result = [];
            foreach ($volumes as $volume) {
                $result[] = $this->parseVolume($volume, $field, $value);
            }

            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function parseVolume(Volume $volume, string $field, string $value) : QueryResult
    {
        $normalized = [
            'googlebooks_link' => $volume->selfLink,
            'title'            => $volume->title,
            'author'           => $volume->authors,
            'description'      => $volume->description,
            'pages'            => $volume->pageCunt,
            'genre'            => $volume->categories,
            'cover'            => $volume->getCover(),
            'language'         => $volume->language
        ];
        $publication = $volume->publishedDate;

        $parsed = false;
        foreach (['Y-m-d', 'd-m-Y', 'Y-m', 'm-Y', 'Y'] as $format) {
            $parsed = \DateTime::createFromFormat($format, $publication);
            if ($parsed !== false) {
                break;
            }
        }
        if ($parsed !== false) {
            $normalized['publicationDate'] = $parsed;
        }

        $identifiers = $volume->industryIdentifiers ?? [];
        $identifiers = array_reduce($identifiers, function (array $carry, \stdClass $item): array {
            $carry[$item->type] = $item->identifier;
            return $carry;
        }, []);
        $isbn = $identifiers['ISBN_13'] ?? $identifiers['ISBN_10'] ?? null;
        $normalized['isbn'] = $isbn;

        return QueryResult::createSimple($this, $field, $value, $volume, array_filter($normalized));
    }

    public function getCode(): string
    {
        return 'google-book-isbn';
    }

    public static function getLabel(): string
    {
        return 'Google Books';
    }
}
