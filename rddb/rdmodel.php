<?php
defined('DS') or define('DS', DIRECTORY_SEPARATOR);
defined('EXT') or define('EXT', '.php');

class rdmodel {

	// Table name, Important
	public $_table = '';

	// Primary Key, Important
	public $_primary_key = 'id';

	// Table fields, used for populate data if populator not specified, Optional,
	// Automaticly filled if empty by reading structure var or table data
	public $_fields = array();

	// Basic Structure Data for Table Creation
	// example
	/*public $_structure = array(
		'field' => array(
			'name' => 'field',
			'type' => 'INT(11)',
			'value' => NULL,
			'null' => true,
			'auto_increment' => true
		)
	);*/
	public $_structure = array(); // Optional

	// Custom Populator, if specified, this array will be used when populate 
	// function called
	public $_populated = array();
	// If true, NULL will be the default instead of empty string
	public $_populate_null_on_empty = false;

	/* 
	 * For validation, using validator library
	 * example
	 * 
	 * public $_validation = array(
	 *		'field' => array(array('rules', 'param', 'message')),
	 *		'field2' => array(
	 *			array('rules', 'param', 'message'),
	 *			array('rules2', 'param', 'message'),
	 *			array('rules3', 'param', 'message')
	 *		)
	 * );
	 * 
	 * If you need to specify the custom function/class for validation, you can
	 * declare it in constructor after calling parent constructor, or add it 
	 * later when validating
	 */
	public $_validation = array(); // Optional

	public $cache = true;
	
	protected $_debug = false;

	protected $db;

	protected $data = array();

	protected $config;

	protected $_cache = array();

	public function __construct($config = false) {
		if ($config === false) {
			$config = '';
		}

		$this->config = $config;
		$this->init();
	}

	public function __set($name, $value) {
		if (in_array($name, $this->_fields)) {
			$this->data[$name] = $value;
		}
	}

	public function __get($name) {
		if (isset($this->data[$name])) {
			return $this->data[$name];
		}
		return NULL;
	}

	public function __isset($name) {
		return (isset($this->data[$name]))?true:false;
	}

	public function __unset($name) {
		if ($this->__isset($name)) unset($this->data[$name]);
	}

	protected function init() {
		$base = realpath(__DIR__);
		
		require_once $base.DS.'db'.EXT;
		
		$this->db = rddb::load($this->config);
		
		$this->db->debug = $this->_debug;

		if ($this->_table == '') {
			echo 'Please specify table name for '.
					str_replace('_model', '', get_class($this)).' model';
			exit;
		}

		if ($this->_primary_key == '') {
			echo 'Please specify primary key for table '.$this->_table;
			exit;
		}

		if (!$this->db->table_exists($this->_table)) {
			if (count($this->_structure) > 0) {
				$this->create_table();
			} else {
				echo 'Table '.$this->_table.' is not exists';
				exit;
			}
		}

		if (!count($this->_fields)) {
			if (count($this->_structure) > 0) {
				foreach ($this->_structure as $data) {
					$this->_fields[] = $data['name'];
				}
			} else {
				foreach ($this->db->list_fields($this->_table) as $field) {
					$this->_fields[] = $field;
				}
			}
		}
	}

	private function create_table() {
		$sql = "CREATE TABLE `".$this->_table."` ( ";

		foreach ($this->_structure as $key => $data) {
			$sql .= "`".$data['name']."` ".$data['type'];
			if ($data['null'] == false) {
				$sql .= " NOT NULL";
			}
			$sql .= " default ".((is_null($data['value']) && $data['null'] == true)
					? 'NULL' : $this->db->escape($data['value']));
			if ($data['auto_increment'] == true) {
				$sql .= " auto_increment";
			}
			$sql .= ", ";
		}

		$sql .= "PRIMARY KEY (`".$this->_primary_key."`)";
		$sql .= " ) ENGINE=InnoDB;";

		$this->db->query($sql);
	}
	
	public function debug($status = false) {
		$this->_debug = $status;
	}

	public function populate($from = 'post', $prefix = '') {
		if (is_array($from)) {
			$data = $from;
		} else {
			$data = ($from == 'post' ? $_POST : $_GET);
		}

		$loop = $this->_fields;
		if (count($this->_populated)) {
			$loop = $this->_populated;
		}

		foreach ($loop as $key) {
			$key2 = $key;
			if ($prefix != '') $key2 = $prefix.$key;
			if (isset($data[$key2])) {
				if (empty($data[$key2]) && $this->_populate_null_on_empty) {
					$this->data[$key] = NULL;
				} else {
					$this->data[$key] = $data[$key2];
				}
			} else if ($this->_populate_null_on_empty) {
				$this->data[$key] = NULL;
			}
		}
	}

	public function to_array() {
		return $this->data;
	}

	public function validate($adds = array(), $populate = true) {
		$base = realpath(__DIR__);
		require_once $base.DS.'validator'.EXT;
		
		$validator = new validator;
		$validator->init($this->_validation);
		
		if (!empty($adds)) {
			$validator->init($adds);
		}
		if ($validator->validate() === false) {
			return false;
		}

		if ($populate) {
			$this->populate();
		}

		return true;
	}

