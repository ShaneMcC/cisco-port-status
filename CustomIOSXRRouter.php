<?php

	class CustomIOSXRRouter extends IOSXRRouter {
		public function getInterfaces($int = '*') {
			$int = $this->exec('show int ' . $int . ' desc');
			$data = [];

			$header = false;

			foreach (explode("\n", $int) as $line) {
				$line = trim($line);

				if ($header) {
					$info = preg_split('#\s+#', $line, 4);
					$data[$info[0]] = ['status' => $info[1], 'protocol' => $info[2], 'desc' => isset($info[3]) ? $info[3] : ''];

				} else if (preg_match('#^-+$#', $line)) {
					$header = true;
				}
			}

			return $data;
		}

		public function getControllerInfo($port = '*') {
			$port = explode("\n", $port)[0];
			if ($port == '*') { $port = 'Te *'; }
			$cont = $this->exec('show controllers ' . $port);

			$data = [];
			$int = '';

			foreach (explode("\n", $cont) as $line) {
				$line = trim($line);

				if (preg_match('#Operational data for interface ([^:]+):#', $line, $m)) {
					$int = $m[1];
					$int = str_replace('TenGigE', 'Te', $int);

					$data[$int] = [];
				} else if (preg_match('#((?:Administrative|Operational|LED) state|Media type|Vendor|(?:Part|Serial) number|Operational address|Speed|Duplex|Flowcontrol|MTU): (.*)#', $line, $m)) {

					$data[$int][$m[1]] = $m[2];
				}
			}

			return $data;
		}

		public function getPowerInfo($port = '*') {
			$port = explode("\n", $port)[0];
			if ($port == '*') { $port = 'Te *'; }
			$cont = $this->exec('show controllers ' . $port . ' phy | i "(PHY data for interface:|x Pow)"');

			$data = [];
			$int = '';

			foreach (explode("\n", $cont) as $line) {
				$line = trim($line);

				if (preg_match('#PHY data for interface: ([^:]+)#', $line, $m)) {
					$int = $m[1];
					$int = str_replace('TenGigE', 'Te', $int);

					$data[$int] = [];
				} else if (preg_match('#((?:[TR]x Power)): (.*)#', $line, $m)) {
					$data[$int][$m[1]] = $m[2];
				}
			}

			return $data;
		}

		public function getBundlePorts($port) {
			$bundle = $this->exec('show int ' . $port);

			$count = 0;
			$members = [];
			foreach (explode("\n", $bundle) as $line) {
				$line = trim($line);

				if (preg_match('#No. of members in this bundle: ([0-9]+)#', $line, $m)) {
					$count = $m[1];
				} else if ($count > 0) {
					$members[] = explode(' ', $line)[0];
					$count--;
				}
			}

			return $members;
		}

		public function isBundle($port) {
			return preg_match('#^(BE|Bundle-Ether)\s?[0-9]+$#i', $port);
		}
	}
