<?php

namespace luya\aws;

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
 *     'class' => 'luya\aws\S3FileSystem',
 *     'bucket' => 'BUCKET_NAME',
 *     'key' => 'KEY',
 *     'secret' => 'SECRET',
 *     'region' => 'eu-central-1',
 * ]
 * ```
 * 
 * @property \Aws\S3\S3Client $client The AWS SDK S3 Client.
 * 
 * @author Basil Suter <basil@nadar.io>
 * @since 1.0.0
 */
class S3FileSystem extends BaseFileSystemStorage
{
    /**
     * @var string Contains the name of the bucket defined on amazon webservice.
     */
    public $bucket;
    
    /**
     * @var string The authentiication key in order to connect to the s3 bucket.
     */
    public $key;
    
    /**
     * @var string The authentification secret in order to connect to the s3 bucket.
     */
    public $secret;
    
    /**
     * @var string Regions overview: https://docs.aws.amazon.com/general/latest/gr/rande.html
     */
    public $region;
    
    /**
     * @var string The ACL default permission when writing new files.
     */
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
     * @inheritdoc
     */
    public function fileHttpPath($fileName)
    {
        return $this->getClient()->getObjectUrl($this->bucket, $fileName);
    }
    
    /**
     * @inheritdoc
     */
    public function fileAbsoluteHttpPath($fileName)
    {
        return $this->fileHttpPath($fileName);   
    }
    
    /**
     * @inheritdoc
     */
    public function fileServerPath($fileName)
    {
        return $this->fileHttpPath($fileName);
    }
    
    /**
     * @inheritdoc
     */
    public function fileSystemContent($fileName)
    {
        $object = $this->fileSystemExists($fileName);
        
        if ($object) {
            return $object['Body'];
        }
    }
    
    /**
     * @inheritdoc
     */
    public function fileSystemExists($fileName)
    {
        try {
            return $this->client->getObject(['Bucket' => $this->bucket, 'Key' => $fileName]);
        } catch (\Aws\S3\Exception\S3Exception $e) {
            return false;
        }
    }
    
    
    /**
     * @inheritdoc
     */
    public function fileSystemSaveFile($source, $fileName)
    {
        $config = [
            'ACL' => $this->acl,
            'Bucket' => $this->bucket,
            'Key' => $fileName,
            'SourceFile' => $source,
        ];
        
        return $this->client->putObject($config);
    }
    
    /**
     * @inheritdoc
     */
    public function fileSystemReplaceFile($fileName, $newSource)
    {
        return $this->fileSystemSaveFile($newSource, $fileName);
    }
    
    /**
     * @inheritdoc
     */
    public function fileSystemDeleteFile($fileName)
    {
        return (bool) $this->client->deleteObject(['Bucket' => $this->bucket, 'Key' => $fileName]);
    }
}
