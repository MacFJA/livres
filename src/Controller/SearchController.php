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

use App\Repository\BookRepository;
use App\Worker\BookSearch;
use function array_column;
use function array_combine;
use function array_filter;
use function array_map;
use function array_merge;
use function array_reduce;
use function array_unique;
use function array_values;
use function count;
use Doctrine\Common\Annotations\Annotation\IgnoreAnnotation;
use function is_array;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class DebugController.
 *
 * @IgnoreAnnotation("suppress")
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SearchController extends AbstractController
{
    /**
     * @IsGranted("ROLE_CAN_VIEW")
     * @Route("/completions", name="completions")
     *
     * @param array<string> $completionsType
     */
    public function getCompletions(BookRepository $bookRepository, array $completionsType): JsonResponse
    {
        return new JsonResponse(
            array_combine(
                $completionsType,
                array_map(function ($type) use ($bookRepository) {
                    return $this->getAllValues($bookRepository, $type);
                }, $completionsType)
            )
        );
    }

    /**
     * @IsGranted("ROLE_CAN_VIEW")
     * @Route ("/suggestions", name="suggestions", methods={"GET"})
     */
    public function suggestAndSearch(Request $request, BookSearch $bookSearch): JsonResponse
    {
        $query = $request->query->get('q');

        if (null === $query) {
            return new JsonResponse([]);
        }

        return new JsonResponse($bookSearch->getSuggestions($query));
    }

    /**
     * @IsGranted("ROLE_CAN_VIEW")
     * @Route ("/searchPreview", name="searchPreview", methods={"GET"})
     */
    public function searchPreview(Request $request, BookSearch $bookSearch): JsonResponse
    {
        $query = $request->query->get('q');

        if (null === $query) {
            return new JsonResponse([]);
        }

        $result = $bookSearch->getPartialSearch($query);

        if (count($result) > 5) {
            return new JsonResponse([]);
        }

        return new JsonResponse($result);
    }

    /**
     * @return array<string>
     */
    private function getAllValues(BookRepository $bookRepository, string $field): array
    {
        $values = $bookRepository->createQueryBuilder('b')
            ->select('b.'.$field)
            ->distinct()
            ->getQuery()
            ->getArrayResult();

        $values = array_column($values, $field);
        $values = array_reduce($values, function ($carry, $item) {
            if (is_array($item)) {
                return array_merge($carry, $item);
            }
            $carry[] = $item;

            return $carry;
        }, []);

        return array_values(array_filter(array_unique($values)));
    }
}
