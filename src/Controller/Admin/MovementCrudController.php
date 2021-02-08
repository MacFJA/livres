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

use App\Entity\Movement;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class MovementCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Movement::class;
    }

    /**
     * @suppress PhanUndeclaredFunctionInCallable
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Movement')
            ->setEntityLabelInPlural('Movements')
            ->setSearchFields(['movementId', 'type', 'person']);
    }

    public function configureFields(string $pageName): iterable
    {
        $startAt = DateTimeField::new('startAt');
        $endAt = DateTimeField::new('endAt');
        $person = TextField::new('person');
        $book = AssociationField::new('book');
        $movementId = IntegerField::new('movementId');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$book, $person, $startAt, $endAt];
        }
        if (Crud::PAGE_DETAIL === $pageName) {
            return [$movementId, $book, $person, $startAt, $endAt];
        }
        if (Crud::PAGE_NEW === $pageName) {
            return [$startAt, $endAt, $person, $book];
        }
        if (Crud::PAGE_EDIT === $pageName) {
            return [$startAt, $endAt, $person, $book];
        }

        return [];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }
}
