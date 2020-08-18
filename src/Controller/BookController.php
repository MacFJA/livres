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

namespace App\Controller;

use App\Doctrine\BookInjectionListener;
use App\Entity\Book;
use App\Worker\BookCreator;
use function array_column;
use Doctrine\ORM\EntityManagerInterface;
use function is_array;
use function json_decode;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

class BookController extends AbstractController
{
    /**
     * @IsGranted("ROLE_CAN_ADD")
     * @Route ("/book/add", name="add_book", methods={"POST"})
     */
    public function addBook(Request $request, BookCreator $bookCreator, EntityManagerInterface $objectManager, CacheManager $cacheManager, BookInjectionListener $injectionListener, \Symfony\Component\Serializer\Normalizer\NormalizerInterface $normalizer): JsonResponse
    {
        return $this->saveBook(null, $request, $bookCreator, $objectManager, $cacheManager, $injectionListener, $normalizer);
    }

    /**
     * @IsGranted("ROLE_CAN_EDIT")
     * @Route ("/book/edit/{bookId<\d+>}", name="edit_book", methods={"POST"})
     */
    public function editBook(int $bookId, Request $request, BookCreator $bookCreator, EntityManagerInterface $objectManager, CacheManager $cacheManager, BookInjectionListener $injectionListener, \Symfony\Component\Serializer\Normalizer\NormalizerInterface $normalizer): JsonResponse
    {
        return $this->saveBook($bookId, $request, $bookCreator, $objectManager, $cacheManager, $injectionListener, $normalizer);
    }

    /**
     * @IsGranted("ROLE_CAN_EDIT")
     * @Route("/book/{id}/is-back", name="book_is_back", methods={"PUT"})
     * @ParamConverter("book", class=Book::class)
     */
    public function isBack(Book $book, EntityManagerInterface $entityManager): Response
    {
        foreach ($book->getMovements() as $movement) {
            if (!$movement->isEnded()) {
                $movement->endNow();
                $entityManager->persist($movement);
            }
        }
        $entityManager->flush();

        return new Response();
    }

    private function saveBook(?int $bookId, Request $request, BookCreator $bookCreator, EntityManagerInterface $objectManager, CacheManager $cacheManager, BookInjectionListener $injectionListener, \Symfony\Component\Serializer\Normalizer\NormalizerInterface $normalizer): JsonResponse
    {
        $metadata = $objectManager->getClassMetadata(Book::class);

        $items = json_decode((string) $request->getContent(), true);
        if (!is_array($items)) {
            throw new BadRequestHttpException();
        }
        $items = array_column($items, 'value', 'key');

        $book = $bookCreator->createBook($items, $bookId);

        $injectionListener->handleCover($book, $metadata);

        return new JsonResponse(
            ((array) $normalizer->normalize($book, 'jsonld'))
            + ['coverFull' => $cacheManager->getBrowserPath($book->getCover() ?? 'placeholder.jpg', 'book_cover')]
        );
    }
}
