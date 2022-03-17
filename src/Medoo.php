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


use Workerman\Timer;

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
     * @var \Medoo\Medoo
     */
    protected static $instances = [];

    /**
     * @return \Medoo\Medoo
     */
    public static function instance($name = 'default')
    {
        if (!isset(static::$instances[$name])) {
            $config = config('plugin.webman.medoo.database');
            static::$instances[$name] = new \Medoo\Medoo($config[$name]);
            Timer::add(55, function () use ($name) {
                static::$instances[$name]->query('select 1')->fetchAll();
            });
        }
        return static::$instances[$name];
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
