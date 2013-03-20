<?php
namespace DmitryDulepov\Simplemvc\View;

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
 * This is an abstract view for the SimpleMVC framework. It provides interaction
 * with other classes.
 *
 * @author Dmitry Dulepov <dmitry.dulepov@gmail.com>
 */
abstract class AbstractView {

	/** @var \DmitryDulepov\Simplemvc\Controller\AbstractController */
	private $controller;

	/**
	 * Creates the instance of this class.
	 *
	 * @param \DmitryDulepov\Simplemvc\Controller\AbstractController $controller
	 */
	public function __constract(\DmitryDulepov\Simplemvc\Controller\AbstractController $controller) {
		$this->controller = $controller;
	}

	/**
	 * Renders the content of the view.
	 *
	 * @return string
	 */
	abstract public function render();

	/**
	 * Obtains the current controller.
	 *
	 * @return \DmitryDulepov\Simplemvc\Controller\AbstractController
	 */
	protected function getController() {
		return $this->controller;
	}

	/**
	 * Renders the content of the TS object. If the path starts with a dot, it will
	 * get the TS from the current configuration. Otherwise global TS setup is
	 * examined.
	 *
	 * @param string $tsPath
	 * @param array|null $data
	 * @param string $tableName
	 * @return string
	 */
	protected function renderTSObject($tsPath, array $data = NULL, $tableName = '_NO_TABLE') {
		if ($tsPath{0} == '.') {
			$tsPath = substr($tsPath, 1);
			$tsType = $this->getController()->getConfigurationValue($tsPath, '');
			$tsConf = $this->getController()->getConfigurationValue($tsPath . '.', '');
		}
		else {
			$config = $GLOBALS['TSFE']->tmpl->setup;
			$tsType = \DmitryDulepov\Simplemvc\Controller\AbstractController::getConfigurationValueFromArray($config, $tsPath, '');
			$tsConf = \DmitryDulepov\Simplemvc\Controller\AbstractController::getConfigurationValueFromArray($config, $tsPath . '.', '');
		}
		$result = '';

		if ($tsType && is_array($tsConf)) {
			$cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer');
			/** @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj */
			if ($data) {
				$cObj->start($data, $tableName);
			}
			$result = $cObj->cObjGetSingle($tsType, $tsConf);
		}

		return $result;
	}
}
