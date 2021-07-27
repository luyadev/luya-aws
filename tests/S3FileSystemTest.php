<?php

namespace luya\aws\test;

use Aws\Arn\Exception\InvalidArnException;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use luya\admin\events\FileEvent;
use luya\admin\models\StorageFile;
use luya\aws\helpers\S3PolicyHelper;
use luya\aws\S3FileSystem;
use luya\testsuite\cases\WebApplicationTestCase;
use luya\testsuite\fixtures\NgRestModelFixture;
use luya\testsuite\traits\AdminDatabaseTableTrait;
use yii\base\ErrorException;

class S3FileSystemTest extends WebApplicationTestCase
{
    use AdminDatabaseTableTrait;

    public function getConfigArray()
    {
        return [
            'id' => 'packagetest',
            'basePath' => __DIR__,
            'language' => 'en',
            'components' => [
                'db' => ['class' => 'yii\db\Connection', 'dsn' => 'sqlite::memory:'],
            ]
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

        $this->assertStringContainsString('foobar.txt?', $s3->presignedUrl('foobar.txt', '10 min'));
        $this->assertStringContainsString('/bucket/foobar.txt', $s3->fileHttpPath('foobar.txt'));
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

    public function testExtendPutObjectExpires()
    {
        $s3 = new S3FileSystem($this->app->request, [
            'region' => 'a',
            'bucket' => 'b',
            'key' => 'c',
        ]);

        $this->assertArrayHasKey('CacheControl', $s3->extendPutObject([]));
        $this->assertSame([
            'bar' => 'foo',
            'CacheControl' => 'max-age=2592000',
        ], $s3->extendPutObject(['bar' => 'foo']));


        $s3->maxAge = false;
        $this->assertArrayNotHasKey('CacheControl', $s3->extendPutObject([]));

        $this->expectException(ErrorException::class);
        $s3->fileSystemSaveFile('x', 'y');
    }

    public function testFileUpdateEvent()
    {
        new NgRestModelFixture([
            'modelClass' => StorageFile::class,
        ]);

        $event = new FileEvent([
            'file' => new StorageFile([
                'name_new_compound' => 'barfoo.jpg'
            ])
        ]);

        $s3 = new S3FileSystem($this->app->request, [
            'region' => 'a',
            'bucket' => 'b',
            'key' => 'c',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $s3->fileUpdateEvent($event);
    }
}