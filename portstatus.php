#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/config.php');
	require_once(dirname(__FILE__) . '/phprouter/PHPRouter.php');
	require_once(dirname(__FILE__) . '/CustomIOSXRRouter.php');
	require_once(dirname(__FILE__) . '/CustomIOSRouter.php');

	$optind = null;

	// Parse CLI Arguments.
	if (PHP_VERSION_ID < 70100) {
		$options = getopt('hpi:', ['help', 'power', 'int:', 'interface:']);
	} else {
		$options = getopt('hpi:', ['help', 'power', 'int:', 'interface:'], $optind);
	}

	$help = isset($options['help']) || isset($options['h']);
	$power = isset($options['power']) || isset($options['p']);

	if ($help) {
		echo 'TODO: Some kind of help...', "\n";
		echo "\n";
		echo '-h, --help              - Show this help.', "\n";
		echo '-p, --power             - Include power output (Slower).', "\n";
		echo '-i, --interface <int>   - Only show this interface.', "\n";
		die(1);
	}

	if (PHP_VERSION_ID < 70100) {
		// Assume host is last.
		$pos_args = array_slice($argv, $argc - 1);
	} else {
		$pos_args = array_slice($argv, $optind);
	}

	$host = isset($pos_args[0]) ? $pos_args[0] : '';

	if (empty($host)) {
		echo 'Please provide a host.', "\n";
		die(1);
	}

	$interfaces = [];
	if (isset($options['i'])) {
		if (!is_array($options['i'])) { $options['i'] = [$options['i']]; }
		$interfaces = array_merge($interfaces, $options['i']);
	}
	if (isset($options['int'])) {
		if (!is_array($options['int'])) { $options['int'] = [$options['int']]; }
		$interfaces = array_merge($interfaces, $options['int']);
	}
	if (isset($options['interfaces'])) {
		if (!is_array($options['interfaces'])) { $options['interfaces'] = [$options['interfaces']]; }
		$interfaces = array_merge($interfaces, $options['interfaces']);
	}

	if (empty($interfaces)) { $interfaces[] = '*'; }

	$routers = parse_ini_file($routersINI, true);

	if (!isset($routers[$host])) {
		echo $host, ' is not defined in ', $routersINI, "\n";
		die(1);
	}

	$r = $routers[$host];

	if (!isset($r['address'])) { die('No Address for ' . $host . "\n"); }
	if (!isset($r['user'])) { die('No Username for ' . $host . "\n"); }
	if (!isset($r['pass'])) { die('No Password for ' . $host . "\n"); }

	$sock = 'ssh';
	if (isset($r['socket']) && $r['socket'] == 'openssh') {
		$sock = new OpenSSHShellSocket($r['address'], $r['user'], $r['pass']);
	} else if (isset($r['socket'])) {
		$sock = $r['socket'];
	}

	$vlan = false;
	if ($r['type'] == 'cisco' || $r['type'] == 'ios') {
		$vlan = true;
		$dev = new CustomIOSRouter($r['address'], $r['user'], $r['pass'], $sock);
	} else if ($r['type'] == 'iosxr') {
		$dev = new CustomIOSXRRouter($r['address'], $r['user'], $r['pass'], $sock);
	} else {
		die('Invalid Router Type for ' . $host . "\n");
	}

	$dev->connect();

	echo sprintf('%-20s', 'Port');
	echo sprintf('%-30s', 'Name');
	if ($vlan) {
		echo sprintf('%-20s', 'Vlan');
	}
	echo sprintf('%-20s', 'Status');
	echo sprintf('%-15s', 'Duplex');
	echo sprintf('%-10s', 'Speed');
	echo sprintf('%-20s', 'Type');
	if ($power) {
		echo sprintf('%-30s', 'Tx Power');
		echo sprintf('%-30s', 'Rx Power');
	}
	echo "\n";

	echo str_repeat('-', 20);
	echo str_repeat('-', 30);
	if ($vlan) {
		echo str_repeat('-', 20);
	}
	echo str_repeat('-', 20);
	echo str_repeat('-', 15);
	echo str_repeat('-', 10);
	echo str_repeat('-', 20);
	if ($power) {
		echo str_repeat('-', 30);
		echo str_repeat('-', 30);
	}
	echo "\n";

	while ($i = array_shift($interfaces)) {

		if ($dev->isBundle($i)) {
			$members = $dev->getBundlePorts($i);
			foreach (array_reverse($members) as $m) { array_unshift($interfaces, $m); }
		}

		$cont = $dev->getControllerInfo($i);
		$intdata = $dev->getInterfaces($i);
		if ($power) {
			$powerData = $dev->getPowerInfo($i);
		}

		foreach ($intdata as $int => $data) {
			$desc = $data['desc'];
			$desc = strlen($desc) > 28 ? substr($desc, 0, 26). '...' : $desc;

			$status = $data['status'];

			echo sprintf('%-20s', $int);
			echo sprintf('%-30s', $desc);
			if ($vlan) {
				$vl = isset($cont[$int]['vlan']) ? $cont[$int]['vlan'] : '--';
				echo sprintf('%-20s', $vl);
			}
			echo sprintf('%-20s', $status);

			$duplex = '--';
			$speed = '--';
			$type = '--';

			if (isset($cont[$int])) {
				$duplex = isset($cont[$int]['Duplex']) ? $cont[$int]['Duplex'] : '--';
				$speed = isset($cont[$int]['Speed']) ? $cont[$int]['Speed'] : '--';
				$type = isset($cont[$int]['Part number']) ? $cont[$int]['Part number'] : '--';
			}

			echo sprintf('%-15s', $duplex);
			echo sprintf('%-10s', $speed);
			echo sprintf('%-20s', $type);

			if ($power) {
				$tx = isset($powerData[$int]['Tx Power']) ? $powerData[$int]['Tx Power'] : '--';
				$rx = isset($powerData[$int]['Rx Power']) ? $powerData[$int]['Rx Power'] : '--';

				echo sprintf('%-30s', $tx);
				echo sprintf('%-30s', $rx);
			}
			echo "\n";
		}
	}
