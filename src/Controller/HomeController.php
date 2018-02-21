<?php
/**
  * @author  MacFJA
  * @license MIT
  */
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends Controller
{
    /**
     * @Route(path="/")
     */
    public function index(): RedirectResponse
    {
        return $this->redirectToRoute('book-list');
    }
}
