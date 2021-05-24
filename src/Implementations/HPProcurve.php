<?php

	namespace shanemcc\PhpRouter\Implementations;

	/**
	 * Class to interact with a HP Procurve switch.
	 */
	class HPProcurve extends NetSwitch {

		/** Have we accepted the login EULA yet? */
		private $hasAcceptedEULA = false;

		/** {@inheritDoc} */
		public function handleAuth($socket) {
			$this->getStreamData('Press any key to continue');
			$this->socket->write("\n");
			$this->hasAcceptedEULA = true;

			$this->sendUserPass($socket->getUser(), $socket->getPass());

			$this->socket->write("\n");
			$this->getStreamData(
				[
					'Username:',
					'Password:',
					"> \n",
					"# \n",
					"Do you want to save current configuration",
					"Main Menu\n1. Status",
				]
			);
			$result = $this->getLastBreakString();

			if ($result == "Do you want to save current configuration") {
				$this->socket->write("n");
				$this->exitMenu();
			} else if ($result == "Main Menu\n1. Status") {
				$this->exitMenu();
			}

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
		 * @param string $username Username to send if requested.
		 * @param string $password Password to send if requested.
		 * @return void
		 */
		private function sendUserPass($username, $password) {
			$this->getStreamData(['Username: ', 'Password: ', "> ", "# "]);
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
		 * @return string The prompt.
		 */
		private function learnPrompt() {
			$this->socket->write("\n");
			$this->getStreamData("\n");
			$this->socket->write("\n");
			$data = $this->getStreamData(["> \n", "# \n"], true);
			if ($this->isDebug()) { echo 'PROMPT is now: ', rtrim($data, "\n"), "\n"; }
			return rtrim($data, "\n");
		}

		/* {@inheritDoc} */
		public function connect() {
			$this->swallowControlCodes = true;
			$this->insertNewLineAfterANSI = true;

			// Admins can turn off paging, normal users can not... lame.
			$this->hasPager = true;
			$this->pagerString = "\n-- MORE --, next page: Space, next line: Enter, quit: Control-C";
			$this->pagerResponse = ' ';

			$this->socket->connect();

			// If we connect over SSH, we get this after the connect rather
			// than before the username/password phase.
			if (!$this->hasAcceptedEULA) {
				$this->getStreamData('Press any key to continue');
				$this->socket->write("\n");

				$this->socket->write("\n");
				$this->getStreamData(
					["> \n", "# \n", "Do you want to save current configuration", "Main Menu\n1. Status"]
				);
				$result = $this->getLastBreakString();

				if ($result == "Do you want to save current configuration") {
					$this->socket->write("n");
					$this->exitMenu();
				} else if ($result == "Main Menu\n1. Status") {
					$this->exitMenu();
				}
			}

			$this->breakString = $this->learnPrompt();
			$this->getNextStreamData();

			$data = $this->exec('no page');
		}

		/* {@inheritDoc} */
		function enable($password = '', $username = '') {
			$this->socket->write("enable\n");
			$this->getStreamData("\n");
			$this->sendUserPass($username, $password);
			$this->breakString = $this->learnPrompt();
			$this->exec('no page');
		}

		/**
		 * Exit from the procurve menu.
		 */
		private function exitMenu() {
			// The "\n" we sent earlier to help discover that we were
			// logged in put us into a sub-menu, back out so that we can
			// return to the CLI.
			$this->socket->write('0');

			// CLI can be 3 or 5 depending on our access level.
			$this->getStreamData(["3. Command Line (CLI)", "5. Command Line (CLI)"]);
			$result = $this->getLastBreakString();

			// Exit the menu, which ever menu item number it is.
			$bits = explode('.', $result);
			$this->socket->write($bits[0]);

			// And lets try again without falling into a menu this time...
			$this->socket->write("\n");
			$this->getStreamData(["> \n", "# \n"]);
		}
	}
