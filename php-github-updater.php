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
 */

class PhpGithubUpdater {
    protected
        $server,
        $user,
        $repository,
        $remoteTags,
        $archiveExtension;

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
        $this->remoteTags       = $this->getRemoteTags();
        $this->archiveExtension = '.zip';
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

        if(!copy( $url, $archive)) {
            throw new PguDownloadException("Download failed.");
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
        $archive = basename($path);
        $directory = '';

        //uncompress from GZ
        if($this->archiveExtension == '.tar.gz') {
            $p = new PharData($path);
            $p->decompress();
            unset($p);
            Phar::unlinkArchive($path);
            $p->unlinkArchive($path);
            $path = substr($path, 0, strlen($path-3)); //point to .tar
        }

        //extract ZIP or TAR (and overwrite if necessary)
        try {
            $phar = new PharData($path);
            $phar->extractTo( dirname($path), null, true );
            // chmod($path, 0755);
        } catch (Exception $e) {
            throw new PguExtractException("Archive extraction failed. The file might be corrupted and you should download it again.");
            return false;
        }

        //find the new subdirectory name
        $file = new RecursiveIteratorIterator($phar);
        $directory = $file->getPathName();
        $directory = substr(
            $directory,
            strpos(
                $directory,
                $archive
            ) + strlen($archive) + 1
        );
        if(strpos($directory, DIRECTORY_SEPARATOR)) {
            $directory = substr($directory, 0, strpos($directory, DIRECTORY_SEPARATOR));
        }

        unset($file);
        unset($phar);
        Phar::unlinkArchive($path); //delete archive

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
     * Return the list of tags from the remote (in the Github API v3 format)
     * See: http://developer.github.com/v3/repos/#list-tags
     * @return array list of tags and their information
     */
    public function getRemoteTags() {
        //load tags only once
        if(!isset($this->remoteTags)) {
            $url = $this->server.'repos/'.$this->user.'/'.$this->repository.'/tags';
            $remoteTags = json_decode(file_get_contents( $url ), true);

            $this->remoteTags = array();
            foreach($remoteTags as $key => $tag) {
                $this->remoteTags[$tag['name']] = $tag;
            }
        }
        return $this->remoteTags;
    }

    /**
     * Get the remote version number following (more recent) the given one
     * @param  string $version version number (doesn't have to exist on remote)
     * @return string          next version number (or false if no result)
     */
    public function getNextVersion($version) {
        $nextVersion = false;
        foreach($this->remoteTags as $tag) {
            if($this->compareVersions($version, $tag['name']) < 0) {
                $nextVersion = $tag['name'];
                break;
            }
        }
        return $nextVersion;
    }

    /**
     * Return the latest remote version number
     * @return string version number
     */
    public function getLatestVersion() {
        reset($this->remoteTags);
        $latest = current($this->remoteTags);
        return $latest['name'];
    }

    /**
     * Get zipball link for the given version
     * @param  string $version version number
     * @return string          URL to zipball
     */
    public function getZipballUrl($version) {
        return isset($this->remoteTags[$version])?$this->remoteTags[$version]['zipball_url']:false;
    }

    /**
     * Get tarball link for the given version
     * @param  string $version version number
     * @return string          URL to tarball
     */
    public function getTarballUrl($version) {
        return isset($this->remoteTags[$version])?$this->remoteTags[$version]['tarball_url']:false;
    }

    /**
     * Check if given version is up-to-date with the remote
     * @param  string  $version version number
     * @return boolean          true if $version >= latest remote version
     */
    public function isUpToDate($version) {
        reset($this->remoteTags);
        $latest = current($this->remoteTags);
        return ($this->compareVersions($version, $latest['name']) >= 0);
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
}

class PguDownloadException extends Exception {}
class PguExtractException extends Exception {}
class PguOverwriteException extends Exception {}

?>