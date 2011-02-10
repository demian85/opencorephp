<?php


return array(
/**
 * Logs directory or file.
 * If a directory is provided, a folder will be created using the specified format below.
 * Log messages will be appended at the end of the file.
 */
'path'	=> APPLICATION_DIR . '/logs',
/**
 * Specify the date format used for naming automatically generated files.
 * Only when {logs.path} is a directory
 * It must be a valid format used for date() function.
 */
'file_name_format'	=> 'Y-m-d.\l\o\g',
/**
 * Log uncaught exceptions thrown by a controller automatically.
 * The default exception handler logs all exceptions.
 * You should disable this option unless you register your own exception handler, otherwise, exceptions will be logged twice.
 */
'log_exceptions'	=> false,
/**
 * Limit log file size in bytes.
 * Only when {logs.path} is a directory.
 * If limit is reached, a new file will be created.
 * 1MB = 1048576 bytes
 */
'max_file_size'		=> 1048576,
/**
 * Indicates default log location. You can combine any value using bit mask.
 * See log.Logger constants.
 * Valid options are:
 * 0 = Disable
 * 1 = File
 * 2 = Database
 * 4 = Email
 * 8 = FirePHP
 * 16 = STDOUT
 * 32 = ChromePHP
 */
'location'		=> 40,
/**
 * Email addresses that will receive log messages.
 * Separate multiple addresses by a comma.
 */
'emails'		=> 'demian@nicestream.com',
/**
 * Table name used for database logging. It must exist in the database.
 * Table definition can be found at log/Logger.php
 */
'db_table'		=> 'Logs',
/**
 * If database logging is enabled, this connection will be used.
 */
'db_connection'	=> 'default'
);
?>