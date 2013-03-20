<?php

class tx_simplemvc_behook {

	/** @var tx_simplemvc_cache */
	protected $cache;

	public function __construct() {
		$this->cache = t3lib_div::makeInstance('tx_simplemvc_cache');
	}

	public function processDatamap_afterDatabaseOperations($status, $table, $id) {
		if ($status != 'new') {
			$this->cache->remove($table . '_' . $id);
		}
	}

	public function processCmdmap_postProcess($command, $table, $id) {
		if ($command == 'delete') {
			$this->cache->remove($table . '_' . $id);
		}
	}
}
