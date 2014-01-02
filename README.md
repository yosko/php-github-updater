PHP Github Updater
=====

PHP Github Updater is a utility class that helps you create an "auto-update" function for your open-source project hosted on Github.

It will check the tags on Github and compare it with your current version number, then allow you to download the latest or next zipball/tarball and uncompress it, replacing your files.

## Requirements

1. PHP 5.3 or above
2. Apache's ```ssl_module``` and PHP's ```php_openssl``` to access Github in https
3. Write access on your directories to download an archive and uncompress it
4. Github API v3 compatible

## Use

### Example

You'll only need one file: ```php-github-updater.class.php```

    require_once('php-github-updater.class.php');
    $user = 'yosko';
    $repository = 'jotter';
    $localVersion = 'v0.2';
    
    $updater = new PhpGithubUpdater($user, $repository);
    if( !$updater->isUpToDate($localVersion) ) {
        $updater->installLatestVersion(
            '/path/to/app/root',
            '/temporary/download/path',
        );
    }

Explanation:

1. ```new PhpGithubUpdater()``` will fetch Github for a list of available version numbers
2. ```isUpToDate()``` checks if current version is equal to the latest
3. ```installLatestVersion()``` downloads, extracts and installs the latest version from Github

### Recommandations

* Always do a backup of your installation before overwriting it!
* You shouldn't call ```PhpGithubUpdater``` each time a page loads in your project because it access Github remotely.

### Documentation

If you would rather do it step by step all by yourself, here is the class documentation. All methods are public:

...

## Licence

PHP Github Updater was written by [Yosko](http://www.yosko.net), all rights reserved. It is distributed under the  [GNU LGPL](http://www.gnu.org/licenses/lgpl.html) license.

## Changelog
