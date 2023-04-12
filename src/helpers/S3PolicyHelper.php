<?php

namespace luya\aws\helpers;

/**
 * S3 Policy Helper
 *
 * @author Basil Suter <git@nadar.io>
 * @since 1.2.0
 */
class S3PolicyHelper
{
    public const S3_POLICY_PUBLIC_READ = 's3PolicyPublicRead';

    public static $policies = [
      self::S3_POLICY_PUBLIC_READ => '{
        "Version": "2012-10-17",
        "Statement": [
          {
            "Action": [
              "s3:GetObject"
            ],
            "Effect": "Allow",
            "Principal": {
              "AWS": [
                "*"
              ]
            },
            "Resource": [
              "arn:aws:s3:::{{bucket}}/*"
            ],
            "Sid": ""
          }
        ]
      }',
    ];
}
