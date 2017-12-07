<?php
require_once './rddb/rddb.php';

// Load default connection from the config.php
$db = rddb::load();

if (!$db) {
	die(rddb::$error);
}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Basic Example</title>
	</head>
	<body>
		<p>
			<?php
			// Standard grab
			$result1 = $db->get('user');

			// Get total rows generated in query
			echo 'Total Row: '.$result1->num_rows().'<br />';
			?>
		</p>
		<ul>
			<?php
			/* Get all result, by default each row is a stdClass
			 * If you want different row type, you can pass these options
			 * as a parameter:
			 *	   rddb::FETCH_ASSOC => each row is an associative array
			 *	   rddb::FETCH_NUM => each row is a numerative array, 0 is the first
			 *	                      field
			 *	   rddb::FETCH_BOTH => combination of ASSOC and NUM
			 *	   rddb::FETCH_OBJECT => each row is a stdClass object
			 */
			$list = $result1->fetch(rddb::FETCH_ASSOC);
			?>
			<?php foreach ($list as $row): ?>
			<li>
				<pre><?php var_dump($row) ?></pre>
			</li>
			<?php endforeach; ?>
		</ul>
	</body>
</html>