<?php
namespace DmitryDulepov\Simplemvc\Model;

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
 * This is an abstract model for the SimpleMVC framework.
 *
 * @author Dmitry Dulepov <dmitry.dulepov@gmail.com>
 */
abstract class AbstractModel {

	/**
	 * Table name for save or delete operations. This *MUST* be set in the
	 * overloaded class.
	 *
	 * @var	string
	 */
	static protected $tableName = '';

	/**
	 * Current class name. This *MUST* be set in the overloaded class to the filexactly
	 * like this:
	 *
	 *	static protected $className = __CLASS__;
	 *
	 * @var	string
	 */
	static protected $className = __CLASS__;

	/**
	 * Maps some fields to object types. For example, calling "getAlbum()"
	 * would cause "tx_myext_album" returned if the following is set in the
	 * map:
	 * 	'album' => 'JohnDoe\Myext\Model\Album'
	 *
	 * Notes:
	 *	- the database field must be 'album_id'
	 *	- this must be set before the costructor is called
	 *	- currently works only with single relations, not for MM or lists
	 *
	 * @var array
	 */
	protected $fieldToObjectMap = array();

	/**
	 * If not empty, getPermalink() uses this cObject to create the link.
	 *
	 * @var string
	 * @see AbstractModel::getPermalink()
	 */
	protected $permalinkTSPath = '';

	/**
	 * Enables cache for records (if necessary). Must be set before the
	 * constructor is called. At the moment uses APC cache without TYPO3
	 * caching framework.
	 *
	 * @var bool
	 */
	static protected $enableCache = false;

	/**
	 * Cache timeout in seconds.
	 *
	 * @var int
	 */
	protected $cacheTimeout = 300;


	//==========================================================================
	// Implementation follows. Do not use any of these attributes in your
	// classes or you risk being screwed!
	//==========================================================================


	/**
	 * Cache key to clear in addition to the model cache key.
	 *
	 * @var array
	 */
	protected $additionalCacheKeys = array();

	/**
	 * CAS token for this instance.
	 *
	 * @var float
	 */
	private $casToken = null;

	/**
	 * Current data row. Do not use this directly.
	 *
	 * @var	array
	 */
	protected $currentRow = array();

	/**
	 * Indicated if the instance is deleted. This is useful for post-delete hooks.
	 *
	 * @var bool
	 */
	private $isDeletedInstance = false;

	/**
	 * Original data row. Do not use this directly.
	 *
	 * @var	array
	 */
	protected $originalRow = array();

	/**
	 * Cached objects from $fieldToObjectMap.
	 *
	 * @var array
	 */
	protected $objectCache = array();

