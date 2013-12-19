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
$remoteTags = $updater->getRemoteTags();

?>
<!doctype html>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <title>PHP Github Updater</title>
    <style>
p { margin: 2px; padding: 4px; }
code, pre { background-color: #f0f0f0; margin: 0; padding: 2px; border: 1px solid #aaa; }
pre code, pre pre { border: 0; }
.important { background-color: #beb; border: 1px solid #9d9; }
</style>
</head>
<body>
    <h1>PHP Github Updater</h1>
    <p class="important"><em>Note: green lines are probably the ones you will need. The other ones are more advanced.</em></p>
    <h2>Is my program up-to-date?</h2>
    <p>Current local version: <code><?php echo $currentVersion; ?></code>.</p>
    <p>Code: <code>$updater->getLastVersion();</code> - Result: <code><?php echo $updater->getLastVersion(); ?></code></p>
    <p class="important">Code: <code>$updater->isUpToDate($currentVersion);</code> - Result: <code><?php echo ($updater->isUpToDate($currentVersion))?'true':'false'; ?></code></p>
    <h2>Download last version</h2>
    <p>Code: <code>$updater->getZipball( $updater->getLastVersion() );</code> - Result: <code><?php echo $updater->getZipball( $updater->getLastVersion() ); ?></code></p>
    <p>Code: <code>$updater->getTarball( $updater->getLastVersion() );</code> - Result: <code><?php echo $updater->getTarball( $updater->getLastVersion() ); ?></code></p>
    <p class="important"></p>
    <h2>Compare versions yourself</h2>
    <p>
        Based on PHP's <code>version_compare()</code>, it compares version number by separating each number or string (separators : <code>_</code>, <code>-</code>, <code>+</code> and <code>.</code>).
        Some specific strings are sorted with a custom order.
    </p>
    <p>
        Example :
        <code>v1-anyOtherString</code>
        &lt; <code>v1-dev</code>
        &lt; <code>v1-alpha</code>
        = <code>v1-a</code>
        &lt; <code>v1-beta</code>
        = <code>v1-b</code>
        &lt; <code>v1-RC</code>
        = <code>v1-rc</code>
        &lt; <code>v1-#</code>
        &lt; <code>v1-pl</code>
        = <code>v1-p</code>
        &lt; <code>v1</code>
        &lt; <code>v1.1</code>
    </p>
    <p>Code: <code>$updater->compareVersions('v0.1-alpha', 'v0.1');</code> - Result: <code><?php echo $updater->compareVersions('v0.1-alpha', 'v0.1'); ?></code></p>
    <p><em>Note: if you want to handle the comparision differently, you can still extend the <code>PhpGithubUpdater</code> class and declare your own <code>compareVersions()</code> method.</em></p>
    <h2>Get list of tags on remote repository</h2>
    <p>Code: <code>$updater->getRemoteTags();</code></p>
    <pre><?php var_dump($updater->getRemoteTags()); ?></pre>
</body>
</html>