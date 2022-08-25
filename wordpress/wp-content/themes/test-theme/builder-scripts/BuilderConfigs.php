<?php

namespace builderScripts;

// Содержит настройки работы скрипта подключения стилей

class BuilderConfigs
{
    /**
     * Зависимости Ключей скриптов к условию их вывода
     * Варианты условий:
     * "-1" - Подключить на всех страницах
     * "ID" - Можно указать ID конкретной страницы
     * "is_front_page()" - Условие проверки страницы
     */
    public static function getConditionsList(): array
    {
        return [
            'main' => [-1]
        ];
    }

    /**
     * Конфигурации оптимизации работы подключения скриптов
     * Для активации Redis необходимо задать RedisRun => true и указать RedisHost
     * Для активации Memcached необходимо задать MemcachedRun => true и указать MemcachedHost, MemcachedPort
     * Если оба параметра активны то приоритет будет у Redis
     * HardCheck - жесткая проверка работы кэширования, если отключить то не подключившись скрипт будет работать в стандартном режиме без кэша
     */
    public static function getConfig(): array
    {
        return [
            'HardCheck' => false,
            'RedisRun' => false,
            'RedisHost' => 'redis-new-boosta-biz',
            'MemcachedRun' => false,
            'MemcachedHost' => 'Memcached',
            'MemcachedPort' => 11211
        ];
    }
}