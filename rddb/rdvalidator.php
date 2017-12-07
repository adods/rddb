<?php
defined('DS') or define('DS', DIRECTORY_SEPARATOR);
defined('EXT') or define('EXT', '.php');

class rdvalidator {

	public $errors = array();

	protected $data = array();

	public function init($validations = array(), $reset = false) {
		if (!is_array($validations)) {
			return false;
		}

		if ($reset === true) {
			$this->reset();
		}

		foreach ($validations as $key => $data) {
			foreach ($data as $item) {
				$this->add_rule($key, $item[0], @$item[2], @$item[1], 
						@$item[3]);
			}
		}
	}

	public function reset() {
		$this->data = array();
		$this->errors = array();
	}

	public function add_rule($field = '', $rule = 'required', 
			$error_message = '', $params = false, $custom = NULL) {
		if (empty($field)) {
			return false;
		}

		if ($this->has_rule($field, $rule, $params, $custom)) {
			return false;
		}

		if (!isset($this->data[$field])) {
			$this->data[$field] = array();
		}
		$this->data[$field][] = array($rule, $params, $error_message, $custom);
	}

	public function validate($break = false) {
		foreach ($this->data as $field => $data) {
			$postdata = isset($_POST[$field]) ? $_POST[$field] : @$_GET[$field];
			//if ($postdata !== false) {
				foreach ($data as $validation) {
					$validator = 'validate_'.$validation[0];
					$params = $validation[1];
					if (is_object($validation[3])) {
						if (method_exists($validation[3], 'validate_'.$validation[0]) 
								&& is_callable(array($validation[3], 
									'validate_'.$validation[0]))) {
							$obj = $validation[3];
							if ($obj->$validator($postdata, $params, $field) 
									=== false) {
								$this->errors[$field][] = $validation[2];
								if ($break) break;
							}
							continue;
						}
					}

					if (function_exists($validator)) {
						if ($validation($postdata, $params, $field) === false) {
							$this->errors[$field][] = $validation[2];
							if ($break) break;
						}
						continue;
					}

					if (method_exists($this, 'validate_'.$validation[0]) 
							&& is_callable(array($this, 'validate_'.$validation[0]))) {
						if ($this->$validator($postdata, $params, $field) === false) {
							$this->errors[$field][] = $validation[2];
							if ($break) break;
						}
						continue;
					} else {
						$this->errors[$field][] = "Validator '".$validator
								."' does not exist";
						if ($break) break;
					}
				}
			//}
		}

		if (count($this->errors)) {
			return false;
		}

		return true;
	}

	public function error_summary($title = '', $html = true, $class = 'error') {
		if (count($this->errors) == 0) {
			return '';
		}

		$message = '';
		if ($html) {
			$message = '<div class="'.$class.'">'."\n";
			if ($title != '') {
				$message .= "\t<h4>".$title."</h4>";
			}
			$message .= "\t<ul>\n";
			foreach ($this->errors as $field => $err_msg) {
				$message .= "\t\t<li>".(count($err_msg) == 1 ? $err_msg[0] : '<ul>'
					. '<li>'.implode('</li><li>', $err_msg).'</li></ul>')."</li>\n";
			}
			$message .= "\t</ul>\n</div>\n";
		} else {
			if ($title != '') {
				$message .= $title."\n";
			}
			
			foreach ($this->errors as $field => $err_msg) {
				$message .= "- ".(count($err_msg) == 1 ? $err_msg[0] 
						: implode("\n- ", $err_msg))."\n";
			}
		}

		return $message;
	}

	public function error_field($field = '') {
		if ($field == '' || !isset($this->errors[$field])) {
			return '';
		}

		return $this->errors[$field];
	}

	public function has_rule($field, $rule, $params = false, $custom = NULL) {
		if (!isset($this->data[$field]) || empty($this->data[$field])) {
			return false;
		}

		foreach ($this->data[$field] as $data) {
			if ($data[0] == $rule && $data[1] == $params && $data[3] == $custom) {
				return true;
			}
		}
		return false;
	}

	public static function validate_required($value, $param, $field) {
		if ($param === 'file') {
			if (!isset($_FILES[$field]) 
					|| $_FILES[$field]['error'] == UPLOAD_ERR_NO_FILE) {
				return false;
			} else {
				return true;
			}
		}

		if (is_string($param) && empty($_GET[$param]) && empty($_POST[$param])) {
			return true;
		}
		
		if ($value === false) {
			return false;
		}

		if (!is_array($value)) {
			return (trim($value) == '')?false:true;
		} else {
			return (!empty($value));
		}
	}

	public static function validate_match($value, $param) {
		return self::validate_equal($value, $param);
	}

