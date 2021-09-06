<?php
/** Debug Functions */
function logger(string $message) {
	$log_file = WP_PLUGIN_DIR.'/shipday-plugin'. '/log.txt';
	$file = file_exists($log_file) ? fopen($log_file, 'a') : fopen($log_file, 'w');
	fwrite($file, $message."\n");
	fclose($file);
}
?>