#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/config.php');
	require_once(dirname(__FILE__) . '/phprouter/PHPRouter.php');
	require_once(dirname(__FILE__) . '/CustomIOSXRRouter.php');
	require_once(dirname(__FILE__) . '/CustomIOSRouter.php');

	$optind = null;

	// Parse CLI Arguments.
	$short = 'hpi:d';
	$long = ['help', 'power', 'int:', 'interface:', 'desc', 'json'];
	if (PHP_VERSION_ID < 70100) {
		$options = getopt($short, $long);
	} else {
		$options = getopt($short, $long, $optind);
	}

	$help = isset($options['help']) || isset($options['h']);
	$power = isset($options['power']) || isset($options['p']);
	$longDesc = isset($options['desc']) || isset($options['d']);

	$json = isset($options['json']);

	if ($help) {
		echo 'TODO: Some kind of help...', "\n";
		echo "\n";
		echo '-h, --help              - Show this help.', "\n";
		echo '-p, --power             - Include power output (Slower).', "\n";
		echo '-d, --desc              - Show long description.', "\n";
		echo '-i, --interface <int>   - Only show this interface.', "\n";
		echo '    --json              - Show output as JSON.', "\n";
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

	if (!$json) {
		echo sprintf('%-20s', 'Port');
		if (!$longDesc) {
			echo sprintf('%-30s', 'Name');
		}
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
		if ($longDesc) {
			echo sprintf('%-30s', 'Description');
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
	}

	$outputInfo = [];

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
			$outputLine = [];

			$desc = $data['desc'];
			if (!$longDesc && !$json) {
				$desc = strlen($desc) > 28 ? substr($desc, 0, 26). '...' : $desc;
			}

			$status = $data['status'];

			$outputLine['int'] = sprintf('%-20s', $int);
			if (!$longDesc && !$json) {
				$outputLine['desc'] = sprintf('%-30s', $desc);
			}
			if ($vlan) {
				$vl = isset($cont[$int]['vlan']) ? $cont[$int]['vlan'] : '--';
				$outputLine['vlan'] = sprintf('%-20s', $vl);
			}
			$outputLine['status'] = sprintf('%-20s', $status);

			$duplex = '--';
			$speed = '--';
			$type = '--';

			if (isset($cont[$int])) {
				$duplex = isset($cont[$int]['Duplex']) ? $cont[$int]['Duplex'] : '--';
				$speed = isset($cont[$int]['Speed']) ? $cont[$int]['Speed'] : '--';
				$type = isset($cont[$int]['Part number']) ? $cont[$int]['Part number'] : '--';
			}

			$outputLine['duplex'] = sprintf('%-15s', $duplex);
			$outputLine['speed'] = sprintf('%-10s', $speed);
			$outputLine['type'] = sprintf('%-20s', $type);

			if ($power) {
				$tx = isset($powerData[$int]['Tx Power']) ? $powerData[$int]['Tx Power'] : '--';
				$rx = isset($powerData[$int]['Rx Power']) ? $powerData[$int]['Rx Power'] : '--';

				$outputLine['tx'] = sprintf('%-30s', $tx);
				$outputLine['rx'] = sprintf('%-30s', $rx);
			}

			if ($longDesc || $json) {
				$outputLine['desc'] = sprintf('%-30s', $desc);
			}

			// Trim trailing spaces for json, or just echo for normal output.
			foreach ($outputLine as &$line) {
				if ($json) { $line = trim($line); } else { echo $line; }
			}

			if ($json) { $outputInfo[] = $outputLine; } else { echo "\n"; }
		}
	}


	if ($json) {
		echo json_encode($outputInfo, JSON_PRETTY_PRINT), "\n";
	}
