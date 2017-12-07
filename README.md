# RDDB A-la Kadar Documentation

## Basic Usage

See [example.php](example.php)

## Main Driver Functions

*   #### $db->error()

    > Get last error message

*   #### $db->ecode()

    > Get last error code

*   #### $db->simple_query($sql)

    > Execute $sql and return true or false

*   #### $db->query($sql)

    > Execute $sql and return corresponding result class depending on the query executed or false if error.
    > 
    > *   Return mysqli_db_list_result for default data grabbing query
    > *   Return mysqli_db_insert_result for insert query
    > *   Return mysqli_db_write_result for modification query

*   #### $db->fetch_query($sql, $type = rddb::FETCH_OBJECT)

    > Execute data grabbing query $sql, and automatically return fetched arrays, using 2nd argument to determine row type

*   #### $db->select($table, $select, $where, $join, $order, $limit, $offset, $group, $having)

    > Generate query string using the given parameters. Parameters can be put in one big array
    > 
    > Example:
    > 
    > <pre>$params = [
    > 	'select' => ['id', 'name'],
    > 	'join' => [
    > 		['othertable', 'othertable.id' => 'table.id', 'left']
    > 	]
    > 	'where' => [
    > 		'email' => $email
    > 	],
    > 	'limit' => 20,
    > 	'start' => 0
    > ];
    > $db->select('table', $params);
    > // Will generate something like: SELECT `id`, `name` FROM `table` LEFT JOIN `othertable` ON `othertable`.`id` = `table`.`id` WHERE `email` = 'test@example.com' LIMIT 0,20</pre>

*   #### $db->get($table, $select, $where, $join, $order, $limit, $offset, $group, $having)

    > Get list data from $table and return mysqli_db_list_result class, or false if error. The query generated using the same method as $db->select(), see above

*   #### $db->fetch_get($table, $select, $where, $join, $order, $limit, $offset, $group, $having)

    > Same as $db->get() but return the result same as $db->fetch_query()

*   #### $db->row($table, $type, $select, $where, $join, $order, $limit, $offset, $group, $having)

    > Get the first row of the result and return it as $type, rddb::FETCH_OBJECT is the default

*   #### $db->count_row($table, $where, $join, $group, $having) or $db->count($table, $where, $join, $group, $having)

    > Get total row generated by the given query parameters

*   #### $db->insert($table, $data)

    > Insert $data into $table. $data should be an array with the keys as the fields and values as inserted values
    > 
    > Example
    > 
    > <pre>$db->insert('test', [
    > 	'field' => 'value',
    > 	'field2' => 'value2'
    > ]);</pre>
    > 
    > NULL will be treated as NULL, a single element array as value will be treated as raw data/unescaped, only used when value use internal mysql function like ['NOW()']

*   #### $db->update($table, $data, $where)

    > Update $table using $data on $where condition. $where can be an array of conditions

*   #### $db->insert_update($table, $condition, $data)

    > Update $table using $data when $condition are met, or insert otherwise

*   #### $db->delete($table, $where, $limit)

    > Delete from $table using condition $where, if $limit specified, only delete that much row

*   #### $db->truncate($table)

    > Truncate $table. If arrays is given, well, truncate them 1 by 1

*   #### $db->protect_key($key) or rddb::_k($key, $db)

    > Protect key string by wraping them in key protector. Example if using mysqli: table => `table`
    > 
    > When using _k method to protect key, $db var is optional, the method will automatically get the first connection available to protect the key

*   #### $db->escape($string) or rddb::_x($string, $db)

    > Smart Check $string, Escape if detected as string by wrapping them using corresponding quote, Convert to 1 or 0 if boolean detected, NULL will be given if $string is exactly NULL or 'NULL', otherwise, return the $string as it is

*   #### $db->close()

    > Close current connection

*   #### $db->list_tables()

    > Get array of tables

*   #### $db->table_exists($table)

    > Check if $table is exist. Can use '#__' to automatically add table prefix specified in config.php. Example: prefix: 't_' then '#__table' will be translated to t_table

*   #### $db->list_fields($table)

    > Get array of fields for $table

