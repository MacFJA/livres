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

namespace App\Controller\Admin;

use App\Doctrine\BookInjectionListener;
use App\Entity\Book;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class BookCrudController extends AbstractCrudController
{
    public function __construct(BookInjectionListener $bookInjection)
    {
        $bookInjection->setDisableInjection(true);
    }

    public static function getEntityFqcn(): string
    {
        return Book::class;
    }

    /**
     * @suppress PhanUndeclaredFunctionInCallable
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Book')
            ->setEntityLabelInPlural('Books')
            ->setSearchFields(['isbn', 'title', 'authors', 'pages', 'series', 'owner', 'illustrators', 'genres', 'format', 'dimension', 'keywords', 'additional', 'storage']);
    }

    public function configureFields(string $pageName): iterable
    {
        $isbn = TextField::new('isbn');
        $title = TextField::new('title');
        $authors = ArrayField::new('authors');
        $pages = IntegerField::new('pages');
        $series = TextField::new('series');
        $sortTitle = TextField::new('sortTitle');
        $owner = TextField::new('owner');
        $illustrators = ArrayField::new('illustrators');
        $genres = ArrayField::new('genres');
        $publicationDate = DateField::new('publicationDate');
        $format = TextField::new('format');
        $dimension = TextField::new('dimension');
        $keywords = ArrayField::new('keywords');
        $addedAt = DateTimeField::new('addedAt');
        $cover = TextField::new('cover');
        $storage = TextField::new('storage');
        $movements = AssociationField::new('movements');
        $bookId = IntegerField::new('bookId');

        if (Crud::PAGE_INDEX === $pageName) {
            $cover = ImageField::new('cover');

            return [$cover, $isbn, $series, $title, $storage, $owner];
        }
        if (Crud::PAGE_DETAIL === $pageName) {
            $cover = ImageField::new('cover');

            return [$bookId, $isbn, $title, $authors, $pages, $series, $sortTitle, $owner, $illustrators, $genres, $publicationDate, $format, $dimension, $keywords, $addedAt, $cover, $storage, $movements];
        }
        if (Crud::PAGE_NEW === $pageName) {
            return [$isbn, $title, $authors, $pages, $series, $sortTitle, $owner, $illustrators, $genres, $publicationDate, $format, $dimension, $keywords, $addedAt, $cover, $storage, $movements];
        }
        if (Crud::PAGE_EDIT === $pageName) {
            return [$isbn, $title, $authors, $pages, $series, $sortTitle, $owner, $illustrators, $genres, $publicationDate, $format, $dimension, $keywords, $addedAt, $cover, $storage, $movements];
        }

        return [];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }
}
