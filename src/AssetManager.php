<?php

namespace luya\aws;

use luya\web\AssetManager as WebAssetManager;
use Yii;

/**
 * 
 * @see Inspiration taken from https://gitlab.com/mikk150/yii2-asset-manager-flysystem
 */
class AssetManager extends WebAssetManager
{
    public $basePath = 'assets';

    public $baseUrl = 'https://cdn.luya.io';

    //'basePath' => './',
    //'baseUrl' => '//cdn.host.com',

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
        return [$dstFile, $this->baseUrl . $dstDir];
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

        if (!Yii::$app->storage->fileSystemExists($dstDir)) {
            Yii::$app->storage->folderTransfer($src, $dstDir);
        }

        // the path directory and the URL that the asset is published as.
        return [$dstDir, $this->baseUrl . '/' . $dir];
    }

}