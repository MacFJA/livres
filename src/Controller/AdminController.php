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

use App\Controller\Admin\ImageTrait;
use App\Controller\Admin\SearchEngineTrait;
use App\Doctrine\BookInjectionListener;
use function array_key_exists;
use EasyCorp\Bundle\EasyAdminBundle\Controller\EasyAdminController;
use Flintstone\Flintstone;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class AdminController extends EasyAdminController
{
    use SearchEngineTrait;
    use ImageTrait;

    public const AVAILABLE_SORT = [
        'newest' => 'Last added first',
        'oldest' => 'Oldest first',
        'title' => 'Title',
        'series' => 'Series',
        'sortTitle' => 'Sort title',
        'series-title' => 'Series, then Title',
        'series-sortTitle' => 'Series, then Sort title',
    ];

    public function __construct(BookInjectionListener $injectionListener)
    {
        $injectionListener->setDisableInjection(true);
    }

    /**
     * @Route("/display-settings", methods={"GET"}, name="admin_display_settings")
     */
    public function displaySettings(Request $request, Flintstone $flintstone): Response
    {
        $this->initialize($request);

        if (null === $request->query->get('entity')) {
            return $this->render('admin/display-settings.html.twig', [
                'per_page' => $flintstone->get('per_page') ?: '10',
                'default_sort' => $flintstone->get('default_sort') ?: 'newest',
            ]);
        }

        return parent::indexAction($request);
    }

    /**
     * @Route ("/display-settings", methods={"POST"}, name="admin_display_settings_post")
     */
    public function displaySettingsPost(Request $request, Flintstone $flintstone): RedirectResponse
    {
        $perPage = (int) $request->request->get('per_page', '10');
        $flintstone->set('per_page', $perPage);

        $defaultSort = $request->request->get('default_sort', 'newest') ?? 'newest';
        if (!array_key_exists($defaultSort, self::AVAILABLE_SORT)) {
            $defaultSort = 'newest';
        }
        $flintstone->set('default_sort', $defaultSort);

        $this->addFlash('success', 'Configurations saved');

        return $this->redirectToRoute('admin_display_settings');
    }
}
