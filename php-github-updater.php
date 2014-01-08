<?php
/**
 * PHP Github Updater - Copyright 2013 Yosko (www.yosko.net)
 * 
 * This file is part of PHP Github Updater.
 * 
 * PHP Github Updater is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * PHP Github Updater is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with PHP Github Updater.  If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * @author  yosko (http://www.yosko.net/)
 * @link    https://github.com/yosko/php-github-updater
 * @version v2
 * 
 */

class PhpGithubUpdater {
    protected
        $server,
        $user,
        $repository,
        $releases,
        $archiveExtension,
        $proxy,
        $prereleasesToo;

    /**
     * Init the updater with remote repository information
     * @param string $user       user name
     * @param string $repository repository name
     * @param string $server     (optional) server name. Default: Github
     *                           useful for Github Enterprise using Github API v3
     */
    public function __construct($user, $repository, $server = 'https://api.github.com/') {
        $this->user             = $user;
        $this->repository       = $repository;
        $this->server           = $server;
        $this->archiveExtension = '.zip';
        $this->releases         = false;
        $this->proxy            = false;
        $this->prereleasesToo   = false;
    }

    /**
     * Define a simple proxy through which all requests to Github
     * will have to go
     * @param  string $proxy proxy url (in the format ip:port)
     */
    function useProxy($proxy) {
        $this->proxy = $proxy;
    }

    /**
     * Define a simple proxy through which all requests to Github
     * will have to go
     * @param  string $proxy proxy url (in the format ip:port)
     */
    function fetchPrereleasesToo($prereleasesToo = true) {
        $previousState = $this->prereleasesToo;
        $this->prereleasesToo = $prereleasesToo;
    }

    /**
     * Perform download and installation of the latest version
     * /!\ WARNING: you should do a backup before calling this method
     * @param  string $root          path where the version will be installed
     * @param  string $tempDirectory path where the version could be downloaded and extracted before install
     * @return string                execution status
     */
    public function installLatestVersion($root, $tempDirectory) {
        $version = $this->getLatestVersion();
        return $this->installVersion($version, $root, $tempDirectory);
    }

    /**
     * Perform download and installation of the given version
     * /!\ WARNING: you should do a backup before calling this method
     * @param  string  $version       version to install
     * @param  string  $root          path where the version will be installed
     * @param  string  $tempDirectory path where the version could be downloaded and extracted before install
     * @return boolean                execution status
     */
    public function installVersion($version, $root, $tempDirectory) {
        $archive = $this->downloadVersion($version, $tempDirectory);
        $extractDir = $this->extractArchive($archive);
        $result = $this->moveFilesRecursive(
            $tempDirectory.DIRECTORY_SEPARATOR.$extractDir,
            $root
        );

        if(!$result) {
            throw new PguOverwriteException("Overwriting failed while installing. You might need to restore a backup of your application.");
        }

        return $result;
    }

    /**
     * Download archive for the given version directly from Github
     * @param  string $destDirectory path to the directory where the archive will be saved
     * @param  string $extension     file extension (default: '.zip', other choice : '.tar.gz')
     * @return misc                  FALSE on failure, path to archive on success
     */
    public function downloadVersion($version, $destDirectory, $extension = '.zip') {
        $this->archiveExtension = $extension;
        $archive = $destDirectory.DIRECTORY_SEPARATOR.$version.$this->archiveExtension;

        if($this->archiveExtension == '.zip') {
            $url = $this->getZipballUrl( $version );
        } elseif($this->archiveExtension == '.tar.gz') {
            $url = $this->getTarballUrl( $version );
        }

        if(!$this->getContentFromGithub( $url, $archive )) {
            throw new PguRemoteException("Download failed.");
        }

        return $archive;
    }

