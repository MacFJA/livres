<?php
/**
 * @author  MacFJA
 * @license MIT
 */
namespace App\Controller;

use App\Entity\ProviderConfiguration;
use Doctrine\ORM\EntityManagerInterface;
use Flintstone\Flintstone;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

class SettingsController extends Controller
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var CacheManager
     */
    private $cacheManager;
    /**
     * @var Filesystem
     */
    private $filesystem;
    /**
     * @var string
     */
    private $coverDir;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * SettingsController constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param CacheManager           $cacheManager
     * @param Filesystem             $filesystem
     * @param TranslatorInterface    $translator
     * @param string                 $coverDir
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        CacheManager $cacheManager,
        Filesystem $filesystem,
        TranslatorInterface $translator,
        string $coverDir
    ) {
        $this->entityManager = $entityManager;
        $this->cacheManager = $cacheManager;
        $this->filesystem = $filesystem;
        $this->coverDir = $coverDir;
        $this->translator = $translator;
    }

    /**
     * @Route("settings/purge-cover",name="settings-purge-cover")
     * @return JsonResponse
     * @throws \Symfony\Component\Translation\Exception\InvalidArgumentException
     */
    public function clearCoverCache(): JsonResponse
    {
        $this->cacheManager->remove();

        return new JsonResponse([
            'status' => 'OK',
            'text' => $this->translator->trans('Cache purged!', [], 'settings')
        ]);
    }

    /**
     * @Route("settings/delete-cover",name="settings-delete-cover")
     * @return JsonResponse
     * @throws \Symfony\Component\Translation\Exception\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\Filesystem\Exception\IOException
     */
    public function deleteDownloadedCover(): JsonResponse
    {
        $this->filesystem->remove(
            (new Finder())
                ->in($this->coverDir)
                ->notName('placeholder.png')
                ->files()
        );

        return new JsonResponse([
            'status' => 'OK',
            'text' => $this->translator->trans('downloader_removed', [], 'settings')
        ]);
    }

    /**
     * @Route("settings", name="settings")
     * @return Response
     * @throws \UnexpectedValueException
     */
    public function viewSettings(): Response
    {
        $providerConfigurations = $this->entityManager
            ->getRepository(ProviderConfiguration::class)
            ->findBy([], ['class' => 'ASC']);
        return $this->render('settings/index.html.twig', ['providers' => $providerConfigurations]);
    }
}
