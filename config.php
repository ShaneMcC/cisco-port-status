<?php

	$routersINI = dirname(__DIR__) . '/routers.ini';

	// Load in local config if it exists.
	if (file_exists(dirname(__FILE__) . '/config.local.php')) {
		require_once(dirname(__FILE__) . '/config.local.php');
	}
