<p align="center">
  <img src="https://raw.githubusercontent.com/luyadev/luya/master/docs/logo/luya-logo-0.2x.png" alt="LUYA Logo"/>
</p>

# LUYA Amazon S3 filesystem

[![LUYA](https://img.shields.io/badge/Powered%20by-LUYA-brightgreen.svg)](https://luya.io)
![Tests](https://github.com/luyadev/luya-aws/workflows/Tests/badge.svg)
[![Test Coverage](https://api.codeclimate.com/v1/badges/5b3028e3ff5f74961f3d/test_coverage)](https://codeclimate.com/github/luyadev/luya-aws/test_coverage)
[![Latest Stable Version](https://poser.pugx.org/luyadev/luya-aws/v/stable)](https://packagist.org/packages/luyadev/luya-aws)
[![Total Downloads](https://poser.pugx.org/luyadev/luya-aws/downloads)](https://packagist.org/packages/luyadev/luya-aws)
[![Slack Support](https://img.shields.io/badge/Slack-luyadev-yellowgreen.svg)](https://slack.luya.io/)

A file system for the LUYA admin interface in order to store and retrieve all storage data from an Amazon S3 Bucket.

## Installation

For the installation of the filesystem composer is required.

```sh
composer require luyadev/luya-aws
```

### Configuration 

After installation via Composer include the storage component to your configuration in the components sesction with your credentials:

```php
'components' => [
    //...
    'storage' => [
        'class' => 'luya\aws\S3FileSystem',
        'bucket' => 'BUCKET_NAME',
        'key' => 'KEY',
        'secret' => 'SECRET',
        'region' => 'eu-central-1',
    ]
]
```
