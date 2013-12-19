PHP Github Updater
=====

PHP Github Updater is a utility class that helps you create an "auto-update" function for your open-source project hosted on Github.

It will check the tags on Github and compare it with your current version number, then allow you to download the latest or next zipball/tarball and uncompress it, replacing your files.

## Requirements

1. Github API v3 compatible
2. Apache's ```ssl_module``` and PHP's ```php_openssl``` to access Github in https

## Use

* You'll only need one file (```php-github-updater.class.php```). Include it in your code.
* ...

Notes
* You shouldn't call this each time a page loads in your project because it access Github remotely.
* The current version doesn't need to exist on Github
* There MUST be at least one tag on Github?

## Licence

PHP Github Updater was written by [Yosko](http://www.yosko.net), all rights reserved. It is distributed under the  [GNU LGPL](http://www.gnu.org/licenses/lgpl.html) license.

## Changelog
