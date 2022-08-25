<?php


namespace builderScripts\inc;

use builderScripts\BuilderConfigs;
use Exception;
use Memcached;
use Redis;

// Берет скрипты и стили из папки bundles и подключает их в тему на WP

class AdderBundles
{
    private string $dir;
    private string $filepath;
    private array $getConditionsList;
    private array $getConfig;

    public function __construct()
    {
        $this->dir = get_template_directory();
        $this->filepath = $this->dir . '/builder-scripts/stats/stats.json';
        $this->getConditionsList = BuilderConfigs::getConditionsList();
        $this->getConfig = BuilderConfigs::getConfig();
    }

    /**
     * Генерирует построчное чтения файла
     */
    private function read_the_file(): \Generator
    {
        $handle = fopen($this->filepath, "r");

        while (!feof($handle)) {
            yield trim(fgets($handle));
        }

        fclose($handle);
    }

    /**
     * @return mixed
     * Находит нужную нам часть stats.json
     */
    private function find_chunks_path()
    {
        $iterator = $this->read_the_file();
        $buffer = "";
        $start = false;

        foreach ($iterator as $iteration) {

            if ($iteration == '"assetsByChunkName": {') {
                $start = true;
            }

            if ($iteration == '"assets": [') {
                break;
            }

            if ($start) {
                $buffer .= $iteration . PHP_EOL;
            }
        }

        if ($buffer) {
            $buffer = '{' . substr($buffer, 0, -2) . '}';
        } else {
            wp_die('Необходимо перезапустить Webpack либо скрипты или стили данной страницы не настроенны');
        }

        return json_decode($buffer);
    }

    /**
     * Получаем массив названий всех типов файлов по ключу страницы
     */
    private function get_source_names($keyPage): array
    {
        $allFiles = [];
        $chunks = $this->find_chunks_path()->assetsByChunkName;

        foreach ($chunks as $key => $value) {

            $haveNeedKey = strripos($key, $keyPage);

            if(!is_array($value)){
                $arrFix[] = $value;
                $value = $arrFix;
            }

            if ($haveNeedKey !== false) {
                $allFiles = array_merge($allFiles, $value);
            }

        }

        return array_reverse($allFiles);
    }

    /**
     * Получаем массив названий файлов по типу файла и ключу страницы
     */
    private function get_source_by_type($keyPage, $type): array
    {
        $files = [];
        $allFiles = $this->get_source_names($keyPage);

        foreach ($allFiles as $fileName) {

            $fileType = substr(strrchr($fileName, '.'), 1);

            if ($fileType == $type) {
                $files[] = $fileName;
            }
        }
        return array_unique($files);
    }

    /**
     * Создаем набор подключений стилий или скриптов по типу и ключу страницы
     */
    private function create_links($keyPage, $type): string
    {
        $links = '';
        $allFiles = $this->get_source_by_type($keyPage, $type);

        foreach ($allFiles as $element) {

            switch ($type) {
                case 'css':
                    $links .= '&lt;link rel="stylesheet" href="' . get_template_directory_uri() . '/bundles/' . $element . '"&gt;';
                    break;
                case'js':
                    $links .= '&lt;script type="text/javascript" src="' . get_template_directory_uri() . '/bundles/' . $element . '"&gt;&lt;/script&gt;';
                    break;
                default:
                    wp_die('Необходимо перезапустить Webpack либо скрипты или стили данной страницы не настроенны');
            }

        }

        return $links;
    }

    /**
     * Получаем Ключь страницы по ее ID
     */
    private function get_page_keys($idCurrentPage): array
    {
        $result = [];
        $array_condition = $this->getConditionsList;

        foreach ($array_condition as $keyPage => $item) {
            if (in_array(-1, $item) || in_array(true, $item) || in_array($idCurrentPage, $item)) {
                $result[] = $keyPage;
            }
        }

        return $result;
    }


    /**
     * Получаем необходимые подключения по типу и ID страницы обычным способом
     */
    private function require_css_js($type, $idCurrentPage): string
    {
        $links = '';
        $pageKeys = $this->get_page_keys($idCurrentPage);

        foreach ($pageKeys as $keyPage) {
            $links .= htmlspecialchars_decode($this->create_links($keyPage, $type));
        }

        return $links;
    }

    /**
     * Проверка подключения к Redis
     */
    private function checked_redis()
    {
        $config = $this->getConfig;

        if (class_exists('Redis') && $config['RedisRun'] && $config['RedisHost']) {

            try {
                $cached = new Redis();
                @$cached->connect($config['RedisHost']);
                $cached->set('test-data', '1');

                $isCacheAvailable = $cached->get('test-data');
                if (!$isCacheAvailable) {
                    return false;
                }

            } catch (Exception $e) {
                if ($config['HardCheck']) {
                    wp_die('Redis: '.$e->getMessage());
                }
            }

        } else {
            return false;
        }
        return $cached;

    }

    /**
     * Проверка подключения к Memcached
     */
    private function checked_memcached()
    {

        $config = $this->getConfig;

        if (class_exists('Memcached') && $config['MemcachedRun'] && $config['MemcachedHost']) {

            try {
                $cached = new Memcached();
                @$cached->addServer($config['MemcachedHost'], $config['MemcachedPort']);

                $cached->set('test-data', '1');
                $isCacheAvailable = $cached->get('test-data');

                if (!$isCacheAvailable) {
                    return false;
                }

            } catch (Exception $e) {
                if ($config['HardCheck']) {
                    wp_die('Memcached: '.$e->getMessage());
                }
            }

        } else {
            return false;
        }
        return $cached;

    }

    /**
     * Проверка кокой кэш использовать
     */
    private function checked_cached()
    {

        $config = $this->getConfig;
        $cached = false;

        if ($this->checked_redis()) {
            $cached = $this->checked_redis();
        } elseif ($this->checked_memcached()) {
            $cached = $this->checked_memcached();
        } else {

            if ($config['HardCheck']) {
                wp_die('Неправильные настройки Redis или Memcache');
            }

        }

        return $cached;
    }

    /**
     * Получаем необходимые подключения по типу и ID страницы используя Redis
     */
    private function require_css_js_cached($type, $idCurrentPage): string
    {
        $links = '';
        $pageKeys = $this->get_page_keys($idCurrentPage);

        $cached = $this->checked_cached();

        foreach ($pageKeys as $keyPage) {

            if ($cached) {

                $cachedKey = $keyPage . '-' . $type;
                $cachedResult = $cached->get($cachedKey);

                if ($cachedResult) {
                    $links .= $cachedResult;
                } else {
                    $link = htmlspecialchars_decode($this->create_links($keyPage, $type));
                    $links .= $link;
                    $cached->set($cachedKey, $link);
                }

            } else {
                $link = htmlspecialchars_decode($this->create_links($keyPage, $type));
                $links .= $link;
            }
        }


        return $links;
    }


    /**
     * Проверяем включено ли использование Redis или Memcached
     */
    private function checked_optimization($type, $idCurrentPage): string
    {
        $config = $this->getConfig;

        if ($config['RedisRun'] || $config['MemcachedRun']) {
            $source = $this->require_css_js_cached($type, $idCurrentPage);
        } else {
            $source = $this->require_css_js($type, $idCurrentPage);
        }

        return $source;
    }

    /**
     * выводит скрипты или стили по типу и ID страницы
     */
    public function print_source($type, $idCurrentPage)
    {
        $source = $this->checked_optimization($type, $idCurrentPage);

        if ($source) {
            echo $source;
        } else {
            wp_die('Необходимо перезапустить Webpack либо скрипты или стили данной страницы не настроенны');
        }
    }

}
