<?php

namespace DmitryDulepov\Simplemvc\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class TwigExtension extends \Twig_Extension {

	/**
	 * Returns a list of global functions to add to the existing list.
	 *
	 * @return array An array of global functions
	 */
	public function getFunctions() {
		return array(
			new \Twig_SimpleFunction('typolink', 'tx_simplemvc_twig_typolink'),
		);
	}

	/**
	 * Returns the name of the extension.
	 *
	 * @return string The extension name
	 */
	public function getName() {
		return 'typo3';
	}

}

function tx_simplemvc_twig_typolink($config) {
	if (is_array($config)) {
		$cObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
//		$cObj->
	}
}