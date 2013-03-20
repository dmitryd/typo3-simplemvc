<?php

class tx_simplemvc_cache implements t3lib_Singleton {

	protected $useMemcached = false;

	/** @var Memcached */
	protected $cache;

	/** @var bool */
	protected $enabled = false;

	/**
	 * Initializes this object.
	 */
	public function __construct() {
		$this->enabled = $this->initCache();
	}

	/**
	 * Obtains the entry from the cache.
	 *
	 * @param string $key
	 * @return mixed|null
	 */
	public function get($key, $getCas = false) {
		$result = null;
		$cas = null;
		if ($this->enabled) {
			$key = $this->getKey($key);
			if ($this->useMemcached) {
				$result = $this->cache->get($key, null, $cas);
				if ($result === false && $this->cache->getResultCode() == Memcached::RES_NOTFOUND) {
					$result = null;
				}
			}
			else {
				$result = apc_fetch($key);
			}
		}
		return $getCas ? array($result, $cas) : $result;
	}

	/**
	 * Generates the key for the object.
	 *
	 * @param string $objectClass
	 * @param int $objectId
	 * @return string
	 */
	public function getCacheKey($objectClass, $objectId) {
		return sprintf('%s_%d', $objectClass, intval($objectId));
	}

	/**
	 * Removes the entry from the cache.
	 *
	 * @param string $key
	 * @return void
	 */
	public function remove($key) {
		if ($this->enabled) {
			if ($this->useMemcached) {
				$this->cache->delete($this->getKey($key));
			}
			else {
				apc_delete($this->getKey($key));
			}
		}
	}

	/**
	 * Sets the value to cache.
	 *
	 * @param string $key
	 * @param string $value
	 * @param int $timeout
	 * @return void
	 */
	public function set($key, $value, $timeout = 300, $cas = null) {
		if ($this->enabled) {
			$key = $this->getKey($key);
			if ($this->useMemcached) {
				if ($cas) {
					$this->cache->cas($cas, $key, $value, $timeout);
				}
				else {
					$this->cache->set($key, $value, $timeout);
				}
			}
			else {
				apc_store($key, $value, $timeout);
			}
		}
	}

	/**
	 * Makes the key for this cache.
	 *
	 * @param string $key
	 * @return string
	 */
	protected function getKey($key) {
		return $key;
	}

	/**
	 * Initializes the cache.
	 *
	 * @return bool
	 */
	protected function initCache() {
		$result = false;

		if ($this->useMemcached) {
			if (class_exists('Memcached')) {
				$this->cache = new Memcached();
				if (count($this->cache->getServerList()) == 0) {
					$result = $this->cache->addServer('127.0.0.1', 11211);
				}
				else {
					$result = true;
				}
				$this->cache->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
				$this->cache->setOption(Memcached::OPT_PREFIX_KEY, sprintf('%x', crc32($_SERVER['HTTP_HOST']) . '_smvc_'));
			}
		}
		else {
			$result = function_exists('apc_store');
		}

		return $result;
	}
}
