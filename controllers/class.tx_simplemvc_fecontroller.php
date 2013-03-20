<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Dmitry Dulepov <dmitry.dulepov@gmail.com>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * $Id: class.tx_simplemvc_fecontroller.php 36760 2011-08-17 14:53:34Z ddulepov $
 */

/**
 * This class contains a main FE controller.
 *
 * @author	Dmitry Dulepov <dmitry.dulepov@gmail.com>
 * @package	tx_simplemvc
 * @subpackage	controllers
 */
class tx_simplemvc_fecontroller extends tx_simplemvc_abstractcontroller {

	protected $csrfTokenName = null;

	/**
	 * Set to true to require cHash check for USER objects
	 *
	 * @var bool
	 */
	protected $requireChash = false;

	public function __construct() {
		parent::__construct();
		if (is_null($this->csrfTokenName)) {
			$this->csrfTokenName = get_class($this) . '/csrf';
		}
	}

	/**
	 * Checks if CSRF token is valid.
	 *
	 * @return bool
	 */
	public function checkCsrfToken() {
		$token = $_SESSION[$this->csrfTokenName];
		$validToken = sha1($token);
		unset($_SESSION[$this->csrfTokenName]);

		$postedToken = $this->getPostParameter('csrf');
		return $token != '' && $postedToken == $validToken;
	}

	/**
	 * Obtains CSRF token for the next forum request.
	 *
	 * @return void
	 */
	public function getCsrfToken() {
		$_SESSION[$this->csrfTokenName] = t3lib_div::getRandomHexString(64);
		return sha1($_SESSION[$this->csrfTokenName]);
	}

	/**
	 * Main dispatching function of the class
	 *
	 * @param string $unused Unused
	 * @param array $conf Plugin configuration
	 * @return string
	 */
	public function main($unused, array $configuration) {
		$this->init($configuration);

		if ($this->requireChash) {
			if ($this->cObj->getUserObjectType() == tslib_cObj::OBJECTTYPE_USER) {
				$GLOBALS['TSFE']->reqCHash();
			}
			else {
				// Reset in case of INT object
				$this->requireChash = false;
			}
		}

		$content = $this->dispatch();

		$content = $this->cleanContent($content);

		return $content;
	}

	/**
	 * Cleans up the output.
	 *
	 * @param string $content
	 * @return string
	 */
	private function cleanContent($content) {
		// Remove subpart markers
		$content = preg_replace('/<!--\s*###[a-z0-9_]+###\s*(?:begin|end)\s*-->/i', '', $content);

		return $content;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simplemvc/controllers/class.tx_simplemvc_maincontroller.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simplemvc/controllers/class.tx_simplemvc_maincontroller.php']);
}

?>