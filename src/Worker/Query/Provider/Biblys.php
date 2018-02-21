<?php
/**
  * @author  MacFJA
  * @license MIT
  */
namespace App\Worker\Query\Provider;

use App\Worker\Query\QueryResult;
use Biblys\Data\Client;
use Biblys\Data\Contributor;

class Biblys extends BaseIsbn
{
    public function search(string $field, string $value): array
    {
        $client = new Client();
        $book = $client->getBook($value);
        
        if (false === $book) {
            return [];
        }

        $authors = array_map(function (Contributor $author): string {
            return $author->getName();
        }, $book->getAuthors());
        
        $normalized = [
            'isbn' => $book->getEan(),
            'author' => $authors,
            'title' => $book->getTitle(),
            'publisher' => $book->getPublisher()->getName()
        ];
        
        return [QueryResult::createSimple($this, $field, $value, $book, $normalized)];
    }

    public function getCode(): string
    {
        return 'biblys';
    }

    public static function getLabel(): string
    {
        return 'Biblys Data';
    }
}
