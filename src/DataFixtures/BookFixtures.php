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

namespace App\DataFixtures;

use App\Entity\Book;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Exception;
use function explode;
use Faker\Factory;
use function implode;
use function random_int;
use function strtolower;

class BookFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        for ($loopIndex = 0; $loopIndex < 150; $loopIndex++) {
            $book = new Book();
            $book
                ->setTitle(implode(' ', (array) $faker->words(random_int(2, 6))))
                ->setAuthors($this->getPeople())
                ->setIllustrators($this->getPeople())
                ->setIsbn($faker->isbn13)
                ->setOwner($faker->name)
                ->setCover($faker->imageUrl(300, 500, 'abstract'))
                ->setGenres(explode(' ', $faker->jobTitle))
                ->setKeywords((array) $faker->words)
                ->setPublicationDate($faker->dateTimeThisCentury)
                ->setPages(random_int(20, 800))
                ->setFormat($faker->randomElement())
                ->setAdditional([])
                ->setStorage('here')
                ->setAddedAt(new DateTime())
                ->setDimension('21x29.7');
            $book->setSortTitle(strtolower($book->getTitle() ?? ''));
            $manager->persist($book);
        }

        $manager->flush();
    }

    /**
     * @throws Exception
     *
     * @return array<string>
     */
    protected function getPeople(): array
    {
        $faker = Factory::create();
        $count = random_int(1, 4);
        $result = [];

        for ($loopIndex = 0; $loopIndex < $count; $loopIndex++) {
            $result[] = $faker->name;
        }

        return $result;
    }
}
