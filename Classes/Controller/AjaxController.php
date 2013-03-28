<?php
namespace DmitryDulepov\Simplemvc\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Dmitry Dulepov <dmitry.dulepov@gmail.com>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Controller for AJAX actions.
 *
 * @author Dmitry Dulepov <dmitry.dulepov@gmail.com>
 */
class AjaxController extends AbstractController {

	/**
	 * Configuration key (TS configration of this controller).
	 *
	 * @var string
	 */
	protected $configurationKey = null;

	/**
	 * Creates the instance of this class.
	 */
	public function __construct() {
		ob_start();

		if (is_null($this->configurationKey)) {
			$this->configurationKey = get_class($this);
		}

		parent::__construct();
		$this->initTSFE();

		$this->sendContentType();
	}

	/**
	 * Initializes $this->cObj.
	 *
	 * @return void
	 * @see AbsractController::initCObj()
	 */
	protected function initCObj() {
		$this->cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\Frontend\\ContentObject\\ContentObjectRenderer');
	}

	/**
	 * Main dispatching function of the class
	 *
	 * @return string
	 */
	public function main() {
		$configuration = &$GLOBALS['TSFE']->tmpl->setup['plugin.'][$this->configurationKey . '.'];
		if (!is_array($configuration)) {
			$configuration = array();
		}
		$this->init($configuration);
		$content = $this->dispatch();

		return $content;
	}

	/**
	 * Checks if the user is logged in. If not, sends a error message.
	 *
	 * @param	string	$errorMessageId	Language ID of the error message
	 * @return	boolean	true if user is logged in
	 */
	public function requireFeUser($errorMessageId) {
		if (!$this->isUserLoggedIn()) {
			$this->sendJsonContentType();
			echo json_encode(array(
				'success' => false,
				'error' => htmlspecialchars($this->languageService->getLL($errorMessageId))
			));
			return false;
		}
		return true;
	}

	/**
	 * Adds content type to the response. This function is called from the
	 * constructor. Override it if you need a content type other than JSON
	 * and use any of sendXXXContentType functions instead.
	 *
	 * @return	void
	 */
	public function sendContentType() {
		$this->sendJsonContentType();
	}

	/**
	 * Adds an HTML content type to the response
	 *
	 * @return	void
	 */
	final public function sendHtmlContentType() {
		header('Content-type: text/html; charset=utf8');
	}

	/**
	 * Adds a JSON content type to the response
	 *
	 * @return	void
	 */
	final public function sendJsonContentType() {
		header('Content-type: application/json');
	}

	/**
	 * Adds an XML content type to the response
	 *
	 * @return	void
	 */
	final public function sendXmlContentType() {
		header('Content-type: text/xml');
	}

	/**
	 * Initializes TSFE object
	 *
	 * @return void
	 */
	protected function initTSFE() {
		// Initialize basic TSFE
		if (!($id = intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id')))) {
			if (!($id = intval($this->getParameter('id')))) {
				throw new \Exception('`id` parameter is not set', 1364489554);
			}
		}

		$GLOBALS['TSFE'] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController', $GLOBALS['TYPO3_CONF_VARS'], $id, '');
		$GLOBALS['TSFE']->set_no_cache();
		$GLOBALS['TSFE']->connectToDB();
		$GLOBALS['TSFE']->initFEuser();
		$GLOBALS['TSFE']->determineId();
		$GLOBALS['TSFE']->getCompressedTCarray();
		$GLOBALS['TSFE']->initTemplate();
		$GLOBALS['TSFE']->getConfigArray();

		// Set linkVars, absRefPrefix, etc
		\TYPO3\CMS\Frontend\Page\PageGenerator::pagegenInit();
	}
}
