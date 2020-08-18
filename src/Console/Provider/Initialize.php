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

namespace App\Console\Provider;

use App\Entity\ProviderConfiguration;
use App\Repository\ProviderConfigurationRepository;
use function array_filter;
use function array_map;
use function array_walk;
use function count;
use Doctrine\ORM\EntityManagerInterface;
use function in_array;
use function sprintf;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Initialize extends Command
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ProviderConfigurationRepository */
    private $configRepository;

    /**
     * Initialize constructor.
     */
    public function __construct(EntityManagerInterface $entityManager, ProviderConfigurationRepository $configRepository)
    {
        $this->entityManager = $entityManager;
        $this->configRepository = $configRepository;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('provider:initialize')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Override existing configuration')
            ->addOption('activate', null, InputOption::VALUE_NONE, 'Activate created provider (or all if --force)')
            ->setDescription('Create default configuration for all providers');
        parent::configure();
    }

    /**
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $style = new SymfonyStyle($input, $output);

        $defaultActivate = $input->getOption('activate');
        $default = [
            ['code' => 'abebooks-html', 'active' => $defaultActivate, 'params' => []],
            ['code' => 'amazon', 'active' => $defaultActivate, 'params' => ['access_key' => '', 'associated_tag' => '']],
            ['code' => 'antoineonline', 'active' => $defaultActivate, 'params' => []],
            ['code' => 'archiveorg', 'active' => $defaultActivate, 'params' => []],
            ['code' => 'digit-eyes', 'active' => $defaultActivate, 'params' => ['api_key' => '', 'app_code' => '', 'language' => 'en']],
            ['code' => 'ebay', 'active' => $defaultActivate, 'params' => ['app_id' => '', 'cert_id' => '', 'dev_id' => '']],
            ['code' => 'ebooksgratuits', 'active' => $defaultActivate, 'params' => []],
            ['code' => 'eurobuch', 'active' => $defaultActivate, 'params' => []],
            ['code' => 'feedbooks', 'active' => $defaultActivate, 'params' => []],
            ['code' => 'good-reads', 'active' => $defaultActivate, 'params' => ['api_key' => '']],
            ['code' => 'googlebooks', 'active' => $defaultActivate, 'params' => []],
            ['code' => 'isbndb', 'active' => $defaultActivate, 'params' => ['api_key' => '']],
            ['code' => 'lalibrairie', 'active' => $defaultActivate, 'params' => []],
            ['code' => 'library-hub', 'active' => $defaultActivate, 'params' => []],
            ['code' => 'library-thing', 'active' => $defaultActivate, 'params' => ['api_key' => '']],
            ['code' => 'loc', 'active' => $defaultActivate, 'params' => []],
            ['code' => 'oclc', 'active' => $defaultActivate, 'params' => []],
            ['code' => 'openlibrary', 'active' => $defaultActivate, 'params' => []],
            ['code' => 'randomhouse', 'active' => $defaultActivate, 'params' => []],
        ];

        $existing = $this->configRepository->findAll();
        $count = $this->configRepository->count([]);

        $style->note(sprintf('Found %d configuration(s) in the application', $count));
        $style->comment(sprintf('They are %d providers available', count($default)));

        $force = $input->getOption('force');
        if (true === $force) {
            $style->note('Removing existing configuration (--force flag)');
            array_walk($existing, [$this->entityManager, 'remove']);
        } else {
            $existingCodes = array_map(function (ProviderConfiguration $configuration) {
                return $configuration->getProvider();
            }, $existing);
            $default = array_filter($default, function (array $configuration) use ($existingCodes) {
                return !in_array($configuration['code'], $existingCodes, true);
            });
        }
        foreach ($default as $configuration) {
            $providerConfig = new ProviderConfiguration();
            $providerConfig->setProvider($configuration['code']);
            $providerConfig->setActive((bool) $configuration['active']);
            $providerConfig->setParameters($configuration['params']);

            $this->entityManager->persist($providerConfig);
        }
        $this->entityManager->flush();

        $style->success(['Providers configuration have been updated', sprintf('%d configuration added/updated', count($default))]);

        return 0;
    }
}
