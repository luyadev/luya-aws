<?php

namespace luya\aws\test;

use luya\aws\AssetManager;
use luya\testsuite\cases\WebApplicationTestCase;
use luya\testsuite\traits\AdminDatabaseTableTrait;

class AssetManagerTest extends WebApplicationTestCase
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

    public function testIsCached()
    {
        $manager = new AssetManager();
        
        $this->assertFalse($this->invokeMethod($manager, 'isCached', [false, 'dist']));
        $this->assertFalse($this->invokeMethod($manager, 'isCached', [true, 'dist']));
    }

    public function testSetCached()
    {
        $manager = new AssetManager();
        
        $this->assertSame(['dist', 'cdnpath'], $this->invokeMethod($manager, 'setCached', [false, 'dist', 'cdnpath']));
        $this->assertSame(['dist', 'cdnpath'], $this->invokeMethod($manager, 'setCached', [true, 'dist', 'cdnpath']));
    }
}