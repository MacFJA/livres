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

namespace App\Worker\Search;

use function array_key_exists;
use function assert;
use Enqueue\Dsn\Dsn;
use function is_string;
use MacFJA\RediSearch\Aggregate;
use MacFJA\RediSearch\Index;
use MacFJA\RediSearch\Integration\IndexObjectFactory;
use MacFJA\RediSearch\Search;
use MacFJA\RediSearch\Suggestions;
use Predis\Client;
use Psr\EventDispatcher\EventDispatcherInterface;
use function str_replace;

class ObjectFactory implements IndexObjectFactory
{
    /**
     * @phpstan-var Client<Client>
     * @psalm-var Client
     *
     * @var Client
     */
    private $client;

    /** @var array<string,Index> */
    private $indexes = [];

    /** @var array<string,Suggestions> */
    private $suggestions = [];

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /**
     * @phpstan-param Client<Client> $client
     * @psalm-param Client $client
     */
    public function __construct(Client $client, EventDispatcherInterface $eventDispatcher)
    {
        $this->client = $client;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getIndex(string $name): Index
    {
        if (!array_key_exists($name, $this->indexes)) {
            $this->indexes[$name] = new Index($name, $this->client);
        }

        return $this->indexes[$name];
    }

    public function getSuggestion(string $name): Suggestions
    {
        if (!array_key_exists($name, $this->indexes)) {
            $this->suggestions[$name] = new Suggestions($name, $this->client);
        }

        return $this->suggestions[$name];
    }

    /**
     * @phpstan-return Client<Client>
     * @psalm-return Client
     */
    public function getRedisClient(): Client
    {
        return $this->client;
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    /**
     * @phpstan-return Client<Client>
     * @psalm-return Client
     */
    public static function createRedisClient(string $dsn): Client
    {
        $dsnData = Dsn::parseFirst($dsn);

        assert($dsnData instanceof Dsn, 'The provided DSN for Redis search is not valid');
        assert('redis' === $dsnData->getSchemeProtocol(), 'The provided DSN for Redis search is not valid');
        assert(is_string($dsnData->getHost()), 'The provided DSN for Redis search is not valid (missing host)');

        $dbIndex = (int) str_replace('/', '', $dsnData->getPath() ?? '/0');

        return new Client([
            'scheme' => 'tcp',
            'host' => $dsnData->getHost(),
            'port' => $dsnData->getPort() ?? 6379,
            'db' => $dbIndex,
            'password' => $dsnData->getPassword() ?? $dsnData->getUser(),
        ]);
    }

    public function getIndexBuilder(): Index\Builder
    {
        return new Index\Builder($this->client);
    }

    public function getSearch(): Search
    {
        return new Search($this->client);
    }

    public function getAggregate(): Aggregate
    {
        return new Aggregate($this->client);
    }
}
