<?php

namespace luya\aws\commands;

use luya\aws\helpers\S3PolicyHelper;
use Yii;
use luya\console\Command;

/**
 * S3 console commands.
 * 
 * In order to use the s3 command add it to the controllerMap, for example with {{luya\Config}}:
 * 
 * ```php
 * $config->application([
 *     'controllerMap' => [
 *         's3' => 'luya\aws\commands\S3Command',
 *     ]
 * ])->consoleRuntime();
 * ```
 * 
 * Then its availabe with `./vendor/bin/luya s3`
 * 
 * @author Basil Suter <git@nadar.io>
 * @since 1.2.0
 */
class S3Command extends Command
{
    public $storage = 'storage';

    /**
     * Change the bucket policy.
     * 
     * Example `s3/apply-policy s3PolicyPublicRead`
     *
     * @param string $policyName
     * @return void
     */
    public function actionApplyPolicy($policyName)
    {
        return Yii::$app->get($this->storage)->updateBucketPolicy(S3PolicyHelper::$policies[$policyName]);
    }
}