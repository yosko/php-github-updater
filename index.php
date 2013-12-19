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

require_once('php-github-updater.php');

//show all errors & warnings
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors','On');

//Github repository information
$user = 'yosko';
$repository = 'jotter';
$currentVersion = 'v0.2';

//prepare the updater tool
$updater = new PhpGithubUpdater($user, $repository);

//use it
?>
<!doctype html>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <title>PHP Github Updater</title>
</head>
<body>
    <h1>PHP Github Updater</h1>
    <h2>Get list of tags on remote repository</h2>
    <p>Code: <code>$updater->getRemoteTags();</code></p>
    <pre><?php var_dump($updater->getRemoteTags()); ?></pre>
</body>
</html>