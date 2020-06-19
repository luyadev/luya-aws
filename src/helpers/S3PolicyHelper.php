<?php

namespace luya\aws\helpers;

class S3PolicyHelper
{
    /*   
    public static function readOnlyPolicy()
    {
        return '{
            "Version": "2012-10-17",
            "Statement": [
              {
                "Action": [
                  "s3:GetBucketLocation",
                  "s3:ListBucket"
                ],
                "Effect": "Allow",
                "Principal": {
                  "AWS": [
                    "*"
                  ]
                },
                "Resource": [
                  "arn:aws:s3:::{{bucket}}"
                ],
                "Sid": ""
              },
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
        }';
    }
    */
    public static function publicReadPolicy()
    {
      return '{
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
      }';
    }
}