*   #### $db->field_exists($table, $field)

    > Check if $field exists in $table

## Building $where

*   #### If $where is string

    > Will be treated as is, unprotected, unescaped

*   #### ['field' => 'value']

    > ``field` = 'value'`
    > 
    > If value contain character '(' it will not be escaped. To force value to be treated as string, you can manually escape the value, or use this: `['field' => new db_string_value('value')]`

*   #### ['field' => '(>|<|=|>=|<=|<>|!=|<=>|LIKE|IN|IS|REGEXP|NOT LIKE|IS NOT|NOT IN) value']

    > ``field` (>|<|=|>=|<=|<>|!=|<=>|LIKE|IN|IS|REGEXP|NOT LIKE|IS NOT|NOT IN) 'value'`

*   #### ['(OR|NOT|AND) field' => 'value']

    > <pre>(OR|NOT|AND) `field` = 'value'</pre>
    > 
    > NOT is used as an alternative to !=

*   #### ['(NOT) field' => NULL]

    > <pre>(NOT) `field` IS NULL</pre>
    > 
    > NOT is used as an alternative to IS NOT

*   #### ['(NOT) field' => ['value1', 'value2', 'value3']]

    > `(NOT) `field` IN ('value1', 'value2', 'value3')`
    > 
    > NOT in the beginning also is an alternative to NOT IN

*   #### ['(NOT) field' => '%value%']

    > `(NOT) `field` LIKE '%value%'`
    > 
    > NOT in the beginning also is an alternative to NOT LIKE

*   #### ['field' => ['BETWEEN', 'value1' => 'value2']]

    > ``field` BETWEEN 'value1' AND 'value2'`

*   #### [['field1' => 'value1', 'field2' => 'value2']]

    > `AND (`field1` => 'value1' AND `field2` => 'value2')`
    > 
    > Every sub array will also be treated as another where

*   #### ['OR' => ['field1' => 'value1', 'field2' => 'value2']]

    > `OR (`field1` => 'value1' AND `field2` => 'value2')`

## $join

*   <pre>[$table_to_join, $join_base_field => $table_base_field, $optional_join_type]
    OR
    [$table_to_join, $identical_base_field, $optional_join_type]
    							</pre>

*   Example:

    <pre>[
    	['table2', 'table2.id' => 'maintable.id'],
    	['table3', 'table3.id' => 'maintable.id', 'right'],
    	['table4', 'id'],
    ]</pre>

*   Result

    <pre>JOIN `table2` ON `table2`.`id` = `maintable`.`id`
    RIGHT JOIN `table3`.`id` ON `table3`.`id` = `maintable`.`id`
    JOIN `table4` USING(`id`)</pre>

## Result Functions

### Global Result Functions

*   #### $result->query_string()

    > Return the query executed in this result

*   #### $result->free()

    > Free the result from memory

### List Result Functions

*   #### $result->fetch($row_type = rddb::FETCH_OBJECT)

    > Fetch all data into array. Row type depends on the $row_type parameter given
    > 
    > *   rddb::FETCH_OBJECT - Default, row is a stdClass object
    > *   rddb::FETCH_ASSOC - Each row is an associative array with key is the field
    > *   rddb::FETCH_NUM - Each row is an numerative array with key is the order of the field on SELECT statement, starting from 0
    > *   rddb::FETCH_BOTH - Combination of ASSOC and NUM

*   #### $result->row($row_type)

    > Get the first for of the result. The result also automatically save all fetched rows

*   #### $result->num_rows()

    > Get total rows returned from the result, affected by LIMIT

*   #### $result->num_fields()

    > Get total fields returned from the result

### Insert Result Functions

*   #### $result->insert_id()

    > Get last inserted row ID

### Write Result Functions

*   #### $result->affected_rows()

    > Get the number of affected rows by the query, also available in insert result

* * *

# RDModel

## Basic Usage

See mymodel.php and [example2.php](example2.php)

## Setup Vars

*   #### public $_table

    > Required variable. Contain string of table name representated in the model class

*   #### public $_primary_key

    > Optional variable but better if declared. Contain string of table primary key field

