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
 * $Id: class.tx_simplemvc_abstractcontroller.php 48437 2012-09-12 04:34:13Z ddulepov $
 */

/**
 * Abstract controller class: a base for all controllers
 *
 * @author Dmitry Dulepov
 * @package	tx_simplemvc
 * @subpackage controllers
 */
class tx_simplemvc_abstractcontroller {

	/**
	 * Content object
	 *
	 * @var tslib_cObj
	 */
	public $cObj;

	/**
	 * Configuration array
	 *
	 * @var array
	 */
	protected $configuration = array();

	/**
	 * Prefix for URL parameters. If empty, implementation class will be substituted
	 *
	 * @var string
	 */
	protected $parameterPrefix = NULL;

	/**
	 * URL query parameters
	 *
	 * @var string
	 */
	private $getParameters = array();

	/** @var language */
	protected $lang;

	/**
	 * Language labels
	 *
	 * @param array
	 */
	private $languageLabels = array();

	/**
	 * Merged parameters
	 *
	 * @var string
	 */
	private $mergedParameters = array();

	/**
	 * POST parameters
	 *
	 * @var string
	 */
	private $postParameters = array();

	/**
	 * TSFE language id
	 *
	 * @var string
	 */
	protected $tsfeLanguage;

	/**
	 * Creates an instance of this class
	 */
	public function __construct() {
		// Set parameter prefix
		if (is_null($this->parameterPrefix)) {
			$this->parameterPrefix = get_class($this);
		}

		// Load parameters
		$this->getParameters = $this->parameterPrefix ? (array)t3lib_div::_GET($this->parameterPrefix) : $_GET;
		$this->postParameters = $this->parameterPrefix ? (array)t3lib_div::_POST($this->parameterPrefix) : $_POST;
		$this->mergedParameters = t3lib_div::array_merge_recursive_overrule($this->getParameters, $this->postParameters);
	}

	/**
	 * Gets the content of the TS object. If the path starts with a dot, it will
	 * get the TS from the current configuration. Otherwise global TS setup is
	 * examined.
	 *
	 * @param string $tsPath
	 * @param array|null $data
	 * @param string|null $tableName
	 * @return string
	 */
	public function getGlobalTSObject($tsPath, array $data = NULL, $tableName = NULL) {
		if ($tsPath{0} == '.') {
			$config = $this->configuration;
			$tsPath = substr($tsPath, 1);
		}
		else {
			$config = $GLOBALS['TSFE']->tmpl->setup;
		}
		$tsType = self::getConfigurationValueFromArray($config, $tsPath, '');
		$tsConf = self::getConfigurationValueFromArray($config, $tsPath . '.', '');
		$result = '';

		if ($tsType && is_array($tsConf)) {
			$cObj = t3lib_div::makeInstance('tslib_cObj');
			/** @var tslib_cObj $cObj */
			if ($data) {
				$cObj->start($data, $tableName);
			}
			$result = $cObj->cObjGetSingle($tsType, $tsConf);
		}

		return $result;
	}

	/**
	 * Gets the content of the TS object.
	 *
	 * @param string $tsPath
	 * @param array|null $data
	 * @param string|null $tableName
	 * @return string
	 */
	public function getTSObject($tsPath, array $data = NULL, $tableName = NULL) {
		$tsType = $this->getConfigurationValue($tsPath, '');
		$tsConf = $this->getConfigurationValue($tsPath . '.', '');
		$result = '';

		if ($tsType && is_array($tsConf)) {
			$cObj = t3lib_div::makeInstance('tslib_cObj');
			/** @var tslib_cObj $cObj */
			if ($data) {
				$cObj->start($data, $tableName);
			}
			$result = $cObj->cObjGetSingle($tsType, $tsConf);
		}

		return $result;
	}

	/**
	 * Adds language labels to the system
	 *
	 * @param array $labels
	 * @return void
	 */
	protected function addLanguageLabels(array $labels) {
		$this->languageLabels = array_merge($this->languageLabels, $labels);
	}

	/**
	 * Adds language labels from file to the system
	 *
	 * @param string $fileRef
	 * @return void
	 */
	protected function addLanguageLabelsFromFile($fileRef) {
		$labels = $this->getLang()->includeLLFile($fileRef, FALSE);
		if (isset($labels[$this->tsfeLanguage])) {
			$labels['default'] = array_merge($labels['default'], $labels[$this->tsfeLanguage]);
		}
		$this->addLanguageLabels($labels['default']);
	}

