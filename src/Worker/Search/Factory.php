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

use function array_intersect;
use function assert;
use function count;
use Ehann\RedisRaw\PhpRedisAdapter;
use Ehann\RedisRaw\PredisAdapter;
use Ehann\RedisRaw\RedisClientAdapter;
use Ehann\RedisRaw\RedisRawClientInterface;
use Enqueue\Dsn\Dsn;
use function is_string;
use function reset;
use function str_replace;

class Factory
{
    private const SUPPORTED_REDIS_CLIENT = ['predis', 'phpredis', 'redis-client'];

    public static function createRedisClient(string $dsn): RedisRawClientInterface
    {
        $dsnData = Dsn::parseFirst($dsn);

        assert($dsnData instanceof Dsn, 'The provided DSN for Redis search is not valid');
        assert('redis' === $dsnData->getSchemeProtocol(), 'The provided DSN for Redis search is not valid');
        assert(is_string($dsnData->getHost()), 'The provided DSN for Redis search is not valid (missing host)');

        $matching = array_intersect(self::SUPPORTED_REDIS_CLIENT, $dsnData->getSchemeExtensions());
        if (0 === count($matching)) {
            $matching = ['predis'];
        }
        $implementation = reset($matching);
        switch ($implementation) {
            case 'phpredis':
                $client = new PhpRedisAdapter();

                break;
            case 'redis-client':
                $client = new RedisClientAdapter();

                break;
            case 'predis':
            default:
                $client = new PredisAdapter();
        }

        $dbIndex = (int) str_replace('/', '', $dsnData->getPath() ?? '/0');

        $client->connect($dsnData->getHost(), $dsnData->getPort() ?? 6379, $dbIndex, $dsnData->getPassword() ?? $dsnData->getUser());

        return $client;
    }
}
