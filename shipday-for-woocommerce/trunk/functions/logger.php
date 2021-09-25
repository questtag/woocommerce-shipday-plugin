<?php
/** Debug Functions */
function logger(string $level, string $message) {
	$wc_logger = wc_get_logger();
    $context = array('source' => 'Shipday');
    $wc_logger->log($level, $message, $context);
}
?>