	public function save($force_insert = false) {
		if ($force_insert === true) {
			return $this->insert($this->data);
		}

		$pk = false;
		if (isset($this->data[$this->_primary_key])) {
			$pk = $this->data[$this->_primary_key];
			$old = $this->load($pk, true);
			if ($old->{$this->_primary_key} == $pk) 
				unset($this->data[$this->_primary_key]);
			unset($old);
		}

		$data = $this->data;
		if ($pk) {
			$data = $this->get_diff($pk, $data);
			if (empty($data)) {
				$r = 1;
			} else {
				$r = $this->update($pk, $data);
			}
		} else {
			$r = $pk = $this->insert($data);
		}

		if ($pk && $r) $this->load($pk);
		return $r;
	}

	protected function get_diff($pk, $data) {
		$old = $this->load($pk, true);

		$rdata = array();
		foreach ($data as $k => $v) {
			if ($old->{$k} != $v) {
				$rdata[$k] = $v;
			}
		}

		return $rdata;
	}

	public function insert($data) {
		$r = $this->db->insert($this->_table, $data);
		return $r ? $r->insert_id() : false;
	}

	public function update($id, $data) {
		$r = $this->db->update($this->_table, $data, 
				array($this->_primary_key => $id));
		if (!$r) {
			return false;
		}
		return $r->affected_rows();
	}

	public function update_by($where, $data) {
		$r = $this->db->update($this->_table, $data, $where);
		if (!$r) {
			return false;
		}
		return $r->affected_rows();
	}

	// override this
	public function ready_to_delete($id) {
		return true;
	}

	public function delete($id = false) {
		if ($id == false && !isset($this->data[$this->_primary_key])) {
			return false;
		}

		if ($id == false) {
			$id = $this->data[$this->_primary_key];
		}

		if (!$this->ready_to_delete($id)) {
			return false;
		}
		return $this->db->delete($this->_table, array($this->_primary_key => $id))
				->affected_rows();
	}

	public function delete_by($where = array()) {
		return $this->db->delete($this->_table, $where)->affected_rows();
	}

	public function load($id = false, $return = false) {
		if ($id == false && !isset($this->data[$this->_primary_key])) {
			return false;
		}

		if ($id == false) {
			$id = $this->data[$this->_primary_key];
		}

		if ($this->cache && isset($this->_cache[$id])) {
			if ($return) {
				return $this->_cache[$id];
			} else {
				$this->data = (array) $this->_cache[$id];
				return $this;
			}
		}

		$d['where'] = array($this->_primary_key => $id);
		$data = $this->db->get($this->_table, $d);

		if ($data->num_rows()) {
			$row = $data->row();
			$this->_cache[$id] = $row;
			if (!$return) {
				$this->data = array();
				foreach ($row as $key => $val) {
					$this->data[$key] = $val;
				}
				return $this;
			} else {
				return $this->_cache[$id];
			}
		} else {
			return NULL;
		}
	}

	public function load_by($where = false, $return = false) {
		if ($where == false) {
			return false;
		}

		$d['where'] = $where;
		$data = $this->db->get($this->_table, $d);

		if ($data->num_rows()) {
			$row = $data->row();
			$this->_cache[$row->id] = $row;
			if (!$return) {
				foreach ($row as $key => $val) {
					$this->data[$key] = $val;
				}
				$this->_cache[$this->data[$this->_primary_key]] = $this->data;
				return $this;
			} else {
				return $row;
			}
		} else {
			return false;
		}
	}

	public function unload() {
		$this->data = array();
		/*foreach ($this->_fields as $field) {
			unset($this->$field);
		}*/
	}

	public function get($select = '*', $where = '', $join = '', $order = '', 
			$limit = false, $offset = false, $group = '', $having = '') {
		if (is_array($select)) {
			if (isset($select['where'])) {
				$where = $select['where'];
				unset($select['where']);
			}
			if (isset($select['join'])) {
				$join = $select['join'];
				unset($select['join']);
			}
			if (isset($select['order'])) {
				$order = $select['order'];
				unset($select['order']);
			}
			if (isset($select['limit'])) {
				$limit = $select['limit'];
				unset($select['limit']);
			}
			if (isset($select['offset'])) {
				$offset = $select['offset'];
				unset($select['offset']);
			} else if ($select['start']) {
				$offset = $select['start'];
				unset($select['start']);
			}
			if (isset($select['group'])) {
				$group = $select['group'];
				unset($select['group']);
			}
			if (isset($select['having'])) {
				$having = $select['having'];
				unset($select['having']);
			}
			if (isset($select['select'])) {
				$sel = $select['select'];
				$select = $sel;
			}
			if ((is_array($select) && !isset($select[0])) || !$select) {
				$select = '*';
			}
		}

		return $this->db->get($this->_table, $select, $where, $join, $order, 
				$limit, $offset, $group, $having);
	}

	public function count($where = '', $join = '', $group = '', $having = '') {
		if (is_array($where)) {
			if (isset($where['join'])) {
				$join = $where['join'];
			}
			if (isset($where['group'])) {
				$group = $where['group'];
			}
			if (isset($where['having'])) {
				$having = $where['having'];
			}
			if (isset($where['where'])) {
				$where = $where['where'];
			}
		}
		return $this->db->count_row($this->_table, $where, $join, $group, 
				$having);
	}
	
	public function __data() {
		return $this->data;
	}
}
