<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Dmitry Dulepov <dmitry.dulepov@gmail.com>
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
 * This class implements a TypoScript object view. It accepts a path to the
 * TypoScript object (either global or local) and an optional model instance.
 * The view will be rendered using provided TS object path.
 *
 * @author Dmitry Dulepov <dmitry.dulepov@gmail.com>
 * @package	tx_simplemvc
 * @subpackage views
 */
class tx_simplemvc_typoscriptview extends tx_simplemvc_abstractview {

	/** @var tx_simplemvc_abstractmodel */
	protected $model;

	/** @var string */
	protected $tsObjectPath;

	/**
	 * Creates this view.
	 *
	 * @param tx_simplemvc_abstractcontroller $controller
	 * @param string $tsObjectPath
	 * @param tx_simplemvc_abstractmodel $model
	 */
	public function __construct(tx_simplemvc_abstractcontroller $controller, $tsObjectPath, tx_simplemvc_abstractmodel $model = null) {
		parent::__construct($controller);
		$this->model = $model;
		$this->tsObjectPath = $tsObjectPath;
	}

	/**
	 * Renders the content of this view.
	 *
	 * @return string
	 */
	public function render() {
		$data = $tableName = null;
		if (!is_null($this->model)) {
			$data = $this->model->getCurrentRawData();
			$tableName = $this->model->getTableName();
		}

		return $this->controller->getGlobalTSObject($this->tsObjectPath, $data, $tableName);
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simplemvc/views/class.tx_simplemvc_typoscriptview.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simplemvc/views/class.tx_simplemvc_typoscriptview.php']);
}

?>