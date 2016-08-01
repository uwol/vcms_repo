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

$gitHubURL = 'https://github.com/uwol/vcms/archive/master.zip';
$dirInZip = 'vcms-master';

$tempDir = 'temp';
$packagesDir = 'packages';
$vcmsZipPath = $tempDir. '/verbindungscms.zip';
$vcmsUnzippedPath = $tempDir. '/' .$dirInZip;


require_once('vendor/vcms/package.php');
require_once('vendor/pear/Archive/Tar.php');


// clean up ----------------------------------------------

echo 'deleting directory packages<br />';
deleteDirectory($packagesDir);

echo 'deleting directory temp<br />';
deleteDirectory($tempDir);


// prepare ----------------------------------------------

/*
* create directories
*/
mkdir($packagesDir);
mkdir($tempDir);

/*
* download current VCMS from GitHub
*/
$ch = curl_init($gitHubURL);
$fp = fopen($vcmsZipPath, 'w');

curl_setopt($ch, CURLOPT_FILE, $fp);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);


if(curl_exec($ch) === false){
    echo curl_error($ch). '<br />';
} else {
	curl_close($ch);
	fclose($fp);

	$zipFileSizeInKB = filesize($vcmsZipPath) / 1000;

	if($zipFileSizeInKB < 500){
		echo 'the zip file is too small: ' .$zipFileSizeInKB. ' KB<br />';
	} else {
		/*
		* unzip VCMS
		*/
		echo 'unzipping zip file<br />';
		system('unzip -q -d ./' .$tempDir. ' ./' .$vcmsZipPath);

		// build ----------------------------------------------

		/*
		* build engine in packages directory
		*/
		echo 'generating engine tar<br />';
		createEnginePackage($vcmsUnzippedPath, $tempDir, $packagesDir);

		/*
		* build module packages in packages directory
		*/
		$modules = array_diff(scandir($vcmsUnzippedPath. '/modules'), array('..', '.'));

		foreach($modules as $module){
			echo 'generating module tar: ' .$module. '<br />';
			createModulePackage($vcmsUnzippedPath. '/modules', $module, $packagesDir);
		}

		/*
		* build manifest
		*/
		echo 'generating manifest php<br />';
		createManifestJson($vcmsUnzippedPath);
	}
}

// clean up ----------------------------------------------

echo 'deleting directory temp<br />';
deleteDirectory($tempDir);

echo 'htaccess<br />';
createVendorHtaccess();
?>