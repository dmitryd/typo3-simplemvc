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
 * This class implements a base view.
 *
 * @author	Dmitry Dulepov <dmitry.dulepov@gmail.com>
 * @package	tx_simplemvc
 * @subpackage	views
 */
abstract class tx_simplemvc_abstractview {

	/**
	 * An instance of the controller for this view
	 *
	 * @var tx_simplemvc_abstractcontroller
	 */
	protected $controller;

	/**
	 * Content object
	 *
	 * @var	tslib_cObj
	 */
	protected	$cObj;

	/** @var bool */
	private $enableTemplateCaching;

	/** @var t3lib_cache_frontend_Cache */
	protected	$templateCache = NULL;

	/**
	 * A cached copy of the template file
	 *
	 * @var	string
	 */
	protected	$templateCode = '';

	/**
	 * Template file name
	 *
	 * @var	string
	 */
	protected	$templateFilePath = '';

	/**
	 * Creates an instance of this class.
	 */
	public function __construct(tx_simplemvc_abstractcontroller $controller) {
		$this->controller = $controller;
		$this->cObj = $controller->cObj;
		$this->enableTemplateCaching = TX_SIMPLEMVC_USE_CACHING &&
				TYPO3_UseCachingFramework &&
				!$GLOBALS['TSFE']->no_cache &&
				$this->controller->getConfigurationValue('simplemvc.enableTemplateCaching', TRUE);

		if ($this->enableTemplateCaching) {
			try {
				$this->templateCache = $GLOBALS['typo3CacheManager']->getCache('tx_simplemvc_templates');
			}
			catch (Exception $exception) {
				$this->templateCache = $GLOBALS['typo3CacheFactory']->create(
					'tx_simplemvc_templates',
					$GLOBALS['tx_simplemvc_cacheConfig']['frontend'],
					$GLOBALS['tx_simplemvc_cacheConfig']['backend'],
					$GLOBALS['tx_simplemvc_cacheConfig']['options']
				);
			}
		}

		// Get the template (if possible)
		if ($this->templateFilePath == '') {
			$this->templateFilePath = $this->getTemplateFilePath();
		}
		if ($this->templateFilePath != '') {
			$cacheKey = md5($this->templateFilePath);
			if ($this->enableTemplateCaching) {
				$this->templateCode = $this->templateCache->get($cacheKey);
			}
			if (!$this->templateCode) {
				$this->templateCode = $this->cObj->fileResource($this->templateFilePath);
				if ($this->enableTemplateCaching && $this->templateCode) {
					$this->templateCache->set($cacheKey, $this->templateCode);
				}
			}
		}

		$this->addHeaderDataFromTS();
	}

	/**
	 * Adds data from template to the page header.
	 *
	 * @param string $extKey
	 * @param string $subpart Subpart with header data
	 * @return void
	 */
	protected function addHeaderData($extKey, $subpart = '###ADDITIONAL_HEADER_DATA###') {
		$template = $this->getTemplate($subpart);
		if ($template != '') {
			$relpath = t3lib_extMgm::siteRelPath($extKey);
			$data = $this->cObj->substituteMarkerArray($template, array(
				'###SITE_REL_PATH###' => $relpath,
				'###SITE_REL_PATH_HSC###' => htmlspecialchars($relpath),
			));
			$GLOBALS['TSFE']->additionalHeaderData[sprintf('%s_%x', $extKey, crc32($data))] = $data;
		}
	}

	/**
	 * Ads header data from the plugin's TypoScript. This function is called
	 * from the constructor and runs automatically.
	 *
	 * @param string $section Section to add data from
	 * @return void
	 * @throws Exception
	 */
	protected function addHeaderDataFromTS($section = 'simplemvc.headerData.') {
		$headerData = $this->controller->getConfigurationValue($section, array());

		if (count($headerData) > 0) {
			foreach ($headerData as $key => $entry) {
				if (is_array($entry)) {
					$filePath = $entry['file'];
					if ($filePath{0} != '/' && !preg_match('/^https?:\/\//', $filePath)) {
						$filePath = $GLOBALS['TSFE']->tmpl->getFileName($filePath);
					}
					if ($filePath) {
						if ($entry['type'] == 'js') {
							$this->addHeaderScript($filePath, $entry['footer']);
						}
						elseif ($entry['type'] == 'css') {
							$this->addHeaderStyles($filePath, isset($entry['media']) ? $entry['media'] : '');
						}
						else {
							throw new Exception('Unknown header include: ' . print_r($entry, TRUE));
						}
					}
					else {
						throw new Exception(
							sprintf('Empty header include: class %s, section %s, key %s',
								get_class($this), $section, $key));
					}
				}
			}
		}
	}

	/**
	 * Adds data from template to the page header.
	 *
	 * @param string $styleSheetPath
	 * @param string $media
	 * @return void
	 */
	protected function addHeaderStyles($styleSheetPath, $media = '') {
		$GLOBALS['TSFE']->additionalHeaderData[$styleSheetPath] =
			'<link href="' . htmlspecialchars($styleSheetPath) . '" ' .
			($media ? ' media="' . $media . '" ' : '') .
			'rel="stylesheet" type="text/css" />';
	}

	/**
	 * Adds data from template to the page header.
	 *
	 * @param string $scriptPath
	 * @param bool $toFooter
	 * @return void
	 */
	protected function addHeaderScript($scriptPath, $toFooter = false) {
		$html = '<script src="' . htmlspecialchars($scriptPath) . '" type="text/javascript"></script>';
		if ($toFooter) {
			$GLOBALS['TSFE']->additionalFooterData[$scriptPath] = $html;
		}
		else {
			$GLOBALS['TSFE']->additionalHeaderData[$scriptPath] = $html;
		}

	}

	/**
	 * Renders the content of the view.
	 *
	 * @return	string	The content
	 */
	public abstract function render();

	/**
	 * Obtains form action (for POST requests, no cHash or query string)
	 *
	 * @return string
	 */
	protected function getFormAction() {
		return $this->cObj->typoLink_URL(array(
			'parameter' => $GLOBALS['TSFE']->id
		));
	}

	/**
	 * Returns a string from the local language file
	 *
	 * @param	string	$stringId	String id
	 * @param	boolean	$hsc	htmlspecialchars flag
	 * @return	string	String
	 */
	protected function getLL($stringId, $hsc = TRUE) {
		return $this->controller->getLL($stringId, $hsc);
	}

	/**
	 * Obtains a subpart from the template file
	 *
	 * @param	string	$part	Subpart marker
	 * @return	string
	 * @throws Exception
	 */
	protected function getTemplate($part) {
		if (!$this->templateCode) {
			throw new Exception(sprintf(
				'Empty template in class %s, file %s', get_class($this), $this->templateFilePath
			));
		}

		$template = '';
		$cacheKey = md5($this->templateFilePath . '::' . $part);
		if ($this->enableTemplateCaching) {
			$template = $this->templateCache->get($cacheKey);
		}
		if (!$template) {
			$template = $this->cObj->getSubpart($this->templateCode, $part);
			if ($template && $this->enableTemplateCaching) {
				$this->templateCache->set($cacheKey, $template);
			}
		}
		if (!$template) {
			throw new Exception(sprintf(
				'Empty template part %s in file %s', $part, $this->templateFilePath
			));
		}
		return $template;
	}

	/**
	 * Obtains a path to the template file. Derived classes can override this
	 * method to provide custom path
	 *
	 * @return string
	 */
	protected function getTemplateFilePath() {
		return $this->controller->getConfigurationValue('templateFile');
	}

	/**
	 * Substitues all ###LLL:string_id### entries inside the $content with
	 * texts from the language file.
	 *
	 * @param string $content
	 * @return string
	 */
	protected function substituteLanguageStrings($content) {
		$matches = array();
		if (preg_match_all('/###LLL:[^#]+###/s', $content, $matches, PREG_PATTERN_ORDER | PREG_OFFSET_CAPTURE)) {
			$matchCount = count($matches[0]);
			for ($current = $matchCount - 1; $current >= 0; $current--) {
				list($matchedString, $position) = $matches[0][$current];
				$languageStringData = substr($matchedString, 7, -3);
				if ($languageStringData != '') {
					list($stringId, $flags) = explode(':', $languageStringData, 2);
					$hsc = $flags == '' || t3lib_div::inList($flags, 'nohsc') == FALSE;
					$nl2br = $flags != '' && t3lib_div::inList($flags, 'nl2br');
					$replacement = $this->getLL($stringId, $hsc);
					if ($nl2br) {
						$replacement = nl2br($replacement);
					}
					$content = substr($content, 0, $position) . $replacement . substr($content, $position + strlen($matchedString));
				}
			}
		}
		return $content;
	}

	/**
	 * Substitutes subpart array. Substitution happens in the order of the
	 * $subpartArray.
	 *
	 * @param string $template
	 * @param array $subpartArray
	 * @return string
	 */
	protected function substituteSubpartArray($template, $subpartArray) {
		$content = $template;
		foreach ($subpartArray as $subpartName => $subpartContent) {
			$content = $this->cObj->substituteSubpart($content, $subpartName, $subpartContent);
		}
		return $content;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simplemvc/views/class.tx_simplemvc_abstractview.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simplemvc/views/class.tx_simplemvc_abstractview.php']);
}

?>