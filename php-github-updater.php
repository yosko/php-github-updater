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
        $remoteTags;

    /**
     * Init the updater with remote repository information
     * @param string $user       user name
     * @param string $repository repository name
     * @param string $server     (optional) server name. Default: Github
     *                           useful for Github Enterprise using Github API v3
     */
    public function __construct($user, $repository, $server = 'https://api.github.com/') {
        $this->user       = $user;
        $this->repository = $repository;
        $this->server     = $server;
        $this->remoteTags = $this->getRemoteTags();
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
     * Get the remote version number preceding (older than) the given one
     * @param  string $version version number (doesn't have to exist on remote)
     * @return string          previous version number
     */
    public function getPreviousVersion($version) {
        //TODO
    }

    /**
     * Get the remote version number following (more recent) the given one
     * @param  string $version version number (doesn't have to exist on remote)
     * @return string          next version number
     */
    public function getNextVersion($version) {
        //TODO
    }

    /**
     * Return the latest remote version number
     * @return string version number
     */
    public function getLastVersion() {
        reset($this->remoteTags);
        $latest = current($this->remoteTags);
        return $latest['name'];
    }

    /**
     * Get zipball link for the given version
     * @param  string $version version number
     * @return string          URL to zipball
     */
    public function getZipball($version) {
        return isset($this->remoteTags[$version])?$this->remoteTags[$version]['zipball_url']:false;
    }

    /**
     * Get tarball link for the given version
     * @param  string $version version number
     * @return string          URL to tarball
     */
    public function getTarball($version) {
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

?>