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

use App\Admin\Form\Field\ProviderParametersField;
use App\Entity\ProviderConfiguration;
use function count;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use function is_array;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ProviderConfigurationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProviderConfiguration::class;
    }

    /**
     * @suppress PhanUndeclaredFunctionInCallable
     * @suppress PhanInvalidFQSENInCallable
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Provider Configuration')
            ->setEntityLabelInPlural('Provider Configurations')
            ->setSearchFields(['configurationId', 'provider', 'parameters']);
    }

    public function configureFields(string $pageName): iterable
    {
        $provider = TextField::new('provider');
        $active = Field::new('active');
        $parameters = ProviderParametersField::new('parameters');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$provider, $active];
        }

        if (Crud::PAGE_EDIT === $pageName) {
            return [$provider, $active, $parameters];
        }

        return [];
    }

    /**
     * @phpstan-return FormBuilderInterface<FormBuilderInterface>
     * @psalm-return FormBuilderInterface
     */
    public function createEditFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $form = parent::createEditFormBuilder($entityDto, $formOptions, $context);
        $parameters = $entityDto->getInstance()->getParameters();
        $form->add('provider', TextType::class, ['attr' => ['readonly' => true]]);

        if (!is_array($parameters) || 0 === count($parameters)) {
            $form->remove('parameters');
        }
        if ($entityDto->getInstance()->haveParameters() && !$entityDto->getInstance()->getActive()) {
            $options = $form->get('active')->getOptions();
            $options['help'] = 'This provider have parameters, active it to configure them.';
            $form->add('active', CheckboxType::class, $options);
        }

        return $form;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable('new', 'delete');
    }
}
