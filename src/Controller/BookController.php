<?php
/**
 * @author  MacFJA
 * @license MIT
 */
namespace App\Controller;

use App\Entity\Book;
use App\Worker\Entity\BookData;
use App\Worker\Entity\Person;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Flintstone\Flintstone;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

class BookController extends Controller
{
    const PAGE_SIZE = 500;
    /**
     * @var BookData
     */
    private $bookData;
    /**
     * @var Person
     */
    private $person;
    /**
     * @var Flintstone
     */
    private $configuration;

    /**
     * BookController constructor.
     *
     * @param BookData   $bookData
     * @param Person     $person
     * @param Flintstone $configuration
     */
    public function __construct(
        BookData $bookData,
        Person $person,
        Flintstone $configuration
    ) {
        $this->bookData = $bookData;
        $this->person = $person;
        $this->configuration = $configuration;
    }

    /**
     * @Route("/book/",  defaults={"page"=1}, name="book-list")
     * @Route("/book/page/{page}", name="book-list-page")
     * @param int $page
     * @return Response
     * @internal param Request $request
     */
    public function listPage(int $page) : Response
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->getDoctrine()->getManager();

        return $this->displayList(
            $entityManager->createQuery(sprintf(
                'SELECT b FROM %s b ORDER BY b.serie ASC, b.sortTitle ASC, b.title ASC',
                Book::class
            )),
            'book-list-page',
            [],
            $page
        );
    }

    private function paginateQuery(Query $query, int $currentPage = 1, int $pageSize = self::PAGE_SIZE) : Paginator
    {
        $query->setMaxResults($pageSize)->setFirstResult($pageSize * ($currentPage - 1));

        return new Paginator($query);
    }

    protected function displayList(
        Query $query,
        string $routeName,
        array $params = [],
        int $page = 1,
        int $pageSize = -1
    ) : Response {
        if ($pageSize == -1) {
            $pageSize = $this->configuration->get('page_size') ?? static::PAGE_SIZE;
        }
        if ($pageSize < 1) {
            $pageSize = static::PAGE_SIZE;
        }

        if ($page < 1) {
            $page = 1;
        }

        $paginator = $this->paginateQuery($query, $page, $pageSize);

        return $this->render('book/list.html.twig', [
            'books'     => $paginator->getIterator(),
            'bookdata'  => $this->bookData,
            'count'     => $paginator->count(),
            'pageCount' => ceil($paginator->count() / $pageSize),
            'page'      => $page,
            'pageUrl'   => ['name' => $routeName, 'params' => $params],
            'filter'    => json_decode(base64_decode($params['criteria']??base64_encode('[]')), true),
            'forms'     => Book::getSearchableFields()
        ]);
    }

    /**
     * @Route("/book/filter/{criteria}", defaults={"page"= 1}, name="book-filter")
     * @Route("/book/filter/{criteria}/page/{page}", name="book-filter-page")
     * @param string $criteria
     * @param int    $page
     * @return Response
     */
    public function filterPage(string $criteria, int $page)
    {
        /** @var array<string,string> $criteriaDecoded */
        $criteriaDecoded = json_decode(base64_decode($criteria), true);
        $dqlWhere = [];

        foreach ($criteriaDecoded as $field => $value) {
            if (!Book::isFieldValid($field)) {
                throw new BadRequestHttpException(vsprintf('Field "%s" does not exists in %s', [$field, Book::class]));
            }

            $dqlWhere[] = sprintf('b.%s LIKE :search%1$s', $field);
        }

        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->getDoctrine()->getManager();
        $books = $entityManager
            ->createQuery(vsprintf('SELECT b FROM %s b WHERE %s', [Book::class, implode(' AND ', $dqlWhere)]));
        foreach ($criteriaDecoded as $field => $value) {
            $books->setParameter(':search' . $field, '%' . $value . '%');
        }

        return $this->displayList($books, 'book-filter-page', ['criteria' => $criteria], $page);
    }

    /**
     * @Route("/book/view/{isbn}", name="book_view")
     * @param string $isbn
     * @return Response
     */
    public function viewPage(string $isbn)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $books = $entityManager->getRepository(Book::class)->findBy(['isbn' => $isbn]);
        $people = array_values($this->person->getAllPersons());

        if (count($books) == 1) {
            return $this->render('book/view.html.twig', [
                'book'       => reset($books),
                'people'     => $people,
                'searchable' => Book::getSearchableFields()
            ]);
        }

        return $this->render('book/view_multiple.html.twig', [
            'books'      => $books,
            'people'     => $people,
            'searchable' => Book::getSearchableFields()
        ]);
    }

    /**
     * @Route("/book/add/do", name="book-add")
     * @return RedirectResponse
     */
    public function addBook(Request $request): RedirectResponse
    {
        $post = $request->request;
        $data = json_decode($post->get('data'), true);
        $data['addedAt'] = new \DateTimeImmutable();
        $book = Book::createFromArray($data);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($book);
        $entityManager->flush();

        return $this->redirectToRoute('book_view', ['isbn' => $book->getIsbn()]);
    }

    /**
     * @Route("/book/update/do/{id}", name="book-update")
     * @param Request $request
     * @param string  $id
     * @return RedirectResponse
     */
    public function updateBook(Request $request, string $id): RedirectResponse
    {
        $entityManager = $this->getDoctrine()->getManager();
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $metaData = $entityManager->getClassMetadata(Book::class);

        /** @var Book $book */
        $book = $entityManager->find(Book::class, $id);
        $post = $request->request;
        $data = json_decode($post->get('data'), true);

        $others = $metaData->getFieldValue($book, 'others');
        foreach ($data as $field => $value) {
            if (Book::isFieldValid($field)) {
                $metaData->setFieldValue($book, $field, $value);
                continue;
            }
            $others[$field] = $value;
        }
        $metaData->setFieldValue($book, 'others', $others);

        $book->validateFieldData();

        $entityManager->persist($book);
        $entityManager->flush();

        return $this->redirectToRoute('book_view', ['isbn' => $book->getIsbn()]);
    }
}
