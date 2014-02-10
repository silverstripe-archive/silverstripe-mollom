<?php

define('MOLLOM_PATH', dirname(__FILE__));

$dir = explode(DIRECTORY_SEPARATOR, MOLLOM_PATH);
define('MOLLOM_DIR', array_pop($dir));