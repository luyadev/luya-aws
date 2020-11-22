<?php

namespace luya\aws;

use luya\traits\CacheableTrait;
use luya\web\AssetManager as WebAssetManager;
use Yii;
use yii\base\Component;

/**
 * Usage
 * 
 * ```php
 * 'assetManager' => [
 *     'class' => 'luya\aws\AssetManager',
 * ],
 * ```
 * 
 * > WOFF/FONT needs a valid cors request to be loaded from remote!
 * 
 * CORS Policy:
 * 
 * ```json
 * [
 *   {
 *       "AllowedHeaders": [
 *           "*"
 *       ],
 *       "AllowedMethods": [
 *           "GET",
 *           "POST"
 *       ],
 *       "AllowedOrigins": [
 *           "http://luyaenvdev-web-luya-env-dev.dev.zephir.ch",
 *           "*",
 *           "localhost"
 *       ],
 *       "ExposeHeaders": [],
 *       "MaxAgeSeconds": 3000
 *   }
 * ]
 * ```
 * 
 * Compared to the original AssetManger the following properties has no effect!
 * 
 * + hashCallback
 * + linkAssets
 * + dirMode
 * + fileMode
 * + beforeCopy
 * 
 * @see Inspiration taken from https://gitlab.com/mikk150/yii2-asset-manager-flysystem
 */
class AssetManager extends WebAssetManager
{
    use CacheableTrait;

    /**
     * All assets will be stored using this path inside the bucket, for root storage use null
     *
     * @var string
     */
    public $basePath = 'assets'; // TODO does only work by accident... https://forum.yiiframework.com/t/publish-assetmanager-files-to-cdn/130920

    public function init()
    {
        $this->hashCallback = function($path) {
            return sprintf('%x', crc32($path . Yii::getVersion() . '|' . Yii::$app->packageInstaller->timestamp));
        };
    }

    //'basePath' => './',

    /**
     * Publish a given file with its directory 
     */
    protected function publishFile($src)
    {
        $dir = $this->hash($src);
        $fileName = basename($src);
        $dstDir = $this->basePath . DIRECTORY_SEPARATOR . $dir; // assets/<hash>
        $dstFile = $dstDir . DIRECTORY_SEPARATOR . $fileName; // assets/<hash>/jquery.js

        if ($cached = $this->isCached($this->forceCopy, $dstFile)) {
            return $cached;
        }

        if ($this->forceCopy || !Yii::$app->storage->fileSystemExists($dstFile)) {
            Yii::$app->storage->fileSystemSaveFile($src, $dstFile);
        }

        // the path and the URL that the asset is published as.
        return $this->setCached($this->forceCopy, $dstFile, Yii::$app->storage->fileHttpPath($dstFile));
    }

    /**
     * Publish a directly with all its file
     *
     * @param [type] $src
     * @param [type] $options
     * @return void
     */
    protected function publishDirectory($src, $options)
    {
        $dir = $this->hash($src);
        $dstDir = $this->basePath . DIRECTORY_SEPARATOR . $dir; // assets/<hash>

        $forceCopy = $this->forceCopy || (isset($options['forceCopy']) && $options['forceCopy']);

        if ($cached = $this->isCached($forceCopy, $dstDir)) {
            return $cached;
        }

        if ($forceCopy || !Yii::$app->storage->fileSystemFolderExists($dstDir)) {
            Yii::$app->storage->folderTransfer($src, $dstDir);
        }

        return $this->setCached($forceCopy, $dstDir, Yii::$app->storage->fileHttpPath($dstDir));
    }

    /**
     * Check if a cacheable value exists and is valid.
     *
     * @param boolean $forceCopy
     * @param string $dst
     * @return boolean|array
     */
    private function isCached($forceCopy, $dst)
    {
        if ($forceCopy) {
            return false;
        }

        $cacheValue = $this->getHasCache(['assetManager', $dst]);

        if (!$cacheValue) {
            return false;
        }

        return [$dst, $cacheValue];
    }

    /**
     * Set the values into cache if allowed and return expected format
     *
     * @param boolean $forceCopy
     * @param string $dst
     * @param string $cdnPath
     * @return array
     */
    private function setCached($forceCopy, $dst, $cdnPath)
    {
        if (!$forceCopy) {
            $this->setHasCache(['assetManager', $dst], $cdnPath, null, 0);
        }

        return [$dst, $cdnPath];
    }
}