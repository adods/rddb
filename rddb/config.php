<?php

$active_connection = 'development';

// Local
$db['development']['driver'] = 'mysqli';
$db['development']['hostname'] = 'localhost';
$db['development']['username'] = 'root';
$db['development']['password'] = '';
$db['development']['database'] = 'test';
$db['development']['table_prefix'] = '';
$db['development']['port'] = '';
$db['development']['charset'] = 'utf8';
$db['development']['dbcollat'] = 'utf8_general_ci';
$db['development']['auto_connect'] = true;

// Online
$db['production']['driver'] = 'mysqli';
$db['production']['hostname'] = 'localhost';
$db['production']['username'] = 'dbuser';
$db['production']['password'] = '';
$db['production']['database'] = 'onlinedb';
$db['production']['table_prefix'] = '';
$db['production']['port'] = '';
$db['production']['charset'] = 'utf8';
$db['production']['dbcollat'] = 'utf8_general_ci';
$db['production']['auto_connect'] = true;
