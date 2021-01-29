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

use Doctrine\Common\Annotations\Annotation\IgnoreAnnotation;
use Flintstone\Flintstone;
use MacFJA\BookRetriever\ProviderConfigurationInterface;
use MacFJA\BookRetriever\ProviderInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class HomepageController.
 *
 * @IgnoreAnnotation("suppress")
 */
class HomepageController extends AbstractController
{
    /**
     * @IsGranted("ROLE_CAN_VIEW")
     *
     * @param iterable<ProviderInterface> $providers
     * @Route("/", name="homepage")
     * @suppress PhanAbstractStaticMethodCall
     */
    public function base(iterable $providers, ProviderConfigurationInterface $providerConfig, Flintstone $flintstone): Response
    {
        $providersInfo = [];
        /** @var ProviderInterface $provider */
        foreach ($providers as $provider) {
            if ($providerConfig->isActive($provider)) {
                $info = [
                    'name' => $provider::getLabel(),
                    'code' => $provider->getCode(),
                    'url' => $this->generateUrl('search_isbn', ['code' => $provider->getCode(), 'isbn' => 0]),
                ];
                $providersInfo[] = $info;
            }
        }

        return $this->render('svelte.html.twig', [
            'providers' => $providersInfo,
            'pageSize' => $flintstone->get('per_page') ?: 10,
        ]);
    }

    /**
     * @Route("/language/{name}/", name="translation")
     */
    public function translation(string $name, TranslatorInterface $translator): JsonResponse
    {
        if (!($translator instanceof TranslatorBagInterface)) {
            return new JsonResponse([]);
        }

        return new JsonResponse($translator->getCatalogue($name)->all('front'));
    }
}
