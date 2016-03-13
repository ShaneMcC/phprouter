<?php
	/**
	 * Class to interact with a HP Procurve switch.
	 */
	class HPProcurve extends NetSwitch {

		private $hasAcceptedEULA = false;

		/** {@inheritDoc} */
		public function handleAuth($socket) {
			$this->getStreamData('Press any key to continue');
			$this->socket->write("\n");
			$this->hasAcceptedEULA = true;

			$this->sendUserPass($socket->getUser(), $socket->getPass());

			$this->socket->write("\n");
			$this->getStreamData(array('Username:', 'Password:', "> \n", "# \n"));
			$result = $this->getLastBreakString();

			// If we are prompted for the username/password again then we are wrong.
			return ($result != 'Username:' && $result != 'Password:');
		}

		/**
		 * Send username/password to device.
		 * Depending on the config, the device may ask for:
		 *  - Both username/password
		 *  - Just password
		 *  - Nothing
		 *
		 * This handles all 3 cases.
		 *
		 * @param $username Username to send if requested.
		 * @param $password Password to send if requested.
		 * @return Nothing
		 */
		private function sendUserPass($username, $password) {
			$this->getStreamData(array('Username: ', 'Password: ', "> ", "# "));
			if ($this->getLastBreakString() == 'Username: ') {
				$this->socket->write($username);
				$this->socket->write("\n");
				$this->getStreamData('Password: ');
			}
			if ($this->getLastBreakString() == 'Password: ') {
				$this->socket->write($password);
				$this->socket->write("\n");
			} else {
				$this->socket->write("\n");
			}
			$this->getStreamData("\n");
		}

		/**
		 * Discover what the current prompt is.
		 *
		 * @return The prompt.
		 */
		private function learnPrompt() {
			$this->socket->write("\n");
			$this->getStreamData("\n");
			$this->socket->write("\n");
			$data = $this->getStreamData(array("> \n", "# \n"), true);
			return rtrim($data, "\n");
		}

		/* {@inheritDoc} */
		public function connect() {
			$this->swallowControlCodes = true;
			$this->insertNewLineAfterANSI = true;

			// Admins can turn off paging, normal users can not... lame.
			$this->hasPager = true;
			$this->pagerString = "\n\n-- MORE --, next page: Space, next line: Enter, quit: Control-C";
			$this->pagerResponse = ' ';

			$this->socket->connect();

			// If we connect over SSH, we get this after the connect rather
			// than before the username/password phase.
			if (!$this->hasAcceptedEULA) {
				$this->getStreamData('Press any key to continue');
				$this->socket->write("\n");
			}

			$this->breakString = $this->learnPrompt();

			$data = $this->exec('no page', true);
		}

		/* {@inheritDoc} */
		function enable($password = '', $username = '') {
			$this->socket->write("enable\n");
			$this->getStreamData("\n");
			$this->sendUserPass($username, $password);
			$this->breakString = $this->learnPrompt();
			$this->exec('no page', true);
		}
	}
?>
