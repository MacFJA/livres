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
use App\Worker\BookSearch;
use Doctrine\ORM\Event\LifecycleEventArgs;

class BookSearchIndexListener
{
    /** @var BookSearch */
    private $bookSearch;

    public function __construct(BookSearch $bookSearch)
    {
        $this->bookSearch = $bookSearch;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @phan-suppress PhanUnusedPublicNoOverrideMethodParameter
     */
    public function postRemove(Book $book, LifecycleEventArgs $lifecycleEvent): void
    {
        $this->bookSearch->removeBookFromIndex($book);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @phan-suppress PhanUnusedPublicNoOverrideMethodParameter
     */
    public function postUpdate(Book $book, LifecycleEventArgs $lifecycleEvent): void
    {
        $this->bookSearch->removeBookFromIndex($book);
        $this->bookSearch->addIndexAndSuggestion($book);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @phan-suppress PhanUnusedPublicNoOverrideMethodParameter
     */
    public function postPersist(Book $book, LifecycleEventArgs $lifecycleEvent): void
    {
        $this->bookSearch->addIndexAndSuggestion($book);
    }
}
