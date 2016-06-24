<?php
	/**
	 * Common Traits for cisco devices.
	 */
	trait CiscoTrait {
		/* {@inheritDoc} */
		public function handleAuth($socket) {
			$this->getStreamData('Username:');
			$this->socket->write($socket->getUser());
			$this->socket->write("\n");
			$this->getStreamData('Password:');
			$this->socket->write($socket->getPass());
			$this->socket->write("\n");
			$this->getStreamData("\n");

			$this->socket->write("\n");
			$result = $this->getStreamData(array('Username:', ">\n", "#\n", "> \n", "# \n"), true);

			// If we are prompted for the username again then we are wrong.
			return ($result != "Username:");
		}

		/* {@inheritDoc} */
		public function connect() {
			$this->execCommandWraps = false;

			$this->socket->connect();
			$this->socket->write("\n");
			$this->getStreamData("\n");
			$this->socket->write("\n");
			$data = $this->getStreamData(array(">\n", "#\n", "> \n", "# \n"), true);
			$this->socket->write("\n");
			$data = $this->getStreamData(array(">\n", "#\n", "> \n", "# \n"), true);
			$this->breakString = rtrim($data, "\n");

			$this->exec('term width 500');
			$this->exec('term len 0');
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