*   #### public $_fields

    > Optional variable but better if declared. Contain array of strings of fields on the table
    > 
    > Example:
    > 
    > <pre>['id', 'name', 'test']</pre>
    > 
    > populate() method by default use this list for populating the data from datasource

*   #### public $_populated

    > Optional variable. Contain array of strings of fields on the table that will be populated when populate() method is called
    > 
    > Only declared if you have some data that should be set manually
    > 
    > Example:
    > 
    > <pre>['id', 'name']</pre>

*   #### public $_populate_null_on_empty

    > Optional variable. Set to false default. If true, all empty string will be NULL-ed when populated

*   #### public $_validation

    > Optional variable. Contain array of validator item used in rdvalidator class. Used when validate() method is called
    > 
    > <pre>[
    > 	'postfield' => [
    > 		['validation_rule', 'validation_param', 'error message', $optional_object]
    > 	]
    > ]</pre>
    > 
    > If $optional_object specified, that variable must contain a method named 'validate_{validation_rule}' with at least 2 parameter for $value and $param
    > 
    > Because of you can't declare a variable as value, $optional_object can only be added later inside another method or manually when calling validate() method
    > 
    > Example:
    > 
    > <pre>[
    > 	'name' => [
    > 		['required', true, 'Name is required'],
    > 		['min_length', 2, 'Name should be at least 2 characters']
    > 	]
    > ]</pre>

*   #### public $_structure

    > Optional variable. Used to generate table if not exists and/or automatically fill the $_fields var when not specified before checking directly to the table
    > 
    > Example:
    > 
    > <pre>[
    > 	'field' => [
    > 		'name' => 'field',
    > 		'type' => 'INT(11)',
    > 		'value' => NULL,
    > 		'null' => true,
    > 		'auto_increment' => true
    > 	]
    > ]</pre>

## Active Records

Active record, in my definition is that a record of a table can be created/accessed/modified through a model. In RDModel, you can access/set fields directly from a model just like accessing their public properties. Example:

<pre>$model->field1 = 'test';
$model->field2 = 'halo';</pre>

Setting a variable on an 'unloaded' record will treat them as new active record, unless the primary key field value is set then it will be an existing active record

To load an existing record from a table to be the current active record, use method load($id), where $id is the value of primary key to be loaded as an active record. Once loaded you can access and/or modify the data directly

<pre>$field1 = $model->field1;</pre>

New Data Example:

<pre>$model->field1 = 'test';
$model->field2 = 'value2';
$model->save();</pre>

Update Data Example:

<pre>$model->load(1);
$model->field2 = 'halo';
$model->save();</pre>

Force Insert

<pre>$model->id = 1;
$model->field1 = '1234';
$model->field2 = 'meow';
$model->save(true);
// OR $model->insert();</pre>

Update without loading the data

<pre>$model->field1 = '2345';
$model->update(1);</pre>

See section below for detail on functions

## Methods

*   #### $model->debug($status)

    > Set rddb debug flag

*   #### $model->populate($source, $prefix)

    > Populate the active record from $source. $source can be a string of 'post' or 'get' corresponding to php global request variables, or array containing key value pair for the data
    > 
    > When the fields specified in $_populated property, populate will only check those fields not the $_fields property
    > 
    > Also when $_populate_null_on_empty is set to true, all empty string will be NULL-ed

*   #### $model->to_array() or $model->__data()

    > Return current active record as array

*   #### $model->validate($addition, $populate)

    > Validate postfields using rule specified in `$_validation`. $addition is collection of rule outside $_validation property. You can pass a custom class validator this way. If $populate set to false, this method will only validate the postfields, you have to populate the data manually

*   #### $model->save($force_insert)

    > Save current active record to database. If $force_insert is set to true, the data will always be inserted, even when primary key value specified. Return the saved data as active record

*   #### $model->insert($data)

    > Insert $data into table. If $data is empty, the current active record will be inserted. Return last insert ID on success and false on error.

*   #### $model->update($id, $data)

    > Update $data from table for matching the primary key $id. Return number of affected rows or false.

