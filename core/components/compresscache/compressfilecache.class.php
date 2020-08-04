<?php

define('DEFAULT_COMPRESS_LVL', 4);
define('CACHE_EXTENSION', '.cache.gz');

/**
 * Реализует сжатие файлов кеша, в остальном принцип работы идентичен xPDOFileCache
 *
 * @package xpdo
 * @subpackage cache
 */
class CompressFileCache extends xPDOCache
{
    private $compressLvl;

    public function __construct(&$xpdo, $options = array())
    {
        parent :: __construct($xpdo, $options);
        $this->initialized = true;

        $defaultCompressLvl = 4;
        $compressLvl = $this->xpdo->getOption('compression_level', null, 4, true);
        if (!is_numeric($compressLvl) || $compressLvl < -1 || $compressLvl > 9) {
            $compressLvl = DEFAULT_COMPRESS_LVL;
        }

        $this->compressLvl = $compressLvl;
    }

    public function getCacheKey($key, $options = array())
    {
        $cachePath = $this->getOption('cache_path', $options);
        $cacheExt = $this->getOption('cache_ext', $options, CACHE_EXTENSION);
        $key = parent :: getCacheKey($key, $options);

        return $cachePath . $key . $cacheExt;
    }

    public function add($key, $var, $expire = 0, $options = array())
    {
        $added = false;
        if (!file_exists($this->getCacheKey($key, $options))) {
            if ($expire === true) {
                $expire = 0;
            }
            $added = $this->set($key, $var, $expire, $options);
        }

        return $added;
    }

    public function set($key, $var, $expire = 0, $options = array())
    {
        $set = false;
        if ($var !== null) {
            if ($expire === true) {
                $expire = 0;
            }
            $expirationTS = $expire ? time() + $expire : 0;
            $expireContent = '';
            if ($expirationTS) {
                $expireContent = 'if(time() > ' . $expirationTS . '){return null;}';
            }

            $fileName = $this->getCacheKey($key, $options);

            $format = (integer) $this->getOption(xPDO::OPT_CACHE_FORMAT, $options, xPDOCacheManager::CACHE_PHP);
            switch ($format) {
                case xPDOCacheManager::CACHE_SERIALIZE:
                    $content = serialize(array('expires' => $expirationTS, 'content' => $var));
                    break;
                case xPDOCacheManager::CACHE_JSON:
                    $content = $this->xpdo->toJSON(array('expires' => $expirationTS, 'content' => $var));
                    break;
                case xPDOCacheManager::CACHE_PHP:
                default:
                    $content = $expireContent . ' return ' . var_export($var, true) . ';';
                    break;
            }

            $content = gzencode($content, $this->compressLvl);

            $folderMode = $this->getOption('new_cache_folder_permissions', $options, false);
            if ($folderMode) {
                $options['new_folder_permissions'] = $folderMode;
            }
            $fileMode = $this->getOption('new_cache_file_permissions', $options, false);
            if ($fileMode) {
                $options['new_file_permissions'] = $fileMode;
            }
            $set= $this->xpdo->cacheManager->writeFile($fileName, $content, 'wb', $options);
        }
        return $set;
    }

    public function replace($key, $var, $expire = 0, $options = array())
    {
        $replaced = false;
        if (file_exists($this->getCacheKey($key, $options))) {
            if ($expire === true) {
                $expire = 0;
            }
            $replaced = $this->set($key, $var, $expire, $options);
        }
        return $replaced;
    }

    public function delete($key, $options = array())
    {
        $deleted= false;
        $cacheKey= $this->getCacheKey($key, array_merge($options, array('cache_ext' => '')));
        if (file_exists($cacheKey) && is_dir($cacheKey)) {
            $results = $this->xpdo->cacheManager->deleteTree(
                $cacheKey,
                array_merge(
                    array(
                        'deleteTop' => false,
                        'skipDirs' => false,
                        'extensions' => array(CACHE_EXTENSION)
                    ),
                    $options
                )
            );

            if ($results !== false) {
                $deleted = true;
            }
        } else {
            $cacheKey = $this->getCacheKey($key, $options);
            if (file_exists($cacheKey)) {
                $deleted = @unlink($cacheKey);
            }
        }
        return $deleted;
    }

    public function get($key, $options = array())
    {
        $value = null;
        $cacheKey = $this->getCacheKey($key, $options);
        if (file_exists($cacheKey)) {
            if ($file = @fopen($cacheKey, 'rb')) {
                $format = (integer) $this->getOption(xPDO::OPT_CACHE_FORMAT, $options, xPDOCacheManager::CACHE_PHP);
                if (flock($file, LOCK_SH)) {
                    switch ($format) {
                        case xPDOCacheManager::CACHE_PHP:
                            if (!filesize($cacheKey)) {
                                $value = false;
                                break;
                            }
                            $value = @eval(gzdecode(stream_get_contents($file)));
                            break;
                        case xPDOCacheManager::CACHE_JSON:
                            $payload = gzdecode(stream_get_contents($file));
                            if ($payload !== false) {
                                $payload = $this->xpdo->fromJSON($payload);
                                if (is_array($payload) && isset($payload['expires']) && (empty($payload['expires']) || time() < $payload['expires'])) {
                                    if (array_key_exists('content', $payload)) {
                                        $value= $payload['content'];
                                    }
                                }
                            }
                            break;
                        case xPDOCacheManager::CACHE_SERIALIZE:
                            $payload = gzdecode(stream_get_contents($file));
                            if ($payload !== false) {
                                $payload = unserialize($payload);
                                if (is_array($payload) && isset($payload['expires']) && (empty($payload['expires']) || time() < $payload['expires'])) {
                                    if (array_key_exists('content', $payload)) {
                                        $value= $payload['content'];
                                    }
                                }
                            }
                            break;
                    }
                    flock($file, LOCK_UN);
                    if ($value === null && $this->getOption('removeIfEmpty', $options, true)) {
                        fclose($file);
                        @unlink($cacheKey);
                        return $value;
                    }
                }
                @fclose($file);
            }
        }
        return $value;
    }

    public function flush($options = array())
    {
        $cacheKey = $this->getCacheKey('', array_merge($options, array('cache_ext' => '')));
        $results = $this->xpdo->cacheManager->deleteTree(
            $cacheKey,
            array_merge(
                array(
                    'deleteTop' => false,
                    'skipDirs' => false,
                    'extensions' => array(CACHE_EXTENSION)
                ),
                $options
            )
        );
        return ($results !== false);
    }
}
