<?php

if (TYPO3_UseCachingFramework) {
	define('TX_SIMPLEMVC_USE_CACHING', true);
	$GLOBALS['tx_simplemvc_cacheConfig'] = array(
		'frontend' => 't3lib_cache_frontend_VariableFrontend',
		'backend' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['cache_hash']['backend'],
		'options' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['cache_hash']['options']
	);
}
else {
	define('TX_SIMPLEMVC_USE_CACHING', false);
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][$_EXTKEY] = 'EXT:simplemvc/cache/class.tx_simplemvc_behook.php:&tx_simplemvc_behook';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][$_EXTKEY] = 'EXT:simplemvc/cache/class.tx_simplemvc_behook.php:&tx_simplemvc_behook';

// Clear cache
//$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['additionalBackendItems']['cacheActions']['clearSimpleMvcCache'] = 'EXT:simplemvc/cache/class.tx_simplemvc_clearcachemenu.php:&tx_simplemvc_clearcachemenu';
//$TYPO3_CONF_VARS['BE']['AJAX']['simplemvc::clearTemplates'] = 'EXT:simplemvc/cache/class.tx_simplemvc_clearrtecache.php:tx_simplemvc_clearrtecache->clearTemplateCache';


?>