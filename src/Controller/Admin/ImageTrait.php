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

use function assert;
use EasyCorp\Bundle\EasyAdminBundle\Controller\EasyAdminController;
use Iterator;
use function iterator_apply;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use RuntimeException;
use function sprintf;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Traversable;

trait ImageTrait
{
    /**
     * @Route("/cover-settings", methods={"GET"}, name="admin_image")
     */
    public function image(Request $request, string $coverDir): Response
    {
        if (!($this instanceof EasyAdminController)) {
            throw new RuntimeException(sprintf(
                'Trait %s can only be used with an instance of %s (currently used with %s)',
                ImageTrait::class,
                EasyAdminController::class,
                self::class
            ));
        }

        $this->initialize($request);

        if (null === $request->query->get('entity')) {
            $covers = (new Finder())
                ->in($coverDir)
                ->notName('placeholder.jpg')
                ->files();
            $size = 0;
            $iterator = $covers->getIterator();
            assert($iterator instanceof Traversable);
            iterator_apply($iterator, function (Iterator $files) use (&$size) {
                $size += $files->current()->getSize();

                return true;
            }, [$iterator]);

            return $this->render('admin/image.html.twig', ['count' => $covers->count(), 'size' => $size]);
        }

        return $this->indexAction($request);
    }

    /**
     * @Route ("/purge-cover", methods={"GET"}, name="admin_image_cache_purge")
     *
     * @return RedirectResponse
     */
    public function purgeCover(CacheManager $cacheManager): Response
    {
        if (!($this instanceof EasyAdminController)) {
            throw new RuntimeException(sprintf(
                'Trait %s can only be used with an instance of %s (currently used with %s)',
                ImageTrait::class,
                EasyAdminController::class,
                self::class
            ));
        }

        $cacheManager->remove();

        $this->addFlash('success', 'Cover cache is purged.');

        return $this->redirectToRoute('admin_image');
    }

    /**
     * @Route("/delete-cover",name="admin_image_delete_cover")
     */
    public function deleteDownloadedCover(Filesystem $filesystem, string $coverDir): Response
    {
        if (!($this instanceof EasyAdminController)) {
            throw new RuntimeException(sprintf(
                'Trait %s can only be used with an instance of %s (currently used with %s)',
                ImageTrait::class,
                EasyAdminController::class,
                self::class
            ));
        }

        $filesystem->remove(
            (new Finder())
                ->in($coverDir)
                ->notName('placeholder.jpg')
                ->files()
        );

        $this->addFlash('success', 'Downloaded covers are deleted.');

        return $this->redirectToRoute('admin_image');
    }
}
