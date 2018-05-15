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
    /**
     * @var string Regions overview: https://docs.aws.amazon.com/general/latest/gr/rande.html
     */
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
            return $this->getClient()->getObject(['Bucket' => $this->bucket, 'Key' => $fileName]);
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
        
        return $this->getClient()->putObject($config);
    }
    
    /**
     * @inheritdoc
     */
    public function fileSystemReplaceFile($oldSource, $newSource)
    {
        
    }
    
    /**
     * @inheritdoc
     */
    public function fileSystemDeleteFile($source)
    {
        
    }
}