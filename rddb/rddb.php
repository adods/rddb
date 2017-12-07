<?php
defined('DS') or define('DS', DIRECTORY_SEPARATOR);
defined('EXT') or define('EXT', '.php');

class rddb {

	const FETCH_OBJECT = 'object';
	const FETCH_ASSOC = 'assoc';
	const FETCH_NUM = 'row';
	const FETCH_BOTH = 'array';
	
	public static $error = '';

	private static $connections = array();

	public static function load($params = '') {
		$base = realpath(__DIR__);
		if (is_string($params) && strpos($params, '://') === false) {
			if (file_exists($base.DS.'config'.EXT)) {
				include $base.DS.'config'.EXT;
			}

			if (!isset($db) or count($db) == 0) {
				self::$error = 'Missing database configurations';
				return false;
			}

			if ($params != '') {
				$active_connection = $params;
			}

			if (!isset($active_connection) || !isset($db[$active_connection])) {
				self::$error = 'Your active database configuration does not '
						. 'exist';
				return false;
			}

			$params = $db[$active_connection];
		} else if (is_string($params)) {
			if (($url = @parse_url($params)) === false) {
				show_error('Invalid database connection string.');
			}

			$params = array(
				'driver' => $url['scheme'],
				'hostname' => (isset($url['host'])?rawurldecode($url['host']):''),
				'username' => (isset($url['user'])?rawurldecode($url['user']):''),
				'password' => (isset($url['pass'])?rawurldecode($url['pass']):''),
				'port' => (isset($url['port'])?rawurldecode($url['port']):''),
				'database' =>
					(isset($url['path'])?rawurldecode(substr($url['path'], 1)):''));
		} else if (is_array($params)) {
			$required = ['driver', 'hostname', 'username', 'password', 'database'];
			
			$missing = [];
			
			foreach ($required as $key) {
				if (!isset($params[$key])) {
					$missing[] = $key;
				}
			}
			
			if (count($missing)) {
				self::$error = 'Missing important connection parameters:'
						.implode(', ', $missing);
				return false;
			}
		}

		$serialized = md5(serialize($params));

		if (isset(self::$connections[$serialized])) {
			return self::$connections[$serialized];
		}

		if (!isset($params['driver']) || $params['driver'] == '') {
			self::$error = 'Missing database driver parameters';
			return false;
		}

		if (!file_exists($base.DS.'db'.DS.'drivers'.DS.$params['driver'].EXT)) {
			self::$error = 'Your selected database driver does not exist';
			return false;
		}

		require_once $base.DS.'db'.DS.'driver'.EXT;
		require_once $base.DS.'db'.DS.'drivers'.DS.$params['driver'].EXT;

		$driver = $params['driver'].'_rddb_driver';

		$db = new $driver($params);

		if ($db->auto_connect == true) {
			$db->connect($db->persistent);
		}

		self::$connections[$serialized] = $db;

		return self::$connections[$serialized];
	}
	
	public static function _x($string, $db = false) {
		if (!$db && count(self::$connections)) {
			$db = reset(self::$connections);
		} else {
			return $string;
		}
		return $db->escape($string);
	}
	
	public static function _k($key, $db = false) {
		if (!$db && count(self::$connections)) {
			$db = reset(self::$connections);
		} else {
			return $string;
		}
		return $db->protect_key($key);
	}
}
