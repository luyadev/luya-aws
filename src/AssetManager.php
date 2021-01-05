<?php

namespace luya\aws;

use luya\helpers\Inflector;
use luya\traits\CacheableTrait;
use luya\web\AssetManager as WebAssetManager;
use Yii;
use yii\base\Component;

/**
 * An S3 compatible Asset Manager.
 *
 * Setup the AssetManager as component in your config. Of course ensure that luya aws storage is setup as storage system. See {{S3FileSystem}}.
 *
 * ```php
 * 'assetManager' => [
 *     'class' => 'luya\aws\AssetManager',
 * ],
 * ```
 *
 * > WOFF/FONT needs a valid CORS request to be loaded from a remote CDN!
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
 * @since 1.4.0
 * @author Basil Suter <git@nadar.io>
 */
class AssetManager extends WebAssetManager
{
    use CacheableTrait;

    /**
     * All assets will be stored using this path inside the bucket, for root storage use null
     *
     * @var string
     */
    public $basePath = 'assets';

    /**
     * {@inheritDoc}
     */
    public function init()
    {
        $this->hashCallback = function ($path) {
            return sprintf('%x', crc32($path . Yii::getVersion()));
        };
    }

    /**
     * Override base path permission check as this check should not be done.
     *
     * @return void
     */
    public function checkBasePathPermission()
    {
    }

    private $_generatedBasePath;

    /**
     * Generate the base path including the version and timestamp of the vendor
     *
     * @return string
     */
    public function generateBasePath()
    {
        if (!$this->_generatedBasePath) {
            $this->_generatedBasePath = $this->basePath . DIRECTORY_SEPARATOR . Inflector::slug(Yii::$app->formatter->asDatetime(Yii::$app->packageInstaller->timestamp, 'yyyyMMddHHmmss') . '-' . Yii::$app->version) . DIRECTORY_SEPARATOR;
        }

        return $this->_generatedBasePath;
    }

    /**
     * Publish a given file with its directory
     *
     * {@inheritDoc}
     */
    protected function publishFile($src)
    {
        $dir = $this->hash($src);
        $fileName = basename($src);
        $dstDir = $this->generateBasePath() . DIRECTORY_SEPARATOR . $dir; // assets/<hash>
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
     * {@inheritDoc}
     */
    protected function publishDirectory($src, $options)
    {
        $dir = $this->hash($src);
        $dstDir = $this->generateBasePath() . DIRECTORY_SEPARATOR . $dir; // assets/<hash>

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
