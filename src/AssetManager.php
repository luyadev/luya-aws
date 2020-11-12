<?php

namespace luya\aws;

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
 * @see Inspiration taken from https://gitlab.com/mikk150/yii2-asset-manager-flysystem
 */
class AssetManager extends WebAssetManager
{
    /**
     * All assets will be stored using this path inside the bucket, for root storage use null
     *
     * @var string
     */
    public $basePath = 'assets'; // TODO does only work by accident... https://forum.yiiframework.com/t/publish-assetmanager-files-to-cdn/130920

    public function init()
    {

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

        if (!Yii::$app->storage->fileSystemExists($dstFile)) {
            Yii::$app->storage->fileSystemSaveFile($src, $dstFile);
        }

        // the path and the URL that the asset is published as.
        return [$dstFile, Yii::$app->storage->fileHttpPath($dstFile)];
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

        $forceCopy = $this->forceCopy && (isset($options['forceCopy']) && $options['forceCopy']);

        if ($forceCopy || !Yii::$app->storage->fileSystemFolderExists($dstDir)) {
            Yii::$app->storage->folderTransfer($src, $dstDir);
        }

        // the path directory and the URL that the asset is published as.
        return [$dstDir, Yii::$app->storage->fileHttpPath($dstDir)];
    }
}