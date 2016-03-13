<?php
	/**
	 * Class to interact with an iosxr router, over ssh.
	 */
	class IOSXRRouter extends CiscoRouter {
		/* {@inheritDoc} */
		function getPrefixList($name, $type = 'ipv4') {
			$type = ($type == 'ipv4' ? 'ip' : 'ipv6');
			$data = $this->exec('show run prefix-set ' . $name);
			$entries = array();
			foreach (explode("\n", $data) as $line) {
				if (preg_match('#([0-9a-f.:]+/[0-9]+[^,]+)#i', trim($line), $m)) {
					$entries[] = 'permit ' . strtolower(trim($m[1]));
				}
			}
			return $entries;
		}

		function enable($password = '', $username = '') {
			/* No-Op */
		}
	}
?>
