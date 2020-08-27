<?php

namespace luya\aws\test;

use Aws\S3\Exception\S3Exception;
use luya\aws\commands\S3Command;
use luya\aws\helpers\S3PolicyHelper;
use luya\aws\S3FileSystem;
use luya\testsuite\cases\ConsoleApplicationTestCase;

class S3CommandTest extends ConsoleApplicationTestCase
{
    public function getConfigArray()
    {
        return [
            'id' => 'packagetest',
            'basePath' => __DIR__,
            'language' => 'en',
            'components' => [
                'storage' => [
                    'class' => S3FileSystem::class,
                    'bucket' => 'bucket',
                    'key' => 'key',
                    'secret' => 'secret',
                    'region' => 'region',
                ],
            ]
        ];
    }

    public function testUpdatePolicyCommand()
    {
        $controller = new S3Command('s3', $this->app);

        $this->expectException(S3Exception::class);
        $controller->actionApplyPolicy(S3PolicyHelper::S3_POLICY_PUBLIC_READ);
    }
}