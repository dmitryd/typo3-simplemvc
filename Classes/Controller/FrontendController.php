<?php
namespace DmitryDulepov\Simplemvc\Controller;

use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

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
 * Controller for Frontend plugins.
 *
 * @author Dmitry Dulepov <dmitry.dulepov@gmail.com>
 */
class FrontendController extends AbstractController {

	/**
	 * Defines the CSRF token name for this controller. You must define it
	 * if you want to use CSRF functions of thios class.
	 *
	 * @var null|string
	 */
	protected $csrfTokenName = null;

	/**
	 * Set to true to require cHash check for USER objects
	 *
	 * @var bool
	 */
	protected $requireChash = false;

	/**
	 * Initializes the instance
	 *
	 * @param array $configuration
	 * @return void
	 */
	public function init(array $configuration) {
		parent::init($configuration);

		$this->mergeFlexform();
	}

	/**
	 * Checks if CSRF token is valid.
	 *
	 * @return bool
	 */
	public function checkCsrfToken() {
		if (!$this->csrfTokenName) {
			throw new \Exception('csrfTokenName must not be null if you use CSRF protection in the controller', 1364482988);
		}
		if (!is_array($_SESSION)) {
			session_start();
		}
		$token = $_SESSION[$this->csrfTokenName];
		$validToken = sha1($token);
		unset($_SESSION[$this->csrfTokenName]);

		$postedToken = $this->getPostParameter('csrf');
		return $token != '' && $postedToken == $validToken;
	}

	/**
	 * Obtains CSRF token for the next request.
	 *
	 * @return string
	 */
	public function getCsrfToken() {
		if (!$this->csrfTokenName) {
			throw new \Exception('csrfTokenName must not be null if you use CSRF protection in the controller', 1364483068);
		}
		if (!is_array($_SESSION)) {
			session_start();
		}
		$_SESSION[$this->csrfTokenName] = GeneralUtility::getRandomHexString(64);
		return sha1($_SESSION[$this->csrfTokenName]);
	}

	/**
	 * Main dispatching function of the class
	 *
	 * @param string $unused Unused
	 * @param array $configuration Plugin configuration
	 * @return string
	 */
	public function main(/** @noinspection PhpUnusedParameterInspection */ $unused, array $configuration) {
		$this->init($configuration);

		if ($this->requireChash) {
			if ($this->cObj->getUserObjectType() == ContentObjectRenderer::OBJECTTYPE_USER) {
				/** @noinspection PhpUndefinedMethodInspection */
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

	/**
	 * Initializes the instance
	 *
	 * @return void
	 */
	private function mergeFlexform() {
		$flexform = (array)GeneralUtility::xml2array($this->cObj->data['pi_flexform']);
		if (isset($flexform['data']['sDEF']['lDEF'])) {
			foreach ($flexform['data']['sDEF']['lDEF'] as $fieldName => $fieldValue) {
				if (isset($fieldValue['vDEF'])) {
					$value = trim($fieldValue['vDEF']);
					if ($value) {
						$this->configuration[$fieldName] = $value;
					}
				}
				elseif (isset($fieldValue['el'])) {
					$this->configuration[$fieldName] = $fieldValue['el'];
				}
			}
		}
	}

}