	/**
	 * Dispatches the request to action
	 *
	 * @return string
	 */
	protected function dispatch() {
		$content = $this->initialize();
		if (!empty($content)) {
			return $content;
		}

		$action = $this->getConfigurationValue('simplemvc.action');
		if (!$action) {
			$action = $this->getParameter('action', 'index');
		}
		$method = ($action ? $action : 'index') . 'Action';
		if ('' == ($content = $this->preprocessAction($action))) {
			if ($action == 'error' || !method_exists($this, $method)) {
				$content = $this->errorAction($action);
			}
			else {
				$content = $this->$method();
			}
		}
		return $content;
	}

	/**
	 * Called when no action is defined with the given name in the current
	 * controller instance.
	 *
	 * @param string $action Action name
	 * @return void
	 * @throws Exception
	 */
	protected function errorAction($action) {
		throw new Exception(sprintf('Action "%s" is not found in the controller "%s"',
			$action, get_class($this)
		));
	}

	/**
	 * Obtains a value from the configuration. The path is a dot-separated value
	 * (i.e. "section1.section2.section3"). If the last character of the path is
	 * a dot, it should point into configuration array.
	 *
	 * @param string $path A path to the configuration value
	 * @param string $defaultValue
	 * @return mixed A value (string) or null
	 */
	public function getConfigurationValue($path, $defaultValue = '') {
		$configuration = $this->configuration;
		return self::getConfigurationValueFromArray($configuration, $path, $defaultValue);
	}

	/**
	 * Gets the value from the configuration using configuration array and path.
	 * This method is internal and can disappear or change the signature.
	 *
	 * @param array $configuration
	 * @param string $path
	 * @param string $defaultValue
	 * @return array
	 * @internal
	 */
	static public function getConfigurationValueFromArray(array $configuration, $path, $defaultValue) {
		$pathParts = explode('.', $path);
		$value = $defaultValue;
		$count = count($pathParts);
		for ($i = 0; $i < $count; $i++) {
			if ($i < $count - 1) {
				// Go deeper
				$key = $pathParts[$i] . '.';
				if (isset($configuration[$key]) && is_array($configuration[$key])) {
					$configuration = $configuration[$key];
				}
				else {
					break;
				}
			}
			elseif ($pathParts[$i] == '') {
				// Last segment is empty: we return array
				$value = $configuration;
			}
			elseif (isset($configuration[$pathParts[$i]])) {
				// Last segment exists, it is the value
				$value = $configuration[$pathParts[$i]];
			}

		}
		return $value;
	}

	/**
	 * Obtains a reference to the language
	 *
	 * @return	language
	 */
	public function getLang() {
		return $this->lang;
	}

	/**
	 * Obtains a string from the language file
	 *
	 * @param string $index
	 * @param boolean $hsc htmlspecialchars flag
	 * @return string
	 */
	public function getLL($index, $hsc = TRUE) {
		$label = isset($this->languageLabels[$index]) ? $this->languageLabels[$index] : '';
		if ($hsc) {
			$label = htmlspecialchars($label);
		}
		return $label;
	}

	/**
	 * Obtains a parameter from the POST parameters.
	 *
	 * @param	string	$name	Parameter name
	 * @param	mixed	$defaultValue	Default value
	 * @return	mixed
	 */
	public function getParameter($name, $defaultValue = '') {
		return isset($this->mergedParameters[$name]) ? $this->mergedParameters[$name] : $defaultValue;
	}

	/**
	 * Obtains a parameter from the POST parameters.
	 *
	 * @param	string	$name	Parameter name
	 * @param	mixed	$defaultValue	Default value
	 * @return	mixed
	 */
	public function getPostParameter($name, $defaultValue = '') {
		return isset($this->postParameters[$name]) ? $this->postParameters[$name] : $defaultValue;
	}

	/**
	 * Sets the value of the POST parameter.
	 *
	 * @param string $name
	 * @param string $value
	 * @return void
	 */
	public function setPostParameter($name, $value) {
		$this->postParameters[$name] = $value;
		$this->mergedParameters[$name] = $value;
	}

	/**
	 * Checks if the current POST request contains valid reCaptcha.
	 *
	 * @return boolean
	 */
	public function getReCaptchaFields() {
		require_once(t3lib_extMgm::extPath('simplemvc') . 'lib/recaptcha/recaptchalib.php');
		$publicKey = $this->getConfigurationValue('simplemvc.reCaptcha.publicKey');
		return recaptcha_get_html($publicKey);
	}

	/**
	 * Obtains a parameter from the POST parameters.
	 *
	 * @param	string	$name	Parameter name
	 * @param	mixed	$defaultValue	Default value
	 * @return	mixed
	 */
	public function getQueryParameter($name, $defaultValue = '') {
		return isset($this->getParameters[$name]) ? $this->getParameters[$name] : $defaultValue;
	}

