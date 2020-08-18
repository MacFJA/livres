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

namespace App\Doctrine;

use App\Entity\Book;
use function count;
use const DIRECTORY_SEPARATOR;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Exception;
use GuzzleHttp\Client;
use function is_string;
use function parse_url;
use function pathinfo;
use const PATHINFO_EXTENSION;
use const PHP_URL_PATH;
use function strpos;
use Symfony\Component\Finder\Finder;

class BookInjectionListener
{
    /** @var string */
    protected $coverDir;

    /** @var bool */
    protected $disableInjection = false;

    public function __construct(string $coverDir)
    {
        $this->coverDir = $coverDir;
    }

    /**
     * @param bool $disableInjection
     */
    public function setDisableInjection($disableInjection): void
    {
        $this->disableInjection = $disableInjection;
    }

    /**
     * @return void
     */
    public function postLoad(Book $book, LifecycleEventArgs $lifecycleEvent)
    {
        $this->handleCover($book, $lifecycleEvent->getEntityManager()->getClassMetadata(Book::class));
    }

    /**
     * @suppress PhanUnusedVariableCaughtException
     */
    public function handleCover(Book $book, ClassMetadata $metadata): void
    {
        if ($this->disableInjection) {
            return;
        }
        $cover = $book->getCover();

        if (empty($cover)) {
            $metadata->setFieldValue($book, 'cover', 'placeholder.jpg');

            return;
        }

        if (!(0 === strpos($cover, 'http'))) {
            return;
        }

        $filename = 'book.cover.'.$book->getBookId().'.'.$this->getExtension($cover);
        if (!$this->find($filename)) {
            $client = new Client();

            try {
                $client->get($cover, ['sink' => $this->coverDir.DIRECTORY_SEPARATOR.$filename]);
            } catch (Exception $exception) {
                return;
            }
        }

        $metadata->setFieldValue($book, 'cover', $filename);
    }

    private function getExtension(string $url): string
    {
        $urlPath = parse_url($url, PHP_URL_PATH);
        if (!is_string($urlPath)) {
            return 'unk';
        }

        return pathinfo($urlPath, PATHINFO_EXTENSION) ?: 'unk';
    }

    private function find(string $filename): bool
    {
        $finder = (new Finder())
            ->name($filename)
            ->in($this->coverDir)
            ->files();

        if (0 === count($finder)) {
            return false;
        }

        return true;
    }
}
