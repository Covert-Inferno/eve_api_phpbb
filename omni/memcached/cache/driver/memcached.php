<?php
/**
*
* This file is part of the phpBB Forum Software package.
*
* @copyright (c) phpBB Limited <https://www.phpbb.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*
* For full copyright and license information, please see
* the docs/CREDITS.txt file.
*
*/

namespace phpbb\cache\driver;

if (!defined('PHPBB_ACM_MEMCACHED_PORT'))
{
	define('PHPBB_ACM_MEMCACHED_PORT', 11211);
}

if (!defined('PHPBB_ACM_MEMCACHED_HOST'))
{
	define('PHPBB_ACM_MEMCACHED_HOST', 'localhost');
}

if (!defined('PHPBB_ACM_MEMCACHED'))
{
	//can define multiple servers with host1/port1,host2/port2 format
	define('PHPBB_ACM_MEMCACHED', PHPBB_ACM_MEMCACHED_HOST . '/' . PHPBB_ACM_MEMCACHED_PORT);
}

/**
* ACM for Memcached
*/
class memcached extends \phpbb\cache\driver\memory
{
	var $extension = 'memcached';

	var $memcached;

	function __construct()
	{
		// Call the parent constructor
		parent::__construct();

		$this->memcached = new \Memcached();
		$i = 0;
		foreach(explode(',', PHPBB_ACM_MEMCACHED) as $u)
		{
			$parts = explode('/', $u);
			$this->memcached->addServer(trim($parts[0]), trim($parts[1]), $i);
			$i++;
		}
	}

	/**
	* {@inheritDoc}
	*/
	function unload()
	{
		parent::unload();

		$this->memcached->quit();
	}

	/**
	* {@inheritDoc}
	*/
	function purge()
	{
		$this->memcached->flush();

		parent::purge();
	}

	/**
	* Fetch an item from the cache
	*
	* @access protected
	* @param string $var Cache key
	* @return mixed Cached data
	*/
	function _read($var)
	{
		$age_data = $this->memcached->get($this->key_prefix . $var . '_age');
		$age_result = $this->memcached->getResultCode();

		if ($age_result != 0)
		{
			return false;
		}

		$age = time() - (int) $age_data['age'];

		if ($age > (int) $age_data['ttl'])
		{
			return false;
		}

		$read = $this->memcached->get($this->key_prefix . $var);
		$read_result = $this->memcached->getResultCode();

		if ($read_result != 0)
		{
			return false;
		}

		return $read;
	}

	/**
	* Store data in the cache
	*
	* @access protected
	* @param string $var Cache key
	* @param mixed $data Data to store
	* @param int $ttl Time-to-live of cached data
	* @return bool True if the operation succeeded
	*/
	function _write($var, $data, $ttl = 2592000)
	{
		$time = time();

		$replace = $this->memcached->replace($this->key_prefix . $var, $data, $ttl);

		$set = $set_age = false;

		if (!$replace)
		{
			$set = $this->memcached->set($this->key_prefix . $var, $data, $ttl);
		}

		$replace_age = $this->memcached->replace($this->key_prefix . $var . '_age', array('age' => $time, 'ttl' => $ttl), $ttl);

		if (!$replace_age)
		{
			$set_age = $this->memcached->set($this->key_prefix . $var . '_age', array('age' => $time, 'ttl' => $ttl), $ttl);
		}

		return (($replace || $set) && ($replace_age || $set_age));
	}

	/**
	* Remove an item from the cache
	*
	* @access protected
	* @param string $var Cache key
	* @return bool True if the operation succeeded
	*/
	function _delete($var)
	{
		return $this->memcached->delete($this->key_prefix . $var);
	}
}