	/**
	 * Implodes parameter for URL
	 *
	 * @param string $parameterList
	 * @return string
	 */
	public function implodeParametersForURL($parameterList) {
		$parameterList = t3lib_div::trimExplode(',', $parameterList, TRUE);
		$parameters = array();
		foreach ($parameterList as $parameter) {
			if (isset($this->mergedParameters[$parameter])) {
				$parameters[$parameter] = $this->mergedParameters[$parameter];
			}
		}
		return t3lib_div::implodeArrayForUrl($this->parameterPrefix, $parameters);
	}

	/**
	 * Initializes the instance
	 *
	 * @param array $configuration
	 * @return void
	 */
	public function init(array $configuration) {
		$this->configuration = $configuration;

		$this->tsfeLanguage = isset($GLOBALS['TSFE']->config['config']['language']) ?
			$GLOBALS['TSFE']->config['config']['language'] : 'default';

		$this->initLanguage();
		$this->initCObj();
		$this->mergeFlexform();
	}

	/**
	 * Initialization action. If returns non-empty string, it will be returned
	 * instead the action content.
	 *
	 * @return string
	 */
	protected function initialize() {
		return '';
	}

	/**
	 * Initializes $this->cObj.
	 *
	 * @return void
	 */
	protected function initCObj() {
	}

	/**
	 * Initializes language support. Requires TSFE.
	 *
	 * @return void
	 */
	protected function initLanguage() {
		// Init language
		$this->lang = t3lib_div::makeInstance('language');
		$this->lang->init($this->tsfeLanguage);
		$this->loadLanguageFilesFromTS();
		$this->loadLanguageFiles();
		$this->overloadLanguageLabelsFromTS();
	}

	/**
	 * Checks if a frontend user is logged in
	 *
	 * @return boolean
	 */
	protected function isUserLoggedIn() {
		return (is_object($GLOBALS['TSFE']) && $GLOBALS['TSFE']->fe_user->user['uid']);
	}

	/**
	 * Checks if the current POST request contains valid reCaptcha.
	 *
	 * @return boolean
	 */
	public function isValidReCaptcha() {
		require_once(t3lib_extMgm::extPath('simplemvc') . 'lib/recaptcha/recaptchalib.php');
		$privateKey = $this->getConfigurationValue('simplemvc.reCaptcha.privateKey');
		$response = recaptcha_check_answer($privateKey,
			$_SERVER['REMOTE_ADDR'],
			$_POST['recaptcha_challenge_field'],
			$_POST['recaptcha_response_field']
		);
		return $response->is_valid;
	}

	/**
	 * Loads language files for the plugin. Derieved classes should override this
	 * function and load their language files like:
	 * <pre>
	 * $this->addLanguageLabels($this->getLang()->includeLLFile(t3lib_extMgm::extPath('extkey') . 'lang/locallang.xml'));
	 * </pre>
	 *
	 * @return void
	 */
	protected function loadLanguageFiles() {
	}

	/**
	 * Loads language files from TypoScript.
	 *
	 * @return void
	 */
	protected function loadLanguageFilesFromTS() {
		$fileList = $this->getConfigurationValue('simplemvc.languageFiles.', NULL);
		if (is_array($fileList) && count($fileList) > 0) {
			foreach ($fileList as $file) {
				$this->addLanguageLabelsFromFile($file);
			}
		}
	}

	/**
	 * Initializes the instance
	 *
	 * @return void
	 */
	protected function mergeFlexform() {
		$flexform = (array)t3lib_div::xml2array($this->cObj->data['pi_flexform']);
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

	/**
	 * Overloads labels from TypoScript
	 *
	 * @return void
	 */
	protected function overloadLanguageLabelsFromTS() {
		$labels = $this->getConfigurationValue('_LOCAL_LANG.' . $this->tsfeLanguage . '.', array());
		if (is_array($labels)) {
			$this->languageLabels = t3lib_div::array_merge_recursive_overrule($this->languageLabels, $labels);
		}
	}

	/**
	 * Called before the action handler. If returns anything, this is used as
	 * content and action is not called. Useful when each action must check
	 * some condition (such as logged in user) and reply in standard way if
	 * condition is not fulfiled.
	 *
	 * @param string $action
	 * @return string
	 */
	protected function preprocessAction($action) {
		return '';
	}

	/**
	 * Removes the parameter from GET/POST if it exists.
	 *
	 * @param string $parameterName
	 * @return void
	 */
	public function removeParameter($parameterName) {
		if (isset($this->getParameters[$parameterName])) {
			unset($this->getParameters[$parameterName]);
		}
		if (isset($this->mergedParameters[$parameterName])) {
			unset($this->mergedParameters[$parameterName]);
		}
		if (isset($this->postParameters[$parameterName])) {
			unset($this->postParameters[$parameterName]);
		}
	}
}

/* - Make extdeveval happy!

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simplemvc/controllers/class.tx_simplemvc_abstractcontroller.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simplemvc/controllers/class.tx_simplemvc_abstractcontroller.php']);
}

*/
