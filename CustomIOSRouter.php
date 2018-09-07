<?php

	class CustomIOSRouter extends IOSXRRouter {
		public function getInterfaces($int = '*') {
			if ($int == '*') { $int = ''; }
			$int = $this->exec('show int ' . $int . ' desc');
			$data = [];
			$header = false;

			foreach (explode("\n", $int) as $line) {
				$line = trim($line);

				if ($header) {
					$line = str_replace('admin down', 'admin-down', $line);
					$info = preg_split('#\s+#', $line, 4);
					$data[$info[0]] = ['status' => $info[1], 'protocol' => $info[2], 'desc' => isset($info[3]) ? $info[3] : ''];

				} else if (!empty($line)) {
					$header = true;
				}
			}

			return $data;
		}

		public function getControllerInfo($port = '*') {
			if ($port == '*') { $port = ''; }
			$int = $this->exec('show int ' . $port . ' status');
			$data = [];
			$header = false;

			foreach (explode("\n", $int) as $line) {
				$line = trim($line);

				if ($header) {
					$portInfo = preg_split('#\s+#', $line);
					$line = substr($line, 32);
					$line = str_replace('No Transceiver', 'No-Transceiver', $line);

					$info = preg_split('#\s+#', $line);
					$data[$portInfo[0]] = ['status' => $info[0], 'vlan' => $info[1], 'Duplex' => $info[2], 'Speed' => $info[3], 'Part number' => isset($info[4]) ? $info[4] : '--'];
				} else if (!empty($line)) {
					$header = true;
				}
			}

			return $data;
		}

		public function getPowerInfo($port = '*') {
			if ($port == '*') { $port = ''; }
			$int = $this->exec('show int ' . $port . ' transceiver');
			$data = [];
			$header = false;

			foreach (explode("\n", $int) as $line) {
				$line = trim($line);

				if ($header) {
					$info = preg_split('#\s+#', $line);

					$data[$info[0]] = ['Tx Power' => $info[4] . ' dBm', 'Rx Power' => $info[5] . ' dBm'];
				} else if (preg_match('#^-+#', $line)) {
					$header = true;
				}
			}

			return $data;
		}

		public function getBundlePorts($port) {
			$bundle = $this->exec('show int ' . $port);

			$members = [];
			foreach (explode("\n", $bundle) as $line) {
				$line = trim($line);

				if (preg_match('#^Members in this channel: (.*)$#', $line, $m)) {
					$members = explode(' ', $m[1]);
				}
			}

			return $members;
		}

		public function isBundle($port) {
			return preg_match('#^(Po|Port-Channel)\s?[0-9]+$#i', $port);
		}
	}
