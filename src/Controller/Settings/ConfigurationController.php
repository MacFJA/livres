<?php
/**
 * @author  MacFJA
 * @license MIT
 */
namespace App\Controller\Settings;

use Flintstone\Flintstone;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConfigurationController extends Controller
{
    /**
     * @var Flintstone
     */
    private $configuration;

    /**
     * ConfigurationController constructor.
     *
     * @param Flintstone             $configuration
     */
    public function __construct(
        Flintstone $configuration
    ) {
        $this->configuration = $configuration;
    }
    
    /**
     * @Route("settings/config", name="settings-config")
     * @param Request $request
     * @return Response
     */
    public function manageConfiguration(Request $request): Response
    {
        $configurationKeys = $this->configuration->getKeys() + ['page_size'];

        $form = $this->createFormBuilder($this->configuration->getAll());
        foreach ($configurationKeys as $key) {
            $form->add($key, TextType::class, ['required' => false]);
        }

        $form = $form->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            foreach ($form->getData() as $key => $value) {
                $this->configuration->set($key, $value);
            }
            return $this->redirectToRoute('settings');
        }

        return $this->render('settings/configurations.html.twig', ['form' => $form->createView()]);
    }
}
