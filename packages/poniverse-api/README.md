# poniverse-php
[![Build Status](https://travis-ci.org/Poniverse/poniverse-php.svg?branch=master)](https://travis-ci.org/Poniverse/poniverse-php) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Poniverse/poniverse-php/badges/quality-score.png?s=7d517521c412c0adf149be941eebb82b13051ec9)](https://scrutinizer-ci.com/g/Poniverse/poniverse-php/) [![Code Coverage](https://scrutinizer-ci.com/g/Poniverse/poniverse-php/badges/coverage.png?s=07f581f7e79b32a700e1fad64950f56179a61bf1)](https://scrutinizer-ci.com/g/Poniverse/poniverse-php/)

## Installation

Require this package in composer.json and update

    "poniverse/api": "dev-master"

### Normal Setup

See `demo\index.php` for an example.

### Laravel 5 Setup

Add the service provider to your `providers` section, usually located in `config/app.php`.

    Poniverse\Api\ApiServiceProvider::class,

Publish the configuration and then edit it

    php artisan vendor:publish
