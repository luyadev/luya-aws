<?php

namespace luya\amazons3;

use luya\admin\storage\BaseFileSystemStorage;
use yii\base\InvalidConfigException;
use Aws\S3\S3Client;

/**
 * Amazon S3 Bucket Filesystem.
 * 
 * Use Amazon S3 Bucket for LUYA Admin Filesystem:
 * 
 * ```php
 * 'storage' => [
 *     'class' => 'luya\amazons3\S3FileSystem',
 *     'bucket' => 'BUCKET_NAME',
 *     'key' => 'KEY',
 *     'secret' => 'SECRET',
 *     'region' => 'eu-central-1',
 * ]
 * ```
 * 
 * @author Basil Suter <basil@nadar.io>
 * @since 1.0.0
 */
class S3FileSystem extends BaseFileSystemStorage
{
    public $bucket;
    public $key;
    public $secret;
    public $region;
    
    public $acl = 'public-read';
    
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        
        if ($this->region === null || $this->bucket === null || $this->key === null) {
            throw new InvalidConfigException("region, bucket and key must be provided for s3 component configuration.");
        }
    }
    
    private $_client;
    
    /**
     * Get the Amazon client library.
     *
     * @return \Aws\S3\S3Client
     */
    public function getClient()
    {
        if ($this->_client === null) {
            $this->_client = new S3Client(['version' => 'latest', 'region' => $this->region, 'credentials' => ['key' => $this->key, 'secret' => $this->secret]]);
        }
        
        return $this->_client;
    }
    
    /**
     * Get the base path to the storage directory.
     *
     * @return string Get the relative http path to the storage folder if nothing is provided by the setter method `setHttpPath()`.
     */
    public function getHttpPath()
    {
        return "https://{$this->bucket}.s3-{$this->region}.amazonaws.com";
    }
    
    /**
     * Get the base absolute base path to the storage directory.
     *
     * @return string Get the absolute http path to the storage folder if nothing is provided by the setter method `setAbsoluteHttpPath()`.
     */
    public function getAbsoluteHttpPath()
    {
        return $this->getHttpPath();   
    }
    
    /**
     * Get the internal server path to the storage folder.
     *
     * Default path is `@webroot/storage`.
     *
     * @return string Get the path on the server to the storage folder based @webroot alias.
     */
    public function getServerPath()
    {
        return $this->getHttpPath();   
    }
    
    
    /**
     * Save the given file source as a new file with the given fileName on the filesystem.
     *
     * @param string $source The absolute file source path and filename, like `/tmp/upload/myfile.jpg`.
     * @param string $fileName The new of the file on the file system like `MyNewFile.jpg`.
     * @return boolean Whether the file has been stored or not.
     */
    public function fileSystemSaveFile($source, $fileName)
    {
        $config = [
            'ACL' => $this->acl,
            'Bucket' => $this->bucket,
            'Key' => $fileName,
            'SourceFile' => $source,
        ];
        
        return $this->getClient()->putObject($config);
    }
    
    /**
     * Replace an existing file source with a new one on the filesystem.
     *
     * @param string $oldSource The absolute file source path and filename, like `/tmp/upload/myfile.jpg`.
     * @param string $newSource The absolute file source path and filename, like `/tmp/upload/myfile.jpg`.
     * @return boolean Whether the file has replaced stored or not.
     */
    public function fileSystemReplaceFile($oldSource, $newSource)
    {
        
    }
    
    /**
     * Delete a given file source on the filesystem.
     * @param string $source The absolute file source path and filename, like `/tmp/upload/myfile.jpg`.
     * @return boolean Whether the file has been deleted or not.
     */
    public function fileSystemDeleteFile($source)
    {
        
    }
}