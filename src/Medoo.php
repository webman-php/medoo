<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Webman\Medoo;


use Webman\Context;
use Workerman\Coroutine\Pool;

/**
 * Class Medoo
 * @package Webman\Medoo
 *
 * @method static array select(string $table, array $columns, array $where)
 * @method static mixed get(string $table, array|string $columns, array $where)
 * @method static bool has(string $table, array $where)
 * @method static mixed rand(string $table, array|string $column, array $where)
 * @method static int count(string $table, array $where)
 * @method static int max(string $table, string $column)
 * @method static int min(string $table, string $column)
 * @method static int avg(string $table, string $column)
 * @method static int sum(string $table, string $column)
 */
class Medoo
{

    /**
     * @var Pool[]
     */
    protected static array $pools = [];

    /**
     * @return \Medoo\Medoo
     */
    public static function instance($name = 'default')
    {
        $config = config('plugin.webman.medoo.database');
        $key = "database.connections.$name";
        $connection = Context::get($key);
        if (!$connection) {
            if (!isset(static::$pools[$name])) {
                $poolConfig = $config[$name]['pool'] ?? [];
                $pool = new Pool($poolConfig['max_connections'] ?? 6, $poolConfig);
                $pool->setConnectionCreator(function () use ($config, $name) {
                    return new \Medoo\Medoo($config[$name]);
                });
                $pool->setConnectionCloser(function ($connection) {
                    $connection->pdo = null;
                });
                $pool->setHeartbeatChecker(function ($connection) {
                    $connection->query('select 1')->fetchAll();
                });
                static::$pools[$name] = $pool;
            }
            try {
                $connection = static::$pools[$name]->get();
                Context::set($key, $connection);
            } finally {
                // We cannot use Coroutine::defer() because we may not be in a coroutine environment currently.
                Context::onDestroy(function () use ($connection, $name) {
                    try {
                        $connection && static::$pools[$name]->put($connection);
                    } catch (Throwable) {
                        // ignore
                    }
                });
            }
        }
        return $connection;
    }


    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return static::instance()->{$name}(... $arguments);
    }
}
