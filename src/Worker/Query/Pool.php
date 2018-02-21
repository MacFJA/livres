<?php
/**
 * @author  MacFJA
 * @license MIT
 */
namespace App\Worker\Query;

use App\Entity\ProviderConfiguration;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class Pool
{
    const EVENT_START_PROVIDER_NAME = 'livres.provider.start';
    const EVENT_END_PROVIDER_NAME = 'livres.provider.end';
    /** @var  ProviderInterface[] */
    protected $providers;
    /** @var  EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * Pool constructor.
     *
     * @param EventDispatcherInterface $eventDispatcher
     * @param EntityManagerInterface   $entityManager
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, EntityManagerInterface $entityManager)
    {
        $this->eventDispatcher = $eventDispatcher;

        $this->loadProviders($entityManager);
    }

    /**
     * Load from the database all providers mark as active, and create an instance of them.
     * @param EntityManagerInterface $entityManager
     * @return void
     */
    protected function loadProviders(EntityManagerInterface $entityManager)
    {
        $repository = $entityManager->getRepository(ProviderConfiguration::class);
        $providers = $repository->findBy(['active' => true]);

        $this->providers = array_map(function (ProviderConfiguration $configuration): ProviderInterface {
            return $configuration->getConfiguredProvider();
        }, $providers);
    }

    /**
     * @param ProviderInterface $provider
     * @return void
     */
    public function addProvider(ProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * Search in all provider for the wanted field/value pair
     * @param string $field
     * @param string $value
     * @return array<string,QueryResult[]>
     */
    public function search(string $field, string $value): array
    {
        $results = [];
        foreach ($this->providers as $provider) {
            if ($provider->canSearch($field)) {
                $this->eventDispatcher->dispatch(static::EVENT_START_PROVIDER_NAME, new GenericEvent($provider));
                try {
                    $providerResult = $provider->search($field, $value);
                } catch (\Exception $e) {
                    $providerResult = [];
                }
                $results[$provider->getCode()] = $providerResult;
                $this->eventDispatcher->dispatch(static::EVENT_END_PROVIDER_NAME, new GenericEvent(
                    $provider,
                    ['result' => $providerResult]
                ));
            }
        }

        return array_filter($results);
    }

    /**
     * Search in all providers the list of field/value pair
     * @param array <string,string> $terms
     * @return array<string,QueryResult[]>
     */
    public function searchComposite(array $terms): array
    {
        $results = [];
        foreach ($this->providers as $provider) {
            $this->eventDispatcher->dispatch(static::EVENT_START_PROVIDER_NAME, new GenericEvent($provider));
            $providerResult = $provider->searchComposite($terms);
            $results[$provider->getCode()] = $providerResult;
            $this->eventDispatcher->dispatch(static::EVENT_END_PROVIDER_NAME, new GenericEvent(
                $provider,
                ['result' => $providerResult]
            ));
        }

        return array_filter($results);
    }

    /**
     * @return ProviderInterface[]
     */
    public function getProviders()
    {
        return $this->providers;
    }

    /**
     * Get all provider that have the code {@code $code}
     * @param string $code
     * @return ProviderInterface[]
     */
    public function getProvidersWithCode(string $code)
    {
        return array_filter($this->providers, function (ProviderInterface $provider) use ($code): bool {
            return $provider->getCode() === $code;
        });
    }
}
