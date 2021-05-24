<?php
	/**
	 * Class to interact with an APC PDU over Telnet or SSH.
	 */
	class APCPDU extends NetworkDevice {

		/** is this a new-style or old style APC CLI? */
		private $oldDevice = true;

		/** {@inheritDoc} */
		public function handleAuth($socket) {
			$this->getStreamData('User Name :');
			$this->socket->write($socket->getUser());
			$this->socket->write("\n");
			$this->getStreamData('Password  :');
			$this->socket->write($socket->getPass());
			$this->socket->write("\n");
			$this->getStreamData("\n");

			$result = $this->getStreamData(array('User Name :', "\nAmerican Power Conversion"), true);

			// If we are prompted for the username again then we are wrong.
			return ($result != "User Name :");
		}

		/* {@inheritDoc} */
		public function connect() {
			$this->socket->connect();

			$this->socket->write("\n");
			$this->breakString = array("\n> \n", "apc>\n", "@apc>\n");
			$this->getNextStreamData();

			$this->oldDevice = ($this->getLastBreakString() == "\n> \n");

			if ($this->oldDevice) {
				$this->breakString = "\n> ";
				$this->getNextStreamData();

				$this->hasPager = true;
				$this->pagerString = "\n        Press <ENTER> to continue...";
				$this->pagerResponse = "\n";
			} else {
				// Figure out the exact prompt
				$this->socket->write("\n");
				$this->getStreamData("\n");
				$this->breakString = array("apc>", "@apc>");
				$prompt = $this->getNextStreamData(true);

				$this->breakString = $prompt;
			}
		}

		/**
		 * Is this an old-style PDU?
		 */
		public function isOldStyle() {
			return $this->oldDevice;
		}

		/**
		 * Send the "Escape" key.
		 *
		 * @param int $count How many times to send the escape key.
		 * @return string Output following the last press of the escape key.
		 */
		public function sendEscape($count = 1) {
			if (!$this->oldDevice) { return ''; }

			$this->execIncludeCommand = false;

			do {
				$result = $this->socket->write(chr(0x1B));
				$this->getNextStreamData();
			} while (--$count > 0);
			$this->execIncludeCommand = true;

			return $result;
		}

		/**
		 * Get the event log, handling pagination.
		 *
		 * @param int $pages Pages of event log to return.
		 * @return string Event Log Data
		 */
		public function getEventLog($pages = 1) {
			$break = $this->breakString;
			$page = $this->pagerString;
			$pageresponse = $this->pagerResponse;

			if ($this->oldDevice) {
				$this->socket->write(chr(0x0C)); // Ctrl-L
				$this->breakString = "\n>";
			} else {
				$this->socket->write("eventlog\n");
				$this->breakString = "\n> ";
				$this->hasPager = true;
			}

			$this->pagerResponse = "";
			$this->pagerString = array("\n\n   <ESC>- Exit, <ENTER>- Refresh, <SPACE>- Next, <B>- Back, <D>- Delete\n",
			                           "\n\n   <ESC>- Exit, <ENTER>- Refresh, <SPACE>- Next, <D>- Delete",
			                           "\n\n   <ESC>- Exit, <ENTER>- Refresh, <D>- Delete",
			                          );

			$result = $this->getStreamData($this->breakString);
			while (--$pages > 0) {
				$this->socket->write(" ");
				$this->breakString = $this->pagerString;
				$response = $this->getStreamData($this->breakString);

				if (preg_match("#-- Event Log --#", $response)) {
					// End of event log.
					break;
				} else {
					$result .= $response;
				}
			}

			$this->pagerString = $page;
			$this->breakString = $break;
			$this->pagerResponse = $pageresponse;
			if (!$this->oldDevice) {
				$this->hasPager = false;
			}

			$this->socket->write(chr(0x1B)); // ESCAPE
			$this->getStreamData($this->breakString);

			return $result;
		}

		/**
		 * Clear the event log.
		 */
		public function clearEventLog() {
			if ($this->oldDevice) {
				$this->socket->write(chr(0x0C)); // Ctrl-L
			} else {
				$this->socket->write("eventlog\n");
			}
			$this->socket->write("D");
			$this->socket->write("YES\n");
			$this->socket->write(chr(0x1B)); // ESCAPE

			$this->getStreamData($this->breakString);
		}
	}
