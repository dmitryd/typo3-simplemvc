<?php

if (!class_exists('Twig_Environment')) {
	require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('simplemvc', 'lib/twig/TwigAutoloader.php'));
	Twig_Autoloader::register();
}

?>