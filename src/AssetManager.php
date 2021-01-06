<?php

namespace luya\aws;

use luya\helpers\Inflector;
use luya\traits\CacheableTrait;
use luya\web\AssetManager as WebAssetManager;
use Yii;

/**
 * An S3 compatible Asset Manager.
 * 
 * Saves all the files in the s3 system and serves them from the CDN url. Uploading is done once, afterwards urls are served from the cache
 * which makes it fast and no disk io is required!
 *
 * > Using this AssetManager requires you to correctly adjust the app version for each deployment or building the app with a CI system
 * > which runns composer install before each deployment (building of the docker images). It also requires an enabled cache system otherwise
 * > this implementation is rather slow compared to the original AssetManager.
 * 
 * By default, an asset folder and its version folder will be created when receiving the first web request. The version folder is based
 * on the composer.lock file timestamp (Yii::$app->packageInstaller->timestamp) and the application version (Yii::$app->version). Afterwarts
 * the path to the uploaded asset file will be stored in the cache, it is therfore required to have caching enabled. When you scale with multiple
 * instances of the same website, ensure the caching system shares its data inbetween.
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
 * @property string $versionPath The version path which should be used between builds. By defaults its a combination of 
 * the vendor timestamp and the Yii::$app->version. The versionPath will be append as folder like `assets/<VERSION_PATH>/1hf3ufh`.
 * Where `assets` is taken from the $basePath property.
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
            return sprintf('%x', crc32($path));
        };
    }

    private $_versionPath;

    /**
     * Setter method of the path name
     * 
     * An example using only the app version would be:
     * 
     * ```php
     * 'versionPath' => function() {
     *    return Yii::$app->version;
     * }
     * ```
     * 
     * @param string|callable $path
     */
    public function setVersionPath($path)
    {
        if (is_callable($path)) {
            $path = call_user_func($path);
        }

        $this->_versionPath = $path;
    }

    /**
     * Getter method for versionPath
     *
     * @return string
     */
    public function getVersionPath()
    {
        if ($this->_versionPath === null) {
            $this->_versionPath = Inflector::slug(Yii::$app->formatter->asDatetime(Yii::$app->packageInstaller->timestamp, 'yyyyMMddHHmmss') . '-' . Yii::$app->version);
        }

        return $this->_versionPath;
    }

    /**
     * Override base path permission check as this check should not be done.
     *
     * @return void
     */
    public function checkBasePathPermission()
    {
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
        $dstDir = $this->basePath . DIRECTORY_SEPARATOR . $this->getVersionPath() . DIRECTORY_SEPARATOR . $dir; // assets/<versionpath>/<hash>
        $dstFile = $dstDir . DIRECTORY_SEPARATOR . $fileName; // assets/<versionpath>/<hash>/jquery.js

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
        $dstDir = $this->basePath . DIRECTORY_SEPARATOR . $this->getVersionPath() . DIRECTORY_SEPARATOR . $dir; // assets/<versionpath>/<hash>

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
