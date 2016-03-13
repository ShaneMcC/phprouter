<?php
	/**
	 * Class to interact with a cisco router.
	 */
	class CiscoRouter extends Router {

		/** {@inheritDoc} */
		public function handleAuth($socket) {
			$this->getStreamData('Username:');
			$this->socket->write($socket->getUser());
			$this->socket->write("\n");
			$this->getStreamData('Password:');
			$this->socket->write($socket->getPass());
			$this->socket->write("\n");
			$this->getStreamData("\n");

			$this->socket->write("\n");
			$result = $this->getStreamData(array('Username:', ">\n", "#\n"), true);

			// If we are prompted for the username again then we are wrong.
			return ($result != "Username:");
		}

		/* {@inheritDoc} */
		public function connect() {
			$this->socket->connect();
			$this->socket->write("\n");
			$this->getStreamData("\n");
			$this->socket->write("\n");
			$data = $this->getStreamData(array(">\n", "#\n"), true);
			$this->breakString = rtrim($data, "\n");
			$data = $this->exec('term len 0');
		}

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

		/* {@inheritDoc} */
		function enable($password = '', $username = '') {
			$this->socket->write("enable\n");
			$this->socket->write($password . "\n");
			$this->socket->write("\n");
			$this->getStreamData("Password: \n");
			$data = $this->getStreamData(array(">\n", "#\n"), true);
			$this->breakString = rtrim($data, "\n");
		}
	}
?>
