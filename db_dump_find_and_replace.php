<?php
/*
Description:
  Perform a find and replace in database dump.
  Also correct the PHP serialize found.

Usage:
  $ drush php-script db_dump_find_and_replace
*/

$sql_file_path = '/path/to/dump.sql';
$old_domain = 'old.domain.com';
$domain = 'new.domain.com';

// Database
drush_shell_exec('sed -i "s/'.str_replace('.', '\.', $old_domain).'/'.$domain.'/g" '.$sql_file_path);
// Correcting of lenght of string in PHP serialized
$new_dump_sql = preg_replace_callback('/(s:)([0-9]*)(:\\\")([^"]*'.str_replace('.', '\.', $domain).'[^"]*)(\\\")/', function ($m){
  return($m[1].strlen($m[4]).$m[3].$m[4].$m[5]);
}, file_get_contents($sql_file_path));
file_put_contents($sql_file_path, $new_dump_sql);
?>