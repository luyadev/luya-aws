<?php

namespace luya\aws\test;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use luya\aws\helpers\S3PolicyHelper;
use luya\aws\S3FileSystem;
use luya\testsuite\cases\WebApplicationTestCase;

class PackageTestCase extends WebApplicationTestCase
{
    public function getConfigArray()
    {
        return [
            'id' => 'packagetest',
            'basePath' => __DIR__,
            'language' => 'en',
        ];
    }

    public function testGetConfig()
    {
        $s3 = new S3FileSystem($this->app->request, [
            'bucket' => 'bucket',
            'key' => 'key',
            'secret' => 'secret',
            'region' => 'region',
        ]);

        $this->assertSame([
            'version' => 'latest',
            'region' => 'region',
            'credentials' => [
                'key' => 'key',
                'secret' => 'secret',
            ]
        ], $s3->getS3Config());


        $s3 = new S3FileSystem($this->app->request, [
            'bucket' => 'bucket',
            'key' => 'key',
            'secret' => 'secret',
            'region' => 'region',
            'usePathStyleEndpoint' => true,
            'endpoint' => 'https://localhost:9000',
        ]);

        $this->assertSame([
            'version' => 'latest',
            'region' => 'region',
            'credentials' => [
                'key' => 'key',
                'secret' => 'secret',
            ],
            'use_path_style_endpoint' => true,
            'endpoint' => 'https://localhost:9000',
        ], $s3->getS3Config());
    }

    public function testCreateClient()
    {
        $s3 = new S3FileSystem($this->app->request, [
            'bucket' => 'bucket',
            'key' => 'key',
            'secret' => 'secret',
            'region' => 'region',
            'usePathStyleEndpoint' => true,
            'endpoint' => 'https://localhost:9000',
        ]);

        $this->assertInstanceOf(S3Client::class, $s3->getClient());
    }

    public function testPresignedUrl()
    {
        $s3 = new S3FileSystem($this->app->request, [
            'bucket' => 'bucket',
            'key' => 'key',
            'secret' => 'secret',
            'region' => 'region',
            'usePathStyleEndpoint' => true,
            'endpoint' => 'https://localhost:9000',
        ]);

        $this->assertContains('foobar.txt?', $s3->presignedUrl('foobar.txt', '10 min'));
    }

    public function testUpdateBucket()
    {
        $s3 = new S3FileSystem($this->app->request, [
            'bucket' => 'bucket',
            'key' => 'key',
            'secret' => 'secret',
            'region' => 'region',
            'usePathStyleEndpoint' => true,
            'endpoint' => 'https://localhost:9000',
        ]);

        $this->expectException(S3Exception::class);
        $s3->updateBucketPolicy(S3PolicyHelper::S3_POLICY_PUBLIC_READ);
    }
}