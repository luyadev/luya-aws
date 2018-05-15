<p align="center">
  <img src="https://raw.githubusercontent.com/luyadev/luya/master/docs/logo/luya-logo-0.2x.png" alt="LUYA Logo"/>
</p>

# LUYA Amazon S3 filesystem

[![LUYA](https://img.shields.io/badge/Powered%20by-LUYA-brightgreen.svg)](https://luya.io)
[![Latest Stable Version](https://poser.pugx.org/luyadev/luya-filesystem-amazons3/v/stable)](https://packagist.org/packages/luyadev/luya-filesystem-amazons3)
[![Total Downloads](https://poser.pugx.org/luyadev/luya-filesystem-amazons3/downloads)](https://packagist.org/packages/luyadev/luya-filesystem-amazons3)
[![Slack Support](https://img.shields.io/badge/Slack-luyadev-yellowgreen.svg)](https://slack.luya.io/)

A file system for the LUYA admin interface in order to store and retrieve all storage data from an Amazon S3 Bucket.

## Installation

For the installation of the filesystem composer is required.

```sh
composer require luyadev/luya-filesystem-amazons3:~1.0.0
```

### Configuration 

After installation via Composer include the storage component to your configuration in the components sesction with your credentials:

```php
'components' => [
    'storage' => [
        'class' => 'luya\amazons3\S3FileSystem',
        'bucket' => 'BUCKET_NAME',
        'key' => 'KEY',
        'secret' => 'SECRET',
        'region' => 'eu-central-1',
    ]
]
```