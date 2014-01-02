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

### Basic Example

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

### Example with exception handling

You can catch exceptions while calling installLatestVersion():

    // /!\ WARNING /!\ You should perform a backup of your current installation here!
    try {
        $updater->installLatestVersion(
            '/path/to/app/root',
            '/temporary/download/path',
        );
    } catch (PguDownloadException $e) {
        //couldn't download latest version
    } catch (PguExtractException $e) {
        //download probably failed because the archive can't be extracted
    } catch (PguOverwriteException $e) {
        //version was downloaded and extracted, but the installation failed
        // /!\ WARNING /!\ You should restore your backup here!
    }

### Advanced example

...

### Recommandations

* Always do a backup of your installation before overwriting it!
* You shouldn't call ```PhpGithubUpdater``` each time a page loads in your project because it access Github remotely.

### Documentation

If you would rather do it step by step all by yourself, here is the class documentation.

All methods are public.

#### ```__construct()```

Declare your updater object. A request for existing version numbers will be sent to Github API.

* ```$user``` (required):
* ```$repository``` (required): required repository information
* ```$server``` (optional): useful if you want to use it on Github Enterprise (default: ```https://api.github.com/```)

#### ```installVersion()``` and ```installLatestVersion()```

Performs the complete download from Github and installation of a version of your app.

* ```$version``` (required): version to install (only in ```installVersion()```)
* ```$root``` (required): where to install version (root of your app)
* ```$tempDirectory``` (required): where to temporary put and extract downloaded archive
* Depends mostly on: ```downloadVersion()```, ```extractArchive()``` and ```moveFilesRecursive()```
* Can throw ```PguOverwriteException```

#### ```downloadVersion()```

Save ZIP or TAR.GZ archive from Github.

* ```$version``` (required): version to download
* ```$destDirectory``` (required): where to save archive
* ```$extension``` (optional): '.zip' (default) or '.tar.gz'
* Uses ```getZipballUrl()``` or ```getTarballUrl()```
* Can throw ```PguDownloadException```

#### ```extractArchive()```

Extract files from downloaded archive

* ```$path``` (required): path to archive
* Returns the directory name where files were extracted (looks like &lt;user&gt;-&lt;repository&gt;-&lt;lastCommitHash&gt;)
* Can throw ```PguExtractException```

#### ```moveFilesRecursive()```

Generic function to move all files from a directory to another.

* ```$source``` (required): path to extracted files
* ```$destination``` (required): path to files to overwrite
* Returns ```true``` on success or ```false``` on error

#### ```getRemoteTags()```

Returns tags (versions) existing on Github). See ["List tags" in Github API](http://developer.github.com/v3/repos/#list-tags).

#### ```getNextVersion()```

Gives the name of the version immediately following the given one.

* ```$version``` (required): version to compare
* Returns a version number or ```false``` if no result
* Uses ```compareVersions()```

#### ```getLatestVersion()```

Gives the name of the latest version existing on Github.

#### ```getZipballUrl()``` and ```getTarballUrl()```

Gives the URL to a Zip or Tar ball to download the requested version.

* ```$version``` (required)
* Returns a URL or false if not found

#### ```isUpToDate()```

Checks if the given version is equal or more recent than the latest one on Github.

* ```$version``` (required): version to check
* Returns ```true``` if version is up-to-date
* Uses ```compareVersions()```

#### ```compareVersions()```

Just a wrapper for PHP's ```version_compare()```. You might want to extend ```PhpGithubUpdater``` and overwrite this to implement a custom version compare algorithm.

Exemple of how it works by default: ```v1-anyOtherString``` &lt; ```v1-dev``` &lt; ```v1-alpha``` = ```v1-a``` &lt; ```v1-beta``` = ```v1-b``` &lt; ```v1-RC``` = ```v1-rc``` &lt; ```v1-#``` &lt; ```v1-pl``` = ```v1-p``` &lt; ```v1``` &lt; ```v1.1```

* ```$version1``` (required): first version to compare
* ```$version2``` (required): second version to compare
* Returns
  * ```1``` if version1 &gt; version2
  * ```0``` if version1 = version2
  * ```-1``` if version1 &lt; version2

## Licence

PHP Github Updater was written by [Yosko](http://www.yosko.net), all rights reserved. It is distributed under the  [GNU LGPL](http://www.gnu.org/licenses/lgpl.html) license.

## Changelog
