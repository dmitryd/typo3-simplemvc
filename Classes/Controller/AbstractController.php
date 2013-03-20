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
 * This is an abstract controller with common methods for all other controllers
 * from the SimpleMVC framework. Every controller that uses SimpleMVC classes
 * must inherit from this class.
 *
 * @author Dmitry Dulepov <dmitry.dulepov@gmail.com>
 */
abstract class AbstractController {

	/**
	 * Content object, set by the calling instance from the outside.
	 *
	 * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	public $cObj;

	/**
	 * TypoScript configuration array as passed by the calling content object.
	 * @var array
	 */
	private $configuration;

	/**
	 * Prefix for URL parameters. If empty, implementation class will be
	 * substituted. This can be changed by the derieved class by defining
	 * the attribute with the same name and different value.
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

	/** @var \TYPO3\CMS\Lang\LanguageService */
	protected $languageService;

	/**
	 * Language labels for the extension
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
	 * Frontend language id
	 *
	 * @var string
	 */
	protected $frontendLanguageId;

	/**
	 * Creates an instance of this class
	 */
	public function __construct() {
		// Set parameter prefix
		if (is_null($this->parameterPrefix)) {
			$this->parameterPrefix = get_class($this);
		}

		// Load parameters
		$this->getParameters = (array)\TYPO3\CMS\Core\Utility\GeneralUtility::_GET($this->parameterPrefix);
		$this->postParameters = (array)\TYPO3\CMS\Core\Utility\GeneralUtility::_POST($this->parameterPrefix);
		$this->mergedParameters = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($this->getParameters, $this->postParameters);
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
		return self::getConfigurationValueFromArray($this->configuration, $path, $defaultValue);
	}

	/**
	 * Gets the value from the configuration using configuration array and path.
	 * Despite being "public", this method is internal and can disappear or
	 * change the signature. The use outside of the framework is highly
	 * discouraged and is never compatible with future versions!
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
		for ($level = 0; $level < $count; $level++) {
			if ($level < $count - 1) {
				// Go deeper
				$key = $pathParts[$level] . '.';
				if (isset($configuration[$key]) && is_array($configuration[$key])) {
					$configuration = $configuration[$key];
				}
				else {
					break;
				}
			}
			elseif ($pathParts[$level] == '') {
				// Last segment is empty: we return array
				$value = $configuration;
			}
			elseif (isset($configuration[$pathParts[$level]])) {
				// Last segment exists, it is the value
				$value = $configuration[$pathParts[$level]];
			}

		}
		return $value;
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
		$parameterList = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $parameterList, TRUE);
		$parameters = array();
		foreach ($parameterList as $parameter) {
			if (isset($this->mergedParameters[$parameter])) {
				$parameters[$parameter] = $this->mergedParameters[$parameter];
			}
		}
		return \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl($this->parameterPrefix, $parameters);
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

//		$this->initLanguage();
//		$this->initCObj();
		$this->mergeFlexform();
	}

	/**
	 * Checks if a frontend user is logged in
	 *
	 * @return boolean
	 */
	public function isUserLoggedIn() {
		return (is_object($GLOBALS['TSFE']) && $GLOBALS['TSFE']->fe_user->user['uid']);
	}

	/**
	 * Removes the parameter from GET/POST if it exists. "Public" because it
	 * can be called from views.
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
	 * Dispatches the request to the action method.
	 *
	 * @return string
	 */
	protected function dispatch() {
		$content = $this->initialize();
		if (empty($content)) {
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
		}
		return $content;
	}

	/**
	 * Called when no action is defined with the given name in the current
	 * controller instance.
	 *
	 * @param string $action Action name
	 * @return void|string
	 * @throws \Exception
	 */
	protected function errorAction($action) {
		throw new \Exception(sprintf('Action "%s" is not found in the controller "%s"',
			$action, get_class($this)
		));
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
	 * Initializes the instance
	 *
	 * @return void
	 */
	private function mergeFlexform() {
		$flexform = (array)\TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($this->cObj->data['pi_flexform']);
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
