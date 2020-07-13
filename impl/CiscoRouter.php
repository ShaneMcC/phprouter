<?php
	/**
	 * Class to interact with a cisco router.
	 */
	class CiscoRouter extends Router {
		use CiscoTrait;

		/* {@inheritDoc} */
		function getPrefixList($name, $type = 'ipv4') {
			$type = ($type == 'ipv4' ? 'ip' : 'ipv6');
			$data = $this->exec('show ' . $type . ' prefix-list ' . $name);

			$entries = array();
			foreach (explode("\n", $data) as $line) {
				if (preg_match('#seq ([0-9]+) (.*)$#', trim($line), $m)) {
					$entries[$m[1]] = strtolower(trim($m[2]));
				}
			}
			return $entries;
		}


		function postConnect() {
			// Check for potential IPv6 bug.
			$data = $this->exec('show ipv6');
			if (stristr($data, 'Invalid input detected')) {
				if ($this->isDebug()) {
					echo '! IPv6 Bug detected.', "\n";
				}
				$this->exec('routing-context vrf global');
			}
		}
	}

