<?php
/**
 * @author  MacFJA
 * @license MIT
 */
namespace App\Controller\Settings;

use App\Entity\ProviderConfiguration;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

class ProviderController extends Controller
{
    /** @var array */
    protected $providers = [];
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * ProviderController constructor.
     *
     * @param FormFactoryInterface   $formFactory
     * @param TranslatorInterface    $translator
     * @param array                  $providers
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        TranslatorInterface $translator,
        array $providers = []
    ) {
        $this->providers = $providers;
        $this->formFactory = $formFactory;
        $this->translator = $translator;
    }
    
    /**
     * @Route("settings/providers", name="settings-providers")
     */
    public function manageProviders(Request $request) : Response
    {
        return $this->render('settings/providers.html.twig', ['confs' => $this->getAllForms($request)]);
    }
    
    private function getAllForms(Request $request) : array
    {
        $results = [];

        foreach ($this->providers as $provider) {
            $form = $this->getConfigurationForm($provider, $request);
            $reflection = new \ReflectionClass($provider);
            $label = $reflection->getMethod('getLabel');

            $results[] = ['form' => $form->createView(), 'label' => $label->invoke(null)];
        }

        $this->getDoctrine()->getManager()->flush();

        return $results;
    }

    /**
     * @param string $class
     * @return string[]
     */
    private function getParameters(string $class): array
    {
        $reflection = new \ReflectionClass($class);
        /** @var \ReflectionMethod|null $constructor */
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return [];
        }

        $parameters = $constructor->getParameters();
        return array_map(function (\ReflectionParameter $parameter): string {
            return $parameter->getName();
        }, $parameters);
    }
    private function getParameterLabel(string $class, string $parameterCode): string
    {
        $reflection = new \ReflectionClass($class);
        $parametersDoc = $reflection->getConstructor()->getDocComment()?:'';

        $matches = [];
        preg_match(
            '/@param\s+[\w\\_]*\s*\$' . preg_quote($parameterCode) . '[^\n]\s*(\S.+)$/smU',
            $parametersDoc,
            $matches
        );

        if (count($matches) == 0) {
            return ucwords(preg_replace('/([A-Z])/', ' $1', $parameterCode));
        }

        return $matches[1];
    }

    private function handleFormRequest(FormInterface $form, Request $request): FormInterface
    {
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $conf = $form->getData();
            $this->getDoctrine()->getManager()->persist($conf);
        }

        return $form;
    }

    private function getConfigurationForm(string $class, Request $request): FormInterface
    {
        $conf = $this->getDoctrine()->getRepository(ProviderConfiguration::class)->findOneBy(['class' => $class]);
        if ($conf === null) {
            $conf = ProviderConfiguration::createFromArray(['class' => $class]);
        }
        $className = explode('\\', $class);
        $className = strtolower(end($className));
        $formBuilder = $this->formFactory->createNamedBuilder($className, FormType::class, $conf);
        $formBuilder->add('active', CheckboxType::class, [
            'required' => false,
            'label' => $this->translator->trans('Active', [], 'provider_field')
        ]);
        $formBuilder->add('class', HiddenType::class);

        $params = $this->getParameters($class);
        foreach ($params as $param) {
            $formBuilder->add($param, TextType::class, [
                'label' => $this->translator->trans($this->getParameterLabel($class, $param), [], 'provider_field'),
                'required' => false
            ]);
        }

        return $this->handleFormRequest($formBuilder->getForm(), $request);
    }
}
