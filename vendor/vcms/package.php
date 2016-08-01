<?php
/*
This file is part of VCMS.

VCMS is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

VCMS is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with VCMS. If not, see <http://www.gnu.org/licenses/>.
*/

function createEnginePackage($srcDirectory, $tempDir, $packagesDir){
	$tempEngineDir = $tempDir. '/engine';

	mkdir($tempEngineDir);

	copy($srcDirectory. '/inc.php', $tempEngineDir. '/inc.php');
	copy($srcDirectory. '/index.php', $tempEngineDir. '/index.php');

	// @Deprecated
	mkdir($tempEngineDir. '/lib');

	copyFolder($srcDirectory. '/styles', $tempEngineDir. '/styles');
	copyFolder($srcDirectory. '/vendor', $tempEngineDir. '/vendor');

	$currentdir = getcwd();
	chdir($tempDir);

	$tar = new Archive_Tar('../' .$packagesDir. '/engine.tar');
	$array = array('engine/inc.php', 'engine/index.php', 'engine/lib/',
		'engine/styles/', 'engine/vendor/');
	$tar->create($array);

	chdir($currentdir);

	if(is_dir($tempEngineDir)){
		deleteDirectory($tempEngineDir);
	}
}

function createModulePackage($modulesDirectory, $moduleName, $packagesDir){
	$currentdir = getcwd();
	chdir($modulesDirectory);
	$tar = new Archive_Tar('../../../' .$packagesDir. '/' .$moduleName. '.tar');
	$tar->create($moduleName);
	chdir($currentdir);
}

function createManifestJson($vcmsDirectory){
	$result = array();

	// global version
	require_once($vcmsDirectory. '/vendor/vcms/lib/LibGlobal.class.php');
	$libGlobal = new \vcms\LibGlobal();
	$result['engine'] = $libGlobal->version;

	// module versions
	$modules = array_diff(scandir($vcmsDirectory. '/modules'), array('..', '.'));

	foreach($modules as $module){
		$moduleVersion = getModuleVersion($vcmsDirectory, $module);
		$result[$module] = $moduleVersion;
	}

	ksort($result);

	/*
	* write manifest
	*/
	$filename = 'manifest.json';
	$handle = fopen($filename, 'w');
	fwrite($handle, json_encode($result));
	fclose($handle);
}

function createRootHtaccess(){
	$filename = '.htaccess';
	$handle = fopen($filename, 'w');
	fwrite($handle, 'AddType application/x-httpd-php .json');
	fclose($handle);
}

function createVendorHtaccess(){
	$filename = 'vendor/.htaccess';
	$handle = fopen($filename, 'w');
	fwrite($handle, 'deny from all');
	fclose($handle);
}

function cleanDirectory($directory){
	$files = array_diff(scandir($directory), array('..', '.'));

	foreach($files as $file){
		if(is_file($directory. '/' .$file)){
			if(substr($part, 0, 1) == '.'){
				unlink($directory. '/' .$file);
			}
		}

		if(is_dir($directory. '/' .$file)){
			cleanDirectory($directory. '/' .$file);
		}
	}
}

function deleteDirectory($directory){
	if(is_dir($directory)){
		$files = array_diff(scandir($directory), array('..', '.'));

		foreach($files as $file){
			if(is_dir($directory. '/' .$file)){
				deleteDirectory($directory. '/' .$file);
			} elseif(is_file($directory. '/' .$file)){
				unlink($directory. '/' .$file);
			}
		}

		if(is_dir($directory)){
			rmdir($directory);
		}
	}
}

function copyFolder($source, $dest){
    if(!is_dir($dest)){
        mkdir($dest);
    }

 	$files = array_diff(scandir($source), array('..', '.'));

    foreach($files as $file){
        if(is_dir($source. '/' .$file)){
            copyFolder($source. '/' .$file, $dest. '/' .$file);
        } else {
            copy($source. '/' .$file, $dest. '/' .$file);
        }
    }
}

function getModuleVersion($vcmsDir, $moduleName){
	if(is_dir($vcmsDir. '/modules/' .$moduleName)){
		$metaJson = $vcmsDir. '/modules/' .$moduleName. '/meta.json';

		if(is_file($metaJson)){
			$jsonFileContents = file_get_contents($metaJson);
			$json = json_decode($jsonFileContents, true);
			return $json['version'];
		}
	}
}
?>