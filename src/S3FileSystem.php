<?php

namespace luya\aws;

use Aws\Result;
use Yii;
use luya\admin\storage\BaseFileSystemStorage;
use yii\base\InvalidConfigException;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use luya\admin\events\FileEvent;
use luya\admin\Module;
use luya\helpers\FileHelper;
use luya\helpers\StringHelper;

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
     * @var boolean If defined the s3 config `use_path_style_endpoint` will recieve this value. This should be set to true when working with minio storage.
     * @since 1.1.0
     */
    public $usePathStyleEndpoint;

    /**
     * @var string If defined the s3 config `endpoint` will recieve this value. Example `http://localhost:9000` for minio usage
     * @since 1.1.0
     */
    public $endpoint;
    
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        
        if ($this->region === null || $this->bucket === null || $this->key === null) {
            throw new InvalidConfigException("region, bucket and key must be provided for s3 component configuration.");
        }

        $this->on(self::FILE_UPDATE_EVENT, function(FileEvent $event) {
            // update the disposition

            /*
            // setup your $s3 connection, and define the bucket and key for your resource.
            $s3->copyObject(array(
            'Bucket' => $bucket,
            'CopySource' => "$bucket/$key",
            'Key' => $key,
            'Metadata' => array(
                'ExtraHeader' => 'HEADER VALUE'
            ),
            'MetadataDirective' => 'REPLACE'
            ));
*/
        });
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
            $this->_client = new S3Client($this->getS3Config());
        }
        
        return $this->_client;
    }

    /**
     * Returns the S3 Client Config Array
     *
     * @return array
     * @since 1.1.0
     */
    public function getS3Config()
    {
        $config = [
            'version' => 'latest',
            'region' => $this->region,
            'credentials' => [
                'key' => $this->key,
                'secret' => $this->secret,
            ]
        ];

        if ($this->usePathStyleEndpoint !== null) {
            $config['use_path_style_endpoint'] = $this->usePathStyleEndpoint;
        };

        if ($this->endpoint !== null) {
            $config['endpoint'] = $this->endpoint;
        }

        return $config;
    }
    
    private $_httpPaths = [];
    
    /**
     * @inheritdoc
     */
    public function fileHttpPath($fileName)
    {
        if (!isset($this->_httpPaths[$fileName])) {
            Yii::debug('Get S3 object url: ' . $fileName, __METHOD__);
            $this->_httpPaths[$fileName] = $this->getClient()->getObjectUrl($this->bucket, $fileName);
        }
        
        return $this->_httpPaths[$fileName];
    }
    
    /**
     * Generate a presigned download url for a private object f.e
     *
     * @param string $fileName The filename
     * @param string $time An example for 10 minutes would be `+10 minutes`
     * @return string
     * @since 1.2.0
     */
    public function presignedUrl($fileName, $time)
    {
        // Get a command object from the client
        $command = $this->getClient()->getCommand('GetObject', [
            'Bucket' => $this->bucket,
            'Key'    => $fileName
        ]);

        // Create a pre-signed URL for a request with duration of 10 miniutes
        $presignedRequest = $this->getClient()->createPresignedRequest($command, $time);

        // Get the actual presigned-url
        return (string) $presignedRequest->getUri();
    }

    /**
     * Update Bucket Policy
     *
     * @param string $policy
     * @return Result
     * @since 1.2.0
     */
    public function updateBucketPolicy($policy)
    {
        // Configure the policy
        return $this->getClient()->putBucketPolicy([
            'Bucket' => $this->bucket,
            'Policy' => StringHelper::template($policy, ['bucket' => $this->bucket]),
        ]);
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
        try {
            $object = $this->client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $fileName,
            ]);
            
            if ($object) {
                return $object['Body'];
            }
        } catch (S3Exception $e) {
            return false;
        }
        
        return false;
    }
    
    /**
     * @inheritdoc
     */
    public function fileSystemExists($fileName)
    {
        return $this->client->doesObjectExist($this->bucket, $fileName);
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
            'ContentType' => FileHelper::getMimeType($source),
        ];

        if (!Module::getInstance()->fileDefaultInlineDisposition) {
            $config['ContentDisposition'] = 'attachement'; // inline is default setting
        } else {
            // inline; filename="$fileName"
        }
        
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
        return (bool) $this->client->deleteObject([
            'Bucket' => $this->bucket,
            'Key' => $fileName,
        ]);
    }
}
