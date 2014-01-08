PHP Github Updater
=====

PHP Github Updater is a utility class that helps you create an "auto-update" function for your open-source project hosted on Github.

It will check the releases on Github and compare it with your current version number, then allow you to download the latest or next zipball/tarball and uncompress it, replacing your files.

If you want to know how releases work on Github, [check this page](https://github.com/blog/1547-release-your-software).

## Requirements

1. PHP 5.3 or above (with cURL)
2. Write access on your directories to download an archive, uncompress it and replace your files
3. Github API v3 compatible

## Use

### Basic Example

You'll only need one file: ```php-github-updater.class.php```

```
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
```

Explanation:

1. [```new PhpGithubUpdater()```](#__construct) prepare the update handler
2. [```isUpToDate()```](#isuptodate) fetch existing versions and checks if current version correspond to the latest
3. [```installLatestVersion()```](#installversion-and-installlatestversion) downloads, extracts and installs the latest version from Github

### Example with exception handling

You can catch exceptions while calling [```installLatestVersion()```](#installversion-and-installlatestversion):

```
// /!\ WARNING /!\ You should perform a backup of your current installation here!
try {
    $updater->installLatestVersion(
        '/path/to/app/root',
        '/temporary/download/path',
    );
} catch (PguRemoteException $e) {
    //couldn't download latest version
} catch (PguExtractException $e) {
    //the zip is corrupted or you don't have persmission to write to the extract location
} catch (PguOverwriteException $e) {
    //version was downloaded and extracted, but the installation failed
    // /!\ WARNING /!\ You should restore your backup here!
}
```

### Advanced example

You can handle each step separatly (see the [documentation](#documentation) below).

It can be useful, for example, if you want to show your user a description of the update, or even allowing them to download a backup of their install before installing the new version.

First of all, get all the information you need, download the zip, create a backup:

```
require_once('php-github-updater.class.php');
$user = 'yosko';
$repository = 'jotter';
$localVersion = 'v0.2';

$updater = new PhpGithubUpdater($user, $repository);

try {
    $isUpToDate = $updater->isUpToDate($localVersion);
} catch (PguRemoteException $e) {
    //couldn't access Github API
}

if( !$isUpToDate ) {
    $root = '/path/to/app/root';
    $tempDir = '/temporary/download/path';
    $nextVersion = $updater->getNextVersion($localVersion);

    //download zip file onto your server in a temporary directory
    try {
        $archive = $updater->downloadVersion( $nextVersion, $tempDir );
    } catch (PguRemoteException $e) {
        //couldn't download latest version
    }

    //extract zip file to the same temporary directory
    try {
        $extractDir = $updater->extractArchive($archive);
    } catch (PguRemoteException $e) {
        //the zip is corrupted or you don't have persmission to write to the extract location
    }

    //BACKUP: you could do a backup here

    //get a description of the update to show to your user
    $updateTitle = $updater->getTitle($nextVersion);
    $updateDescription = $updater->getDescription($nextVersion);
}
```

Then show those informations to your user, and when the want to go to the next level, you can do the install:

```
$updater = new PhpGithubUpdater('yosko', 'ddb');
try {
    //note that $tempDir, $extractDir and $root were defined in the previous script
    $result = $updater->moveFilesRecursive(
        $tempDir.DIRECTORY_SEPARATOR.$extractDir,
        $root
    );
} catch (PguOverwriteException $e) {
    //couldn't overwrite existing installation
    // /!\ WARNING /!\ You should restore your backup here!
}

//if there is any data/database upgrade to perform, you might do it here
```

### Recommandations

* Always do a backup of your installation before overwriting it!
* You shouldn't check for newer version on each page load in your project because it access Github remotely.

### Documentation

If you would rather do it step by step all by yourself, here is the class documentation.

All methods are public.

#### ```__construct()```

Declare your updater object. A request for existing version numbers will be sent to Github API.

* ```$user``` (required):
* ```$repository``` (required): required repository information
* ```$server``` (optional): useful if you want to use it on Github Enterprise, but not yet tested (default: ```https://api.github.com/```)

#### ```installVersion()``` and ```installLatestVersion()```

Performs the complete download from Github and installation of a version of your app.

* ```$version``` (required): version to install (only in ```installVersion()```)
* ```$root``` (required): where to install version (root of your app)
* ```$tempDirectory``` (required): where to temporary put and extract downloaded archive
* Depends mostly on: [```downloadVersion()```](#downloadversion), [```extractArchive()```](#extractarchive) and [```moveFilesRecursive()```](#movefilesrecursive)
* Can throw ```PguOverwriteException```

#### ```downloadVersion()```

Save ZIP or TAR.GZ archive from Github.

* ```$version``` (required): version to download
* ```$destDirectory``` (required): where to save archive
* ```$extension``` (optional): '.zip' (default) or '.tar.gz'
* Uses [```getZipballUrl()``` or ```getTarballUrl()```](#getzipballurl-and-gettarballurl)
* Can throw ```PguRemoteException```

#### ```extractArchive()```

Extract files from downloaded archive

* ```$path``` (required): path to archive
* Returns the directory name where files were extracted (looks like &lt;user&gt;-&lt;repository&gt;-&lt;lastCommitHash&gt;)
* Can throw ```PguExtractException```

#### ```moveFilesRecursive()```

Generic method to move all files from a directory to another.

* ```$source``` (required): path to extracted files
* ```$destination``` (required): path to files to overwrite
* Returns ```true``` on success or ```false``` on error

#### ```getReleases()```

Get existing releases. To also get prereleases, call [```fetchPrereleasesToo()```](#fetchprereleasestoo) first.

* ```$forceFetch``` (optional): true to force a call to Github
* Returns releases (versions) existing on Github). See ["Releases" in Github API documentation](http://developer.github.com/v3/repos/releases/).

#### ```getNextVersion()```

Gives the name of the version immediately following the given one.

* ```$version``` (required): version to compare
* Returns a version number or ```false``` if no result
* Uses [```compareVersions()```](#compareversions)

#### ```getLatestVersion()```

Gives the name of the latest version existing on Github or ```false``` if no result.

#### ```getZipballUrl()``` and ```getTarballUrl()```

Gives the URL to a Zip or Tar ball to download the requested version.

* ```$version``` (required)
* Returns a URL or false if not found

#### ```getTitle()``` and ```getDescription()```

Gives the title or description (Github flavored Markdown format) of the requested version.

* ```$version``` (required)
* Returns a string (title or description), or an empty string if nothing found.

#### ```isUpToDate()```

Checks if the given version is equal or more recent than the latest one on Github.

* ```$version``` (required): version to check
* Returns ```true``` if version is up-to-date
* Uses [```compareVersions()```](#compareversions)

#### ```compareVersions()```

Just a wrapper for PHP's [```version_compare()```](http://www.php.net/manual/en/function.version-compare.php). You might want to extend the class ```PhpGithubUpdater``` and overwrite this to implement a custom version compare algorithm.

Exemple of how it works by default: ```v1-anyOtherString``` &lt; ```v1-dev``` &lt; ```v1-alpha``` = ```v1-a``` &lt; ```v1-beta``` = ```v1-b``` &lt; ```v1-RC``` = ```v1-rc``` &lt; ```v1-#``` &lt; ```v1-pl``` = ```v1-p``` &lt; ```v1``` &lt; ```v1.1```

* ```$version1``` (required): first version to compare
* ```$version2``` (required): second version to compare
* Returns
  * ```1``` if version1 &gt; version2
  * ```0``` if version1 = version2
  * ```-1``` if version1 &lt; version2

#### ```getContentFromGithub()```

Utility method to get JSONs and ZIPs from Github using cURL.

#### ```useProxy()```

If your server is behind a proxy, you might want to define the proxy URL

* ```$proxy``` (required): url/ip and port

#### ```fetchPrereleasesToo()```

Configure the type of releases you want to fetch (only main releases if you don't call ```fetchPrereleasesToo()```). You might want to force another check after that ([```getReleases(true)```](#getreleases)).

* ```$prereleasesToo``` (optional): set to false I you don't want prereleases

## Licence

PHP Github Updater was written by [Yosko](http://www.yosko.net), all rights reserved. It is distributed under the  [GNU LGPL](http://www.gnu.org/licenses/lgpl.html) license.

## Changelog

* v2 (2014-01-08):
  * switched from tags to releases
  * option to fetch prereleases too
  * access to release title and description
  * added version number at the top of the file
  * rewrote some methods
  * multiple fixes
* v1 (2014-01-02): initial version