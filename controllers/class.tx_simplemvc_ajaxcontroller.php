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
 * $Id: class.tx_simplemvc_ajaxcontroller.php 60585 2013-09-18 17:31:13Z ddulepov $
 */

require_once(PATH_tslib . 'class.tslib_fe.php');
require_once(PATH_tslib . 'class.tslib_pagegen.php');
require_once(PATH_t3lib . 'class.t3lib_page.php');
require_once(PATH_tslib . 'class.tslib_content.php');
require_once(PATH_t3lib . 'class.t3lib_userauth.php' );
require_once(PATH_tslib . 'class.tslib_feuserauth.php');
require_once(PATH_t3lib . 'class.t3lib_tstemplate.php');
require_once(PATH_t3lib . 'class.t3lib_cs.php');

/**
 * This class implements an AJAX controller for the user center.
 *
 * @author	Dmitry Dulepov <dmitry.dulepov@gmail.com>
 * @package	tx_simplemvc
 * @subpackage controllers
 */
class tx_simplemvc_ajaxcontroller extends tx_simplemvc_abstractcontroller {

	/**
	 * Configuration key (TS configration of this controller).
	 *
	 * @var string
	 */
	protected $configurationKey = null;

	/**
	 * Language object
	 *
	 * @var language
	 */
	protected	$lang;

	/**
	 * Creates an instance of this class.
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
	 * @see tx_simplemvc_absractcontroller::initCObj()
	 */
	protected function initCObj() {
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
	}

	/**
	 * Initializes TSFE object
	 *
	 * @throws Exception
	 * @return void
	 */
	protected function initTSFE() {
		// Initialize basic TSFE
		if (!($id = intval(t3lib_div::_GP('id')))) {
			if (!($id = intval($this->getParameter('id')))) {
				throw new Exception('`id` parameter is not set');
			}
		}

		$GLOBALS['TSFE'] = t3lib_div::makeInstance('tslib_fe', $GLOBALS['TYPO3_CONF_VARS'], $id, '');
		$GLOBALS['TSFE']->set_no_cache();
		$GLOBALS['TSFE']->connectToDB();
		$GLOBALS['TSFE']->initFEuser();
		$GLOBALS['TSFE']->determineId();
		$GLOBALS['TSFE']->getCompressedTCarray();
		$GLOBALS['TSFE']->initTemplate();
		$GLOBALS['TSFE']->getConfigArray();
		$GLOBALS['TSFE']->settingLanguage();

		// Set linkVars, absRefPrefix, etc
		TSpagegen::pagegenInit();
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
		if (!$GLOBALS['TSFE']->fe_user->user['uid']) {
			$this->sendJsonContentType();
			echo json_encode(array(
				'success' => false,
				'error' => htmlspecialchars($this->lang->getLL($errorMessageId))
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
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simplemvc/controllers/class.tx_simplemvc_ajaxcontroller.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simplemvc/controllers/class.tx_simplemvc_ajaxcontroller.php']);
}
