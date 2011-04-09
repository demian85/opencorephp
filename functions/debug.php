<?php

import('log.Logger');

function debug($object) {
	return Logger::getInstance()->log($object, Logger::TYPE_TEXT, Logger::LOG_CHROMEPHP | Logger::LOG_FIREPHP);
}

?>
