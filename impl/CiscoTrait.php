<?php
	/**
	 * Common Traits for cisco devices.
	 */
	trait CiscoTrait {
		private $canary;

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
			$this->canary = '! ' . md5(uniqid(true));
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

			if (method_exists($this, 'postConnect')) {
				$this->postConnect();
			}
		}

		/* {@inheritDoc} */
		function enable($password = '', $username = '') {
			$this->socket->write("enable\n");
			if (!empty($password)) {
				$this->socket->write($password . "\n");
				$this->socket->write("\n");
				$this->getStreamData("Password: \n");
				$this->getStreamData(array(">\n", "#\n"));
			}
			$data = $this->getStreamData(array(">", "#"), true);
			$this->breakString = rtrim($data, "\n");
		}

		function getCanary() {
			return $this->canary;
		}
	}
