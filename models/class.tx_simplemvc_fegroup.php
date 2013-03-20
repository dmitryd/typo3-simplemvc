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
 * This class contains a model for the Frontend user group.
 *
 * @author	Dmitry Dulepov <dmitry.dulepov@gmail.com>
 * @package	tx_simplemvc
 * @subpackage	models
 */
class tx_simplemvc_fegroup extends tx_simplemvc_abstractmodel {

	static protected $tableName = 'fe_groups';
	static protected $className = __CLASS__;

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simplemvc/models/class.tx_simplemvc_fegroup.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simplemvc/models/class.tx_simplemvc_fegroup.php']);
}

?>