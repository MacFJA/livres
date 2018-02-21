<?php
/**
  * @author  MacFJA
  * @license MIT
  */
namespace App\Controller;

use App\Entity\Book;
use App\Entity\Movement;
use App\Worker\Entity\Person;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

class MovementController extends Controller
{
    /** @var  Person */
    protected $personWorker;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * MovementController constructor.
     *
     * @param Person              $personWorker
     * @param TranslatorInterface $translator
     */
    public function __construct(Person $personWorker, TranslatorInterface $translator)
    {
        $this->personWorker = $personWorker;
        $this->translator = $translator;
    }

    /**
     * @Route("/movement/add/{book}", name="movement-add")
     * @param string $book
     * @return Response
     */
    public function addMovementForm(Request $request, string $book) : Response
    {
        $form = $this->getForm($request, $book);
        /** @var Book $forBook */
        $forBook = $this->getDoctrine()->getManager()->find(Book::class, $book);
        $people = $this->personWorker->getAllPersons();

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Movement $movement */
            $movement = $form->getData();
            $this->getDoctrine()->getManager()->persist($movement);
            $this->getDoctrine()->getManager()->flush();
            return $this->redirectToRoute('book_view', ['isbn' => $forBook->getIsbn()]);
        }

        return $this->render(
            'movement/create.html.twig',
            ['form' => $form->createView(), 'people' => array_values($people)]
        );
    }

    protected function getForm(Request $request, string $book) : FormInterface
    {
        /** @var Book $forBook */
        $forBook = $this->getDoctrine()->getManager()->find(Book::class, $book);
        $movement = Movement::createFromArray(['book' => $forBook, 'startAt' => new \DateTime()]);

        $form = $this->createFormBuilder($movement)
            ->setAction($this->generateUrl('movement-add', ['book' => $book]))
            ->add('person', TextType::class)
            ->add('type', ChoiceType::class, ['choices' => [
                $this->translator->trans('Is lend', [], 'movement') => Movement::TYPE_LEND,
                $this->translator->trans('Is borrow', [], 'movement') => Movement::TYPE_BORROW
            ]])
            ->getForm()
        ;
        $form->handleRequest($request);

        return $form;
    }

    /**
     * @Route("/ajax/movement/add/{book}", name="movement-ajax-add")
     * @param Request $request
     * @param string  $book
     * @return Response
     */
    public function ajaxAddForm(Request $request, string $book) : Response
    {
        $form = $this->getForm($request, $book);

        return $this->render(
            'movement/create.ajax.html.twig',
            ['form' => $form->createView()]
        );
    }

    /**
     * @Route("/movement/finish/{id}", name="movement-finish")
     * @param int $id
     * @return Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function finishMovement(int $id) : Response
    {
        /** @var Movement|null $movement */
        $movement = $this->getDoctrine()->getManager()->find(Movement::class, $id);
        
        if ($movement === null) {
            throw new NotFoundHttpException();
        }
        
        $movement->setEndAt(new \DateTimeImmutable());
        $this->getDoctrine()->getManager()->persist($movement);
        $this->getDoctrine()->getManager()->flush();
        
        return $this->redirectToRoute('book_view', ['isbn' => $movement->getBook()->getIsbn()]);
    }
}
