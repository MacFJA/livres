<?php
/**
 * @author  MacFJA
 * @license MIT
 */
namespace App\Controller;

use App\Entity\Book;
use App\Worker\Entity\BookInjectionListener;
use App\Worker\Entity\PredefinedType;
use App\Worker\Query\Pool;
use App\Worker\Query\ProviderInterface;
use App\Worker\Query\QueryResult;
use Doctrine\ORM\Query;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

class EditController extends Controller
{
    /** @var  Pool */
    protected $pool;
    /** @var  PredefinedType */
    protected $predefinedType;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var BookInjectionListener
     */
    private $bookInjection;

    /**
     * EditController constructor.
     *
     * @param Pool                  $pool
     * @param PredefinedType        $predefinedType
     * @param TranslatorInterface   $translator
     * @param BookInjectionListener $bookInjection
     */
    public function __construct(
        Pool $pool,
        PredefinedType $predefinedType,
        TranslatorInterface $translator,
        BookInjectionListener $bookInjection
    ) {
        $this->pool = $pool;
        $this->predefinedType = $predefinedType;
        $this->translator = $translator;
        $this->bookInjection = $bookInjection;
    }

    /**
     * @Route("/book/improve/{id}", name="book-improve")
     * @param string $id
     * @return Response
     */
    public function improve(string $id): Response
    {
        $this->bookInjection->setDisableCoverInjection(true);
        $entityManager = $this->getDoctrine()->getManager();

        /** @var Book $book */
        $book = $entityManager->find(Book::class, $id);

        $baseFields = $this->getBaseFields();

        $fields = $book->toArray(['addedAt', 'ean'], [Book::ARRAY_INJECT_OTHERS => true]);

        foreach ($baseFields as $field) {
            if (!array_key_exists($field, $fields)) {
                $fields[$field] = '';
            }
        }

        $providers = array_map(function (ProviderInterface $providerInterface): array {
            return ['code' => $providerInterface->getCode(), 'label' => $providerInterface::getLabel()];
        }, $this->pool->getProviders());

        return $this->render('addCompare/complete.ajax.html.twig', [
            'providers' => $providers,
            'fields'    => $fields,
            'id'        => $id,
            'isbn'      => $book->getIsbn()
        ]);
    }
    
    private function getBaseFields(): array
    {
        return [
            'isbn',
            'title',
            'author',
            'pages',
            'serie',
            'sortTitle',
            'publisher',
            'owner',
            'illustrator',
            'translator',
            'genre',
            'edition',
            'editor',
            'dimension',
            'keywords',
            'cover',
            'storage'
        ];
    }

    private function addByPage(string $template): Response
    {
        $baseFields = $this->getBaseFields();
        $providers = array_map(function (ProviderInterface $providerInterface): array {
            return ['code' => $providerInterface->getCode(), 'label' => $providerInterface::getLabel()];
        }, $this->pool->getProviders());

        return $this->render($template, [
            'providers' => $providers,
            'fields'    => array_fill_keys($baseFields, '')
        ]);
    }

    /**
     * @Route("book/add/by-image", name="book-add-by-image")
     *
     * @return Response
     */
    public function addByImage(): Response
    {
        return $this->addByPage('addCompare/image.html.twig');
    }

    /**
     * @Route("book/add/by-video", name="book-add-by-video")
     *
     * @return Response
     */
    public function addByVideo(): Response
    {
        return $this->addByPage('addCompare/live.html.twig');
    }

    /**
     * @Route("/book/add/by-isbn", name="book-add-by-isbn")
     * @return Response
     */
    public function addByIsbn(): Response
    {
        return $this->addByPage('addCompare/ajax.html.twig');
    }
    
    /**
     *
     * @Route("/book/add/ajax/isbn/{isbn}/provider/{provider}", name="book-ajax-add-search")
     *
     * @param string $provider
     * @param string $isbn
     * @return Response
     * @throws \InvalidArgumentException
     */
    public function ajaxAddSearch(string $provider, string $isbn) : Response
    {
        $baseFields = $this->getBaseFields();

        $providers = $this->pool->getProvidersWithCode($provider);

        $results = [];
        foreach ($providers as $item) {
            if ($item->canSearch('isbn')) {
                try {
                    $results[$item->getCode()] = array_merge(
                        $results[$item->getCode()]??[],
                        $item->search('isbn', $isbn)
                    );
                } catch (\Exception $e) {
                    // Do nothing
                }
            }
        }

        $providers = count($results);
        $resultsCount = array_reduce($results, function (int $count, array $providerResults): int {
            return $count + count($providerResults);
        }, 0);
        /** @var string[] $allFields */
        $allFields = array_reduce($results, function (array $carry, array $providerResults):array {
            $founds = array_reduce($providerResults, function (array $fields, QueryResult $oneResult): array {
                return array_merge($fields, array_keys($oneResult->getNormalized(false)));
            }, []);

            return array_merge($carry, $founds);
        }, []);

        $allFields = array_merge($allFields, $baseFields);
        $allFields = array_unique($allFields);

        $allFields = array_combine(array_values($allFields), array_map(function (string $item):string {
            return $this->translator->trans($item, [], 'book_field');
        }, $allFields));

        $types = array_filter($this->predefinedType->getTypes(Book::class));
        $types['cover'] = 'image';

        $json = [
            'resultsCount'  => $resultsCount,
            'providerCount' => $providers,
            'allFields'     => $allFields,
            'types'         => $types
        ];

        $resultJson = array_reduce($results, function (array $carry, array $providerResults) use ($types): array {
            $found = array_map(function (QueryResult $result) use ($types): array {
                $fields = [];
                foreach ($result->getNormalized(false) as $field => $value) {
                    $fields[] = [
                        'label' => $this->translator->trans($field, [], 'book_field'),
                        'name'  => $field,
                        'value' => $value,
                        'type'  => $types[$field]??''
                    ];
                }

                return [
                    'providerLabel' => $result->getProviderName(),
                    'fields'        => $fields
                ];
            }, $providerResults);

            return array_merge($carry, $found);
        }, []);

        $json['results'] = $resultJson;

        return new JsonResponse($json);
    }
}