	public static function validate_length($value, $param) {
		if (is_array($value)) {
			return (count($value) == $param)?false:true;
		}

		if (function_exists('mb_strlen')) {
			return (mb_strlen($value) != $param)?false:true;
		}

		return (strlen($value) != $param)?false:true;
	}

	public static function validate_min_length($value, $param) {
		if (is_array($value)) {
			return (count($value) < $param)?false:true;
		}

		if (function_exists('mb_strlen')) {
			return (mb_strlen($value) < $param)?false:true;
		}

		return (strlen($value) < $param)?false:true;
	}

	public static function validate_max_length($value, $param) {
		if (is_array($value)) {
			return (count($value) > $param)?false:true;
		}

		if (function_exists('mb_strlen')) {
			return (mb_strlen($value) > $param)?false:true;
		}

		return (strlen($value) > $param)?false:true;
	}

	public static function validate_email($value) {
		return self::validate_preg_match($value, "/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_"
				. "\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix");
	}

	public static function validate_url($value) {
		return self::validate_preg_match(
				$value,
				'/^(https?):\/\/(([a-z0-9$_\.\+!\*\\\'\(\),;\?&=-]|%[0-9a-f]{2})+'
				. '(:([a-z0-9$_\.\+!\*\\\'\(\),;\?&=-]|%[0-9a-f]{2})+)?@)?(?#)'
				. '((([a-z0-9][a-z0-9-]*[a-z0-9]\.)*[a-z][a-z0-9-]*[a-z0-9]|'
				. '((\d|[1-9]\d|1\d{2}|2[0-4][0-9]|25[0-5])\.){3}'
				. '(\d|[1-9]\d|1\d{2}|2[0-4][0-9]|25[0-5]))(:\d+)?)'
				. '(((\/+([a-z0-9$_\.\+!\*\\\'\(\),;:@&=-]|%[0-9a-f]{2})*)*'
				. '(\?([a-z0-9$_\.\+!\*\\\'\(\),;:@&=-]|%[0-9a-f]{2})*)?)?)?'
				. '(#([a-z0-9$_\.\+!\*\\\'\(\),;:@&=-]|%[0-9a-f]{2})*)?$/i'
				);
	}

	public static function validate_alphabet($value) {
		return self::validate_preg_match($value, "/^([a-z])+$/i");
	}

	public static function validate_alnum($value) {
		return self::validate_preg_match($value, "/^([a-z0-9])+$/i");
	}

	public static function validate_alnumplus($value) {
		return self::validate_preg_match($value, "/^([ a-z0-9_\-\.])+$/i");
	}

	public static function validate_urltitle($value) {
		return self::validate_preg_match($value, "/^([a-z0-9_\-\.])+$/i");
	}

	public static function validate_number($value) {
		return self::validate_preg_match($value, "/^([0-9])+$/");
	}

	public static function validate_decimal($value) {
		return self::validate_preg_match($value, "/^\d+(\.\d+)?$/");
	}

	public static function validate_min($value, $param) {
		if (!is_numeric($value) && empty($value)) {
			return true;
		}
		return ($value < $param)?false:true;
	}

	public static function validate_max($value, $param) {
		if (empty($value)) {
			return true;
		}
		return ($value > $param)?false:true;
	}

	public static function validate_equal($value, $param) {
		if (empty($value)) {
			return true;
		}
		return ($value != $param)?false:true;
	}

	public static function validate_date($value, $param) {
		switch ($param) {
			case 'iso':
				$pattern = "/\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])/";
				break;
			case 'us':
				$pattern = "/(0[1-9]|1[0-2])\/(0[1-9]|[12][0-9]|3[01])\/\d{4}/";
				break;
			default:
				$pattern = "/(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/\d{4}/";
				break;
		}
		return self::validate_preg_match($value, $pattern);
	}

	public static function validate_datetime($value, $param) {
		switch ($param) {
			case 'iso':
				$pattern = "/\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])\s"
					. "([01][0-9]|2[0-3]):([0-5][0-9])(:([0-5][0-9]))?/";
				break;
			case 'us':
				$pattern = "/(0[1-9]|1[0-2])\/(0[1-9]|[12][0-9]|3[01])\/\d{4}\s"
					. "(0[1-9]|1[0-2]):([0-5][0-9])(:([0-5][0-9]))?\s(A|P)M/";
				break;
			default:
				$pattern = "/(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/\d{4}\s"
					. "([01][0-9]|2[0-3]):([0-5][0-9])(:([0-5][0-9]))?/";
				break;
		}
		return self::validate_preg_match($value, $pattern);
	}

	public static function validate_preg_match($value, $param) {
		if (empty($value)) {
			return true;
		}
		return (!preg_match($param, $value))?false:true;
	}
	
	public static function validate_ip($value) {
		if (empty($value)) {
			return true;
		}
		return (!filter_var($value, FILTER_VALIDATE_IP))?false:true;
	}
}
