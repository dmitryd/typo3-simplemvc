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
 * This class contains a model for the Frontend user.
 *
 * @author	Dmitry Dulepov <dmitry.dulepov@gmail.com>
 * @package	tx_simplemvc
 * @subpackage	models
 */
class tx_simplemvc_feuser extends tx_simplemvc_abstractmodel {

	static protected $tableName = 'fe_users';
	static protected $className = __CLASS__;

	/**
	 * Receives user instance by name.
	 *
	 * @static
	 * @param string $username
	 * @return tx_simplemvc_feuser
	 */
	static public function getUserByUsername($username) {
		list($row) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', self::$tableName,
			'username=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($username, self::$tableName) .
				self::enableFields(self::$tableName)
		);
		return is_array($row) ? t3lib_div::makeInstance(__CLASS__, $row) : null;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simplemvc/models/class.tx_simplemvc_feuser.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simplemvc/models/class.tx_simplemvc_feuser.php']);
}

?>