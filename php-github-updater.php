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
        $this->remoteTags = false;
    }

    public function getRemoteTags() {
        //load tags only once
        if($this->remoteTags === false) {
            $url = $this->server.'repos/'.$this->user.'/'.$this->repository.'/tags';
            $this->remoteTags = json_decode(file_get_contents( $url ));
        }
        return $this->remoteTags;
    }
}

?>