*   #### $model->update_by($where, $data)

    > Update $data from table for matching condition $where

*   #### $model->ready_to_delete($id)

    > Method to be overrided. Check if data with primary key $id is valid to delete. By default only return true.

*   #### $model->delete($id)

    > Delete from table with primary key $id. If not specified will get from current active record

*   #### $model->delete_by($where)

    > Delete from table with condition matching $where

*   #### $model->load($id, $return)

    > Load data with primary key $id as current active record if $return is not specified or set to false. If $return set to true, the data will be returned as an object

*   #### $model->unload()

    > Reset current active record. Should be used when before trying to modify/insert multiple data

*   #### $model->get($select, $where, $join, $order, $limit, $offset, $group, $having)

    > Same as $db->get() except without $table parameter in the beginning

*   #### $model->count($where, $join, $group, $having)

    > Same as $db->count() except without $table parameter in the beginning

* * *

## RDValidator

A validation class used by RDModel

### Methods

*   #### $validator->init($validations, $reset)

    > Add starting validation rule. $validations is the same as $_validation in RDModel. Set $reset to true, to clear current rules before adding $validations

*   #### $validator->reset()

    > Clear all validation rules

*   #### $validator->add_rule($field, $rule, $message, $param, $customclass)

    > Add new validation rule. See $_validation for more detail

*   #### $validator->has_rule($field, $rule, $param, $customclass)

    > Check if $field has validation rule $rule. By default $param is false, and $customclass is NULL

*   #### $validator->validate($break)

    > Execute the validation process. If $break set to true, the method will return when the first error occured

*   #### $validator->error_summary($title, $html, $class)

    > Generate Error messages with $title. By default, $html is true and will return HTML string of div with class $class, h4 of $title, and unordered list of error message. If $html is set to false, will only return normal text of $title and list of error message

*   #### $validator->error_field($field)

    > Check if $field has error

### Provided Validation Rules

*   Name: required  
    Param: (true|'file')  
    Validation: Field must exists and is not empty, if param is 'file', will check $_FILES instead
*   Name: match  
    Param: {value to be compared}  
    Validation: Field value must match with param value
*   Name: length  
    Param: number  
    Validation: Field length must be exactly the same as param, if field value is an array, the number of the array will be counted instead
*   Name: min_length  
    Param: number  
    Validation: Field must have length at least the same as param, if field value is an array, the number of the array will be counted instead
*   Name: max_length  
    Param: number  
    Validation: Field length must not exceed param, if field value is an array, the number of the array will be counted instead
*   Name: preg_match  
    Param: regex pattern  
    Validation: Field must match with given pattern
*   Name: equal  
    Param: number  
    Validation: Field must be the same number as param
*   Name: min  
    Param: number  
    Validation: Field value must be bigger or equal to param
*   Name: max  
    Param: number  
    Validation: Field value must be lower or equal to param
*   Name: ip  
    Param: true  
    Validation: Field value must be a valid IP Address
*   Name: email  
    Param: true  
    Validation: Field value must be a valid email address
*   Name: url  
    Param: true  
    Validation: Field value must be a valid URL
*   Name: alphabet  
    Param: true  
    Validation: Field value can only contain Alphabet
*   Name: number  
    Param: true  
    Validation: Field value must be a number without decimal
*   Name: decimal  
    Param: true  
    Validation: Field value must be a valid decimal number
*   Name: alnum  
    Param: true  
    Validation: Field value can only contain Alphabet and number without decimal
*   Name: alnumplus  
    Param: number  
    Validation: Field value can only contain Alphabet, number, and these characters _ - .
*   Name: date  
    Param: ('iso'|'us'|true)  
    Validation: Field value must be in a valid date format as param. 'iso' = YYYY-MM-DD, 'us' = MM/DD/YYYY, true = DD/MM/YYYY
*   Name: datetime  
    Param: ('iso'|'us'|true)  
    Validation: Field value must be in a valid datetime format as param. 'iso' = YYYY-MM-DD HH:mm[:ss], 'us' = MM/DD/YYYY HH:mm[:ss] (AM|PM), true = DD/MM/YYYY HH:mm[:ss]