<?php

	namespace shanemcc\PhpRouter\Implementations;

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
			$result = $this->getStreamData(['Username:', ">\n", "#\n", "> \n", "# \n"], true);

			// If we are prompted for the username again then we are wrong.
			return ($result != "Username:");
		}

		/* {@inheritDoc} */
		public function connect() {
			$this->canary = '! ' . md5(uniqid(true));
			$this->execCommandWraps = false;

			$this->socket->connect();
			$this->getStreamData(true);
			$this->socket->write("\n");
			$this->getStreamData("\n");
			$this->socket->write("\n");
			$data = $this->getStreamData([">\n", "#\n", "> \n", "# \n"], true);
			$this->socket->write("\n");
			$data = $this->getStreamData([">\n", "#\n", "> \n", "# \n"], true);
			$this->breakString = rtrim($data, "\n");

			$this->exec('terminal width 500');
			$this->exec('terminal length 0');

			if (method_exists($this, 'postConnect')) {
				$this->postConnect();
			}
		}

		/* {@inheritDoc} */
		function enable($password = '', $username = '') {
			if (!empty($password)) {
				$this->socket->write("enable\n");
				$this->getStreamData(array("Password: ", ">", "#"));
				if ($this->getLastBreakString() == "Password: ") {
					$this->socket->write($password . "\n");
				} else {
					$this->socket->write("\n");
				}
				$this->socket->write("\n");
				$this->getStreamData([">\n", "#\n"]);
			}
			$data = $this->getStreamData([">", "#"], true);
			$this->breakString = rtrim($data, "\n");
		}

		function getCanary() {
			return $this->canary;
		}
	}