	/**
	 * Creates an instance of this class
	 *
	 * @param mixed $idOrRow
	 */
	public function __construct($idOrRow = null) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::loadTCA(static::$tableName);
		if (is_array($idOrRow)) {
			$this->currentRow = $idOrRow;
			if (isset($this->currentRow['uid'])) {
				// Existing row
				$this->originalRow = $this->currentRow;
			}
		}
		elseif (is_numeric($idOrRow)) {
			/** @noinspection PhpUndefinedMethodInspection */
			list($row) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*',
				static::$tableName, 'uid=' . intval($idOrRow) .
					self::enableFields(static::$tableName));
			if (is_array($row)) {
				$this->originalRow = $this->currentRow = $row;
			}
		}
		else {
			$this->originalRow = $this->currentRow = array();
		}
	}

	/**
	 * Gets all instances of the given model, optionally from the specified pid list.
	 *
	 * @static
	 * @param string $pidList
	 * @return array
	 */
	static public function getAll($pidList = null) {
		$result = array();

		// Get table name from the class
		$tableName = static::$tableName;

		$sorting = self::getSortingForTable($tableName);
		if (!is_null($pidList)) {
			/** @noinspection PhpUndefinedMethodInspection */
			$pidList = $GLOBALS['TYPO3_DB']->cleanIntList($pidList);
		}
		if ($pidList) {
			$where = 'pid IN (' . $pidList . ')' . self::enableFields($tableName);
		}
		else {
			$where = self::enableFieldsClean($tableName);
		}

		/** @noinspection PhpUndefinedMethodInspection */
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', $tableName,
			$where, '', $sorting
		);

		foreach ($rows as $row) {
			$result[] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(static::$className, $row);
		}

		return $result;
	}

	/**
	 * Obtains the instance of the model by its ID.
	 *
	 * @static
	 * @param int $id
	 * @param string $className
	 * @return AbstractModel
	 */
	public static function getById($id, $className = NULL) {
		$result = null;

		if ($id) {
			// Get table name and caching information from the class
			if (!$className) {
				$tableName = static::$tableName;
				$className = static::$className;
				$cachingEnabled = static::$enableCache;
			}
			else {
				$refClass = new \ReflectionClass($className);
				// Note: cannot use getStaticPropertyValue() because $tableName property is protected!
				$allStatics = $refClass->getStaticProperties();
				$tableName = $allStatics['tableName'];
				$cachingEnabled = $allStatics['enableCache'];
			}

			// Try cache if enabled
/* TODO not yet supported in master
			if ($cachingEnabled) {
				$cache = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_simplemvc_cache');
				$cacheKey = $cache->getCacheKey($className, $id);
				$casToken = null;
				list($result, $casToken) = $cache->get($cacheKey, true);
				if ($casToken) {
					$result->casToken = $casToken;
				}
			}
*/

			// Fetch data from db if necessary
			if (!$result) {
				/** @noinspection PhpUndefinedMethodInspection */
				list($row) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', $tableName,
						'uid=' . intval($id) . self::enableFields($tableName));

				if (is_array($row)) {
					$result = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($className, $row);
					/** @var AbstractModel $result */
					$result->cacheMe();
				}
			}
		}

		return $result;
	}

	/**
	 * Obtains the instance of the model by its IDs.
	 *
	 * @static
	 * @param string $idList
	 * @param string $sorting If not null, this field is used for sorting, otherwise - the field from $TCA.
	 * @param int $start
	 * @param int $amount
	 * @return AbstractModel
	 */
	public static function getByIdList($idList, $sorting = null, $start = null, $amount = null) {
		$result = array();

		$idList = implode(',', array_filter(\TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $idList)));

		if ($idList) {
			// Get table name from the class
			$tableName = static::$tableName;

			if (is_null($sorting)) {
				$sorting = self::getSortingForTable($tableName);
			}

			$limit = '';
			if (!is_null($start)) {
				$limit = intval($start);
				if (!is_null($amount)) {
					$limit .= ',' . intval($amount);
				}
			}

			/** @noinspection PhpUndefinedMethodInspection */
			$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', $tableName,
				'uid IN(' . $idList . ')' . self::enableFields($tableName),
				'', $sorting, $limit
			);

			foreach ($rows as $row) {
				$result[] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(static::$className, $row);
			}
		}

		return $result;
	}

	/**
	 * Obtains number of possible model instances by its IDs.
	 *
	 * @static
	 * @param string $idList
	 * @return int
	 */
	public static function getCountByIdList($idList) {
		$result = 0;

		$idList = implode(',', array_filter(\TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $idList)));

		if ($idList) {
			// Get table name from the class
			$tableName = static::$tableName;

			/** @noinspection PhpUndefinedMethodInspection */
			list($row) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('COUNT(*) AS c',
				$tableName,
				'uid IN (' . $idList . ')' . self::enableFields($tableName)
			);
			$result = intval($row['c']);
		}

		return $result;
	}

	/**
	 * Gets record's current data. Typical use is in TS records.
	 *
	 * @return array
	 */
	public function getCurrentRawData() {
		return $this->currentRow;
	}

	/**
	 * Returns a link to this record. Default implementation returns empty link.
	 *
	 * @return string
	 */
	public function getPermalink() {
		$result = '';
		if ($this->permalinkTSPath) {
			$cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tslib_cObj');
			/** @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj */
			$cObj->start($this->currentRow, static::$tableName);
			$tsName = \DmitryDulepov\Simplemvc\Controller\AbstractController::getConfigurationValueFromArray($GLOBALS['TSFE']->tmpl->setup, $this->permalinkTSPath, '');
			$tsConf = \DmitryDulepov\Simplemvc\Controller\AbstractController::getConfigurationValueFromArray($GLOBALS['TSFE']->tmpl->setup, $this->permalinkTSPath . '.', '');
			if ($tsName || is_array($tsConf)) {
				$result = $cObj->cObjGetSingle($tsName, $tsConf);
			}
		}
		return $result;
	}

	/**
	 * Obtains instance of the model.
	 *
	 * @static
	 * @param int $start
	 * @param int $amount
	 * @param string $pidList
	 * @return AbstractModel
	 */
	public static function getLimit($start, $amount, $pidList = '') {
		// Get table name from the class
		$tableName = static::$tableName;
		if (version_compare(TYPO3_branch, '6.1', '<')) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::loadTCA($tableName);
		}

		$sorting = self::getSortingForTable($tableName);

		if ($pidList) {
			/** @noinspection PhpUndefinedMethodInspection */
			$pidList = $GLOBALS['TYPO3_DB']->cleanIntList($pidList);
		}
		if ($pidList) {
			$where = 'pid IN (' . $pidList . ')' . self::enableFields($tableName);
		}
		else {
			$where = self::enableFieldsClean($tableName);
		}

		$result = array();
		/** @noinspection PhpUndefinedMethodInspection */
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $tableName,
			$where, '', $sorting, $start . ',' . $amount);
		/** @noinspection PhpUndefinedMethodInspection */
		while (false !== ($data = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
			$result[] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(static::$className, $data);
		}
		/** @noinspection PhpUndefinedMethodInspection */
		$GLOBALS['TYPO3_DB']->sql_free_result($res);

		return $result;
	}

	/**
	 * Obtains object's table name.
	 *
	 * @return string
	 */
	public function getTableName() {
		return static::$tableName;
	}

	/**
	 * Gets or sets the value of the model.
	 *
	 * @param string $name
	 * @param array $arguments
	 * @return mixed Depends on the function name
	 * @internal Internal use only!
	 */
	public function __call($name, array $arguments) {
		$methodPrefix = substr($name, 0, 3);
		if ($methodPrefix === 'get') {
			return $this->getAttributeValue($this->getAttributeName(substr($name, 3)));
		}
		elseif (substr($name, 0, 2) === 'is') {
			return (boolean)$this->getAttributeValue($this->getAttributeName(substr($name, 2)));
		}
		elseif ($methodPrefix === 'set') {
			if (count($arguments) !== 1) {
				throw new \Exception(sprintf('Wrong parameter count to %s::%s()',
					get_class($this), $name
				));
			}
			if ($arguments[0] instanceof AbstractModel) {
				$this->setObjectValue(substr($name, 3), $arguments[0]);
			}
			else {
				switch (gettype($arguments[0])) {
					case 'object':
					case 'array':
						$value = serialize($arguments[0]);
						break;
					case 'boolean':
						$value = $arguments[0] ? 1 : 0;
						break;
					default:
						$value = $arguments[0];
				}
				$this->setAttributeValue($this->getAttributeName(substr($name, 3)), $value);
			}
		}
		return null;
	}

	/**
	 * Deletes the current record
	 *
	 * @param bool $forceDatabaseDelete
	 * @return void
	 */
	public function delete($forceDatabaseDelete = false) {
		if ($this->currentRow['uid']) {
			$forceDatabaseDelete |= !isset($GLOBALS['TCA'][static::$tableName]['ctrl']['delete']);

			$class = get_class($this);
			// TODO Fix hooks here!
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_simplemvc_model']['canDelete'][$class])) {
				 foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_simplemvc_model']['canDelete'][$class] as $hook) {
					 $parameters = array(
						 'instance' => $this,
						 'soft' => !$forceDatabaseDelete,
						 'result' => true
					 );
					 \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($hook, $parameters, $this);
					 if (!$parameters['result']) {
						 return;
					 }
				 }
			}
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_simplemvc_model']['preDelete'][$class])) {
				 foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_simplemvc_model']['preDelete'][$class] as $hook) {
					 $parameters = array(
						 'instance' => $this,
						 'soft' => !$forceDatabaseDelete,
					 );
					 \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($hook, $parameters, $this);
				 }
			}

 			if ($forceDatabaseDelete) {
				/** @noinspection PhpUndefinedMethodInspection */
				$GLOBALS['TYPO3_DB']->exec_DELETEquery(static::$tableName,
					'uid=' . intval($this->currentRow['uid']));
			}
			else {
				$parameters = array(
					$GLOBALS['TCA'][static::$tableName]['ctrl']['delete'] => 1
				);
				if (isset($GLOBALS['TCA'][static::$tableName]['ctrl']['tstamp'])) {
					$parameters[$GLOBALS['TCA'][static::$tableName]['ctrl']['tstamp']] = time();
				}
				/** @noinspection PhpUndefinedMethodInspection */
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery(static::$tableName,
					'uid=' . intval($this->currentRow['uid']), $parameters);
			}

			$this->isDeletedInstance = true;

			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_simplemvc_model']['postDelete'][$class])) {
				 foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_simplemvc_model']['postDelete'][$class] as $hook) {
					 $parameters = array(
						 'instance' => $this,
						 'soft' => !$forceDatabaseDelete
					 );
					 \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($hook, $parameters, $this);
				 }
			}

			$this->clearCache();

			$this->originalRow = $this->currentRow = array();
		}
	}

	/**
	 * Produces "enableFields" for the table, possibly with alias
	 *
	 * @param string $tableName
	 * @param string $alias
	 * @return string
	 */
	static public function enableFields($tableName, $alias = '') {
		if (TYPO3_MODE == 'BE') {
			$enableFields = \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields($tableName) .
				\TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($tableName);
		}
		else {
			/** @noinspection PhpUndefinedMethodInspection */
			$enableFields = $GLOBALS['TSFE']->sys_page->enableFields($tableName);
		}
		if ($alias != '') {
			$enableFields = str_replace($tableName . '.', $alias . '.', $enableFields);
		}
		return $enableFields;
	}

	/**
	 * Indicates if instance is deleted. This is usable when working in delete hooks.
	 *
	 * @return bool
	 */
	public function isDeleted() {
		return $this->isDeletedInstance;
	}

	/**
	 * Caches this instance.
	 *
	 * @return void
	 */
	protected function cacheMe() {
		if (static::$enableCache) {
/* TODO Implement
			$cache = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_simplemvc_cache');
			$cacheKey = $cache->getCacheKey(get_class($this), $this->getId());
			$cache->set($cacheKey, $this, $this->cacheTimeout, $this->casToken);
*/
		}
	}

	/**
	 * Produces "enableFields" for the table, possibly with alias, without
	 * leading ' AND '
	 *
	 * @param string $tableName
	 * @param string $alias
	 * @return string
	 */
	static public function enableFieldsClean($tableName, $alias = '') {
		$enableFields = trim(self::enableFields($tableName, $alias));
		if ($enableFields) {
			$enableFields = trim(substr($enableFields, 3));
		}
		return $enableFields;
	}

	/**
	 * Converts method name to the attribute name. TxWhateverName becomes
	 * tx_whatever_name.
	 *
	 * @param string $methodName
	 * @return string
	 */
	protected function getAttributeName($methodName) {
		$hasUnderscore = ($methodName{0} === '_');
		if ($hasUnderscore) {
			$methodName = substr($methodName, 1);
		}
		$attributeName = preg_replace('/[A-Z]/e', 'tx_simplemvc_model_convertForName(\'\\0\')', $methodName);
		if (!$hasUnderscore) {
			$attributeName = substr($attributeName, 1);
		}
		return $attributeName;
	}

	/**
	 * Obtains sorting for the table (just fields, no ORDER BY).
	 *
	 * @param string $tableName
	 * @return string
	 */
	static protected function getSortingForTable($tableName) {
		$sorting = '';
		if (isset($GLOBALS['TCA'][$tableName]['ctrl']['default_sortby'])) {
			$sorting = substr($GLOBALS['TCA'][$tableName]['ctrl']['default_sortby'], 9);
		}
		elseif (isset($GLOBALS['TCA'][$tableName]['ctrl']['sortby'])) {
			$sorting = $GLOBALS['TCA'][$tableName]['ctrl']['sortby'];
		}

		return $sorting;
	}

	/**
	 * Clears the cache for this model instance.
	 *
	 * @return void
	 */
	public function clearCache() {
		if (static::$enableCache) {
/* TODO Implement
			$cache = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_simplemvc_cache');
			$cacheKey = $cache->getCacheKey(get_class($this), $this->getId());
			$cache->remove($cacheKey);
			foreach ($this->additionalCacheKeys as $cacheKey) {
				$cache->remove($cacheKey);
			}
*/
		}
	}

	/**
	 * Obtains attribute value
	 *
	 * @param string $attributeName Attribute name
	 * @return mixed Attribute value
	 * @internal Internal use only!
	 */
	private function getAttributeValue($attributeName) {
		$result = null;
		if (isset($this->fieldToObjectMap[$attributeName])) {
			// Getting the object
			$id = intval($this->currentRow[$attributeName . '_id']);
			if ($id) {
				if (isset($this->objectCache[$attributeName])) {
					$result = $this->objectCache[$attributeName];
				}
				else {
					$this->objectCache[$attributeName] = $result = self::getById($id, $this->fieldToObjectMap[$attributeName]);
				}
			}
		}
		else {
			$result = $this->currentRow[$attributeName];
		}
		return $result;
	}

	/**
	 * Retrieves id of the record
	 *
	 * @return	int	ID
	 */
	public function getId() {
		return intval($this->currentRow['uid']);
	}

	/**
	 * Retrieves pid of the record
	 *
	 * @return	int	Pid
	 */
	public function getPid() {
		return intval($this->currentRow['pid']);
	}

	/**
	 * Checks if the current objects represents data from the database.
	 *
	 * @return boolean
	 */
	public function hasRecord() {
		return isset($this->currentRow['uid']);
	}

	/**
	 * Saves data into the database. Executes either update of insert.
	 *
	 * @return	void
	 */
	public function save() {
		// Check that table name is set
		if (static::$tableName == '') {
			throw new \Exception(sprintf('tableName is not set in %s', get_class($this)));
		}

		// Save the data
		$dataDiff = array_diff_assoc($this->currentRow, $this->originalRow);

		$class = get_class($this);
		// TODO Fix hooks
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_simplemvc_model']['preSave'][$class])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_simplemvc_model']['preSave'][$class] as $hook) {
				$parameters = array(
					'instance' => $this,
					'modifiedFieldsList' => array_keys($dataDiff)
				);
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($hook, $parameters, $this);
			}
		}

		// Remove keys starting from '_'
		foreach (array_keys($dataDiff) as $key) {
			if ($key{0} == '_') {
				unset($dataDiff[$key]);
			}
		}

		if (count($dataDiff) > 0) {
			// We have differencies in data. Let's update/insert it
			if ($GLOBALS['TCA'][static::$tableName]['ctrl']['tstamp']) {
				$dataDiff[$GLOBALS['TCA'][static::$tableName]['ctrl']['tstamp']] =
				$this->currentRow[$GLOBALS['TCA'][static::$tableName]['ctrl']['tstamp']] = time();
			}
			if (!isset($this->currentRow['uid'])) {
				if ($GLOBALS['TCA'][static::$tableName]['ctrl']['crdate'] &&
					!isset($dataDiff[$GLOBALS['TCA'][static::$tableName]['ctrl']['crdate']])) {
					$dataDiff[$GLOBALS['TCA'][static::$tableName]['ctrl']['crdate']] =
					$this->currentRow[$GLOBALS['TCA'][static::$tableName]['ctrl']['crdate']] = time();
				}
				if (!isset($dataDiff['pid'])) {
					throw new \Exception('pid value is not set for the new row');
				}

				// New record: insert and fetch all fields
				/** @noinspection PhpUndefinedMethodInspection */
				$GLOBALS['TYPO3_DB']->exec_INSERTquery(static::$tableName,
													   $dataDiff);
				/** @noinspection PhpUndefinedMethodInspection */
				$uid = $GLOBALS['TYPO3_DB']->sql_insert_id();
				/** @noinspection PhpUndefinedMethodInspection */
				list($this->currentRow) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*',
					static::$tableName, 'uid=' . $uid);

				// Update refindex
				$refindex = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('t3lib_refindex');
				/* @var \TYPO3\CMS\Core\Database\ReferenceIndex $refindex */
				$refindex->updateRefIndexTable(static::$tableName, $this->currentRow['uid']);

				$newRecord = true;

				// TODO Update refindex for all db relation fields!!!
			}
			else {
				// Update record
				/** @noinspection PhpUndefinedMethodInspection */
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery(static::$tableName,
					'uid=' . $this->currentRow['uid'], $dataDiff);

				$newRecord = false;
			}
			$this->originalRow = $this->currentRow;

			// TODO Fix hooks
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_simplemvc_model']['postSave'][$class])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_simplemvc_model']['postSave'][$class] as $hook) {
					$parameters = array(
						'instance' => $this,
						'modifiedFieldsList' => array_keys($dataDiff),
						'new' => $newRecord
					);
					\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($hook, $parameters, $this);
				}
			}

			$this->clearCache();
			$this->cacheMe();
		}
	}

	/**
	 * Sets attribute value
	 *
	 * @param string $attributeName Attribute name
	 * @param string $attributeValue Attribute value
	 * @return void
	 */
	private function setAttributeValue($attributeName, $attributeValue) {
// Does not work with null fields!
//		if (isset($this->currentRow['uid']) && !isset($this->currentRow[$attributeName])) {
//			throw new Exception(sprintf('Unknown field "%s" in "%s" (class "%s"',
//				$attributeName, static::$tableName, get_class($this)
//			));
//		}
		if (gettype($attributeValue) == 'boolean') {
			$attributeValue = intval($attributeValue);
		}
		$this->currentRow[$attributeName] = $attributeValue;
	}

	/**
	 * Sets object's value
	 *
	 * @param string $attributeName Attribute name
	 * @param AbstractModel $attributeValue Attribute value
	 * @return void
	 */
	private function setObjectValue($attributeName, AbstractModel $attributeValue) {
		$attributeName = $this->getAttributeName($attributeName);

		$id = $attributeValue->getId();
		if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($id)) {
			throw new \Exception(sprintf(
				'Unable to set value for the attribute "%s.%s" (class "%s") because the value ' .
					'object does not have the id yet.',
				static::$tableName, $attributeName, get_class($this)
			));
		}

		if (isset($GLOBALS['TCA'][static::$tableName]['columns'][$attributeName . '_id'])) {
			// Has id attribute
			$attributeName .= '_id';
			$attributeValue = intval($id);
		}
		else if (!isset($GLOBALS['TCA'][static::$tableName]['columns'][$attributeName])) {
			throw new \Exception(sprintf(
				'Unable to set value for the attribute "%s.%s" (class "%s") because neither id ' .
					'nor direct field exists in $TCA.',
				static::$tableName, $attributeName, get_class($this)
			));
		}

		$this->currentRow[$attributeName] = $attributeValue;
	}

	/**
	 * Sets a new pid for the record
	 *
	 * @param	int	$pid	Page id of the record
	 * @return	void
	 */
	public function setPid($pid) {
		$this->currentRow['pid'] = intval($pid);
	}
}

/**
 * Fastest possible way to map capital letters to normal ones.
 * @param string $match
 * @return string
 */
function tx_simplemvc_model_convertForName($match) {
	$map = array(
		'A' => 'a',
		'B' => 'b',
		'C' => 'c',
		'D' => 'd',
		'E' => 'e',
		'F' => 'f',
		'G' => 'g',
		'H' => 'h',
		'I' => 'i',
		'J' => 'j',
		'K' => 'k',
		'L' => 'l',
		'M' => 'm',
		'N' => 'n',
		'O' => 'o',
		'P' => 'p',
		'Q' => 'q',
		'R' => 'r',
		'S' => 's',
		'T' => 't',
		'U' => 'u',
		'V' => 'v',
		'W' => 'w',
		'X' => 'x',
		'Y' => 'y',
		'Z' => 'z',
	);
	return '_' . $map[$match{0}];
}