    /**
     * Extract the content 
     * @param  string $path archive path
     * @return string       name (not path!) of the subdirectory where files where extracted
     *                      should look like <user>-<repository>-<lastCommitHash>
     */
    public function extractArchive($path) {
        // $archive = basename($path);
        $directory = '';

        //ZipArchive way
        $zip = new ZipArchive;
        if ($zip->open($path) === true) {
            $stat = $zip->statIndex( 0 );
            $directory = substr( $stat['name'], 0, strlen($stat['name'])-1 );
            $zip->extractTo( dirname($path) );
            $zip->close();
        } else {
            throw new PguExtractException("Archive extraction failed. The file might be corrupted and you should download it again.");
        }

        //zip_open way
        // if ($zip = zip_open($path)) {
        //     $destDir = dirname($path);
        //     while ($zip_entry = zip_read($zip)) {
        //         $zip_entry_name = zip_entry_name($zip_entry);
        //         $zip_entry_path = $destDir.'/'.$zip_entry_name;

        //         //entry is a directory: create it
        //         if(substr($zip_entry_path, -1) == '/') {
        //             if(empty($directory))
        //                 $directory = substr($zip_entry_name, 0, strlen($zip_entry_name)-1);

        //             if(!file_exists($zip_entry_path))
        //                 mkdir($zip_entry_path, 0777, true);

        //         //entry is a file: write it
        //         } elseif (zip_entry_open($zip,$zip_entry,"r"))  {
        //             $fstream = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
        //             file_put_contents($zip_entry_path, $fstream);
        //             chmod($zip_entry_path, 0777);
        //         }
        //         zip_entry_close($zip_entry);
        //     }
        //     zip_close($zip);
        // } else {
        //     throw new PguExtractException("Archive extraction failed. The file might be corrupted and you should download it again.");
        // }

        //Phar way (handles .tar.gz too)
        //uncompress from GZ
        // if($this->archiveExtension == '.tar.gz') {
        //     $p = new PharData($path);
        //     $p->decompress();
        //     unset($p);
        //     Phar::unlinkArchive($path);
        //     $p->unlinkArchive($path);
        //     $path = substr($path, 0, strlen($path-3)); //point to .tar
        // }
        //extract ZIP or TAR (and overwrite if necessary)
        // try {
        //     $phar = new PharData($path);
        //     $phar->extractTo( dirname($path), null, true );
        //     // chmod($path, 0755);
        // } catch (Exception $e) {
        //     throw new PguExtractException("Archive extraction failed. The file might be corrupted and you should download it again.");
        //     return false;
        // }
        // //find the new subdirectory name
        // $file = new RecursiveIteratorIterator($phar);
        // $directory = $file->getPathName();
        // $directory = substr(
        //     $directory,
        //     strpos(
        //         $directory,
        //         $archive
        //     ) + strlen($archive) + 1
        // );
        // if(strpos($directory, DIRECTORY_SEPARATOR)) {
        //     $directory = substr($directory, 0, strpos($directory, DIRECTORY_SEPARATOR));
        // }
        // unset($file);
        // unset($phar);
        // Phar::unlinkArchive($path); //delete archive

        return $directory;
    }

    /**
     * Recursively move all files from $source directory into $destination directory
     * @param  string  $source      source directory from which files and subdirectories will be taken
     * @param  string  $destination destination directory where files and subdirectories will be put
     * @return boolean              execution status
     */
    public function moveFilesRecursive($source, $destination) {
        $result = true;

        if(file_exists($source) && is_dir($source)) {
            if(!file_exists($destination)) {
                mkdir($destination);
            }

            $files = scandir($source);
            foreach ($files as $file) {
                if (in_array($file, array(".",".."))) continue;

                if(is_dir($source.DIRECTORY_SEPARATOR.$file)) {
                    $result = $this->moveFilesRecursive(
                        $source.DIRECTORY_SEPARATOR.$file,
                        $destination.DIRECTORY_SEPARATOR.$file
                    );
                } else {
                    $result = copy(
                        $source.DIRECTORY_SEPARATOR.$file,
                        $destination.DIRECTORY_SEPARATOR.$file
                    );
                    unlink($source.DIRECTORY_SEPARATOR.$file);
                }

                if(!$result) break;
            }
        }

        rmdir($source);

        return $result;
    }

    /**
     * Return the list of releases from the remote (in the Github API v3 format)
     * See: http://developer.github.com/v3/repos/releases/
     * @param  boolean $forceFetch force (re)fetching
     * @return array               list of releases and their information
     */
    public function getReleases($forceFetch = false) {
        if($forceFetch)
            $this->releases = false;

        //load releases only once
        if($this->releases === false) {
            $url = $this->server.'repos/'.$this->user.'/'.$this->repository.'/releases';
            $releases = json_decode($this->getContentFromGithub( $url ), true);

            $this->releases = array();
            foreach($releases as $key => $release) {
                //keep pre-releases only if asked to
                if($this->prereleasesToo || $release['prerelease'] == false)
                    $this->releases[$release['tag_name']] = $release;
            }
        }
        return $this->releases;
    }

    /**
     * Get the remote version number following (more recent) the given one
     * @param  string $version version number (doesn't have to exist on remote)
     * @return string          next version number (or false if no result)
     */
    public function getNextVersion($version) {
        $this->getReleases();
        $nextVersion = false;
        foreach($this->releases as $release) {
            if($this->compareVersions($version, $release['tag_name']) < 0) {
                $nextVersion = $release['tag_name'];
                break;
            }
        }
        return $nextVersion;
    }

    /**
     * Return the latest remote version number
     * @return string version number (or false if no result)
     */
    public function getLatestVersion() {
        $this->getReleases();
        $latest = false;
        if(!empty($this->releases)) {
            reset($this->releases);
            $latest = current($this->releases);
        }
        return $latest['tag_name'];
    }

    /**
     * Get zipball link for the given version
     * @param  string $version version number
     * @return string          URL to zipball
     */
    public function getZipballUrl($version) {
        $this->getReleases();
        return isset($this->releases[$version])?$this->releases[$version]['zipball_url']:false;
    }

    /**
     * Get tarball link for the given version
     * @param  string $version version number
     * @return string          URL to tarball
     */
    public function getTarballUrl($version) {
        $this->getReleases();
        return isset($this->releases[$version])?$this->releases[$version]['tarball_url']:false;
    }

    /**
     * Get the title of a release
     * @param  string $version release version number
     * @return string          title
     */
    public function getTitle($version) {
        $this->getReleases();
        return isset($this->releases[$version]['name'])?$this->releases[$version]['name']:'';
    }

    /**
     * Get the description of a release
     * @param  string $version release version number
     * @return string          description (in Markdown syntax format)
     */
    public function getdescription($version) {
        $this->getReleases();
        return isset($this->releases[$version]['body'])?$this->releases[$version]['body']:'';
    }

    /**
     * Check if given version is up-to-date with the remote
     * @param  string  $version version number
     * @return boolean          true if $version >= latest remote version
     */
    public function isUpToDate($version) {
        $this->getReleases();
        reset($this->releases);
        $latest = current($this->releases);
        return ($this->compareVersions($version, $latest['tag_name']) >= 0);
    }

    /**
     * Compare two version numbers (based on PHP-standardized version numbers)
     * See http://php.net/manual/en/function.version-compare.php
     * @param  string  $version1 first version number
     * @param  string  $version2 second version number
     * @return integer           $version1 < $version2 => -1
     *                           $version1 = $version2 => 0
     *                           $version1 > $version2 => 1
     */
    public function compareVersions($version1, $version2) {
        return version_compare($version1, $version2);
    }

    /**
     * Perform a request to Github API
     * @param  string $url URL to get
     * @return string      Github's response
     */
    public function getContentFromGithub($url, $path = false) {
        //use curl if possible
        if(function_exists('curl_version')) {
            $ch = curl_init();
            curl_setopt($ch,CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER,1); 
            curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,5);
            curl_setopt($ch, CURLOPT_USERAGENT, 'php-github-updater');
            if($this->proxy !== false) {
                curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
            }
            if($path !== false) {
                if(!file_exists(dirname($path))) {
                    mkdir(dirname($path));
                }
                touch($path);
                $file = fopen($path,'w+');
                curl_setopt($ch, CURLOPT_FILE, $file); 
                curl_setopt($ch, CURLOPT_HEADER, 0); 
            }
            $content = curl_exec($ch);
            curl_close($ch);
            if($path !== false) {
                fclose($file);
            }

        //fallback - might raise a warning with proxies
        } else {
            $content = file_get_contents( $url );
        }

        if(empty($content)) {
            throw new PguRemoteException("Fetch data from Github failed. You might be behind a proxy.");
        }

        return $content;
    }
}

class PguRemoteException extends Exception {}
class PguExtractException extends Exception {}
class PguOverwriteException extends Exception {}

?>