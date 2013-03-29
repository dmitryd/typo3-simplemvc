<?php
namespace DmitryDulepov\Simplemvc\View;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Dmitry Dulepov <dmitry.dulepov@gmail.com>
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
 * This is a SimpleMVC view that uses Twig for templates.
 *
 * @author Dmitry Dulepov <dmitry.dulepov@gmail.com>
 */
class TwigView extends AbstractView implements \Twig_LoaderInterface {

	/**
	 * File path of the twig template.
	 *
	 * @var string
	 */
	protected $templateFilePath;

	/**
	 * @param \DmitryDulepov\Simplemvc\Controller\AbstractController $controller
	 * @param string|null $templateFile
	 */
	public function __construct(\DmitryDulepov\Simplemvc\Controller\AbstractController $controller) {
		parent::__construct($controller);
		$this->setTemplateFilePath(null);
	}

	/**
	 * Renders the content of the view.
	 *
	 * @return string
	 */
	public function render() {
		$twig = new \Twig_Environment($this, array(
			'cache' => PATH_site . 'typo3temp/tx_simplemvc/twig_cache',
			'debug' => $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['debug'],
			'auto_reload' => $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['debug'],
		));

		$content = $twig->render($this->templateFilePath, $this->data);

		return $content;
	}

	/**
	 * Sets the template file path. There are three options:
	 * 1. set from the passed parameter
	 * 2. set from the TypoScript: simplemvc.<controller_name>.<action_name>.twigTemplate
	 * 3. set from automatically using 'EXT:' . <current_ext_key> . '/Resources/Private/Templates/' . <controller_name> . '/' . <action_name> . '.html.twig'
	 *
	 * In 1st and 2nd cases the path must be parsable by GeneralUtility::fileX.
	 *
	 * @param string|null $templateFile
	 * @return void
	 */
	protected function setTemplateFilePath($templateFile) {
		if (!$templateFile) {
			// From TypoScript

			$controller = $this->getController();

			$fullControllerClassName = get_class($controller);

			list(, $controllerClassName) = GeneralUtility::revExplode('\\', get_class($controller), 2);
			$controllerAction = $controller->getAction();

			$configurationPath = sprintf('simplemvc.%s.%s.twigTemplate', $controllerClassName, $controllerAction);
			$templateFile = $this->getController()->getConfigurationValue($configurationPath);

			if (!$templateFile) {
				// Autoname
				list(, $extensionKey) = explode('\\', $fullControllerClassName, 3);
				$extensionKey = GeneralUtility::camelCaseToLowerCaseUnderscored($extensionKey);

				$templateFile = sprintf('EXT:%s/Resources/Private/Templates/%s/%s.html.twig', $extensionKey, $controllerClassName, $controllerAction);
			}
		}
		if ($templateFile) {
			$this->templateFilePath = GeneralUtility::getFileAbsFileName($templateFile);
		}
	}

	/**
	 * Checks that the template exists and throws a error if not.
	 *
	 * @param string $name
	 * @throws \Twig_Error_Loader
	 */
	private function checkTemplateExists($name) {
		if (!file_exists($name)) {
			throw new \Twig_Error_Loader(sprintf('Twig template file \'%s\' does not exist.', $this->stripPathSite($name)));
		}
	}

	/**
	 * Strips the PATH_site from the path.
	 *
	 * @param string $filePath
	 * @return string
	 */
	private function stripPathSite($filePath) {
		if (substr($filePath, 0, strlen(PATH_site)) == PATH_site) {
			$filePath = substr($filePath, strlen(PATH_site));
		}

		return $filePath;
	}

	/*===== Methods from Twig_LoaderInterface =====*/

	/**
	 * Gets the source code of a template, given its name.
	 *
	 * @param string $name The name of the template to load
	 *
	 * @return string The template source code
	 *
	 * @throws \Twig_Error_Loader When $name is not found
	 */
	public function getSource($name) {
		$this->checkTemplateExists($name);

		return file_get_contents($name);
	}

	/**
	 * Gets the cache key to use for the cache for a given template name.
	 *
	 * @param string $name The name of the template to load
	 *
	 * @return string The cache key
	 *
	 * @throws \Twig_Error_Loader When $name is not found
	 */
	public function getCacheKey($name) {
		return str_replace('/', '_', $this->stripPathSite($name));
	}

	/**
	 * Returns true if the template is still fresh.
	 *
	 * @param string $name The template name
	 * @param int $time The last modification time of the cached template
	 *
	 * @return Boolean true if the template is fresh, false otherwise
	 *
	 * @throws \Twig_Error_Loader When $name is not found
	 */
	public function isFresh($name, $time) {
		$this->checkTemplateExists($name);

		return filemtime($name) <= $time;
	}
}