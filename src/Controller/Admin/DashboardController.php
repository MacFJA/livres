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

use App\Entity\Book;
use App\Entity\Movement;
use App\Entity\ProviderConfiguration;
use App\Entity\User;
use App\Repository\BookRepository;
use App\Repository\MovementRepository;
use App\Repository\ProviderConfigurationRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DashboardController extends AbstractDashboardController
{
    /**
     * @Route("/admin")
     */
    public function dashboard(ProviderConfigurationRepository $configRepository, BookRepository $bookRepository, MovementRepository $movementRepository): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'bookCount' => $bookRepository->count([]),
            'movementCount' => $movementRepository->count(['endAt' => null]),
            'activeProviderCount' => $configRepository->count(['active' => true]),
            'lastBook' => $bookRepository->findOneBy([], ['addedAt' => 'DESC']),
            'lastReturn' => $movementRepository->findOneBy([], ['endAt' => 'DESC']),
            'lastLeaving' => $movementRepository->findOneBy([], ['startAt' => 'DESC']),
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Livres <small>&mdash; Keep track of your books</small>');
    }

    public function configureCrud(): Crud
    {
        return Crud::new();
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @suppress PhanUnusedPublicMethodParameter
     */
    public function configureUserMenu(UserInterface $user): UserMenu
    {
        return UserMenu::new()
            ->setName($user->getUsername())
            ->displayUserName(true)
            ->displayUserAvatar(false)
            ->addMenuItems([
                MenuItem::linkToLogout('Logout', 'fa fa-sign-out'),
            ]);
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToCrud('Books', 'fas fa-book', Book::class);
        yield MenuItem::linkToCrud('Movements', 'fas fa-people-arrows', Movement::class);

        yield MenuItem::section('Configurations');
        yield MenuItem::linkToCrud('Providers Configuration', 'fas fa-sliders-h', ProviderConfiguration::class);
        yield MenuItem::linktoRoute('Display Configurations', 'fas fa-desktop', 'admin_display_settings');

        yield MenuItem::section('Others');
        yield MenuItem::linktoRoute('Search Engine', 'fas fa-search', 'admin_search_engine');
        yield MenuItem::linktoRoute('Covers', 'far fa-image', 'admin_image');
        yield MenuItem::linkToCrud('Users', 'fas fa-user-cog', User::class);
    }
}
