<?php
	/**
	 * Class to interact with an APC PDU over Telnet or SSH.
	 */
	class APCPDU extends NetworkDevice {

		/** {@inheritDoc} */
		public function handleAuth($socket) {
			$this->getStreamData('User Name :');
			$this->socket->write($socket->getUser());
			$this->socket->write("\n");
			$this->getStreamData('Password  :');
			$this->socket->write($socket->getPass());
			$this->socket->write("\n");
			$this->getStreamData("\n");

			$result = $this->getStreamData(array('User Name :', "\n\n"), true);

			// If we are prompted for the username again then we are wrong.
			return ($result != "User Name :");
		}

		/* {@inheritDoc} */
		public function connect() {
			$this->socket->connect();
			$this->socket->write("\n");
			$this->getStreamData("\n> \n");

			$this->breakString = "\n> ";
			$this->getStreamData($this->breakString);


			$this->hasPager = true;
			$this->pagerString = "\n        Press <ENTER> to continue...";
			$this->pagerResponse = "\n";
		}

		/**
		 * Send the "Escape" key.
		 *
		 * @param $debug Show command run, and output.
		 * @return Output following sending the escape key.
		 */
		public function sendEscape($debug = false) {
			$this->execIncludeCommand = false;
			$result = $this->exec(chr(0x1B), $debug);
			$this->execIncludeCommand = true;

			// Exec sends a "\n" which reprints the last output, swallow it.
			$this->getStreamData($this->breakString);

			return $result;
		}

		/**
		 * Get the event log, handling pagination.
		 *
		 * @param $pages Pages of event log to return.
		 * @return Event Log Data
		 */
		public function getEventLog($pages = 1) {
			$break = $this->breakString;
			$page = $this->pagerString;
			$pageresponse = $this->pagerResponse;

			$this->breakString = "\n>";
			$this->pagerResponse = "";
			$this->pagerString = array("\n\n   <ESC>- Exit, <ENTER>- Refresh, <SPACE>- Next, <B>- Back, <D>- Delete\n",
				                       "\n\n   <ESC>- Exit, <ENTER>- Refresh, <SPACE>- Next, <D>- Delete",
				                       "\n\n   <ESC>- Exit, <ENTER>- Refresh, <D>- Delete",
			                          );
			$this->socket->write(chr(0x0C)); // Ctrl-L

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

			$this->socket->write(chr(0x1B)); // ESCAPE
			$this->getStreamData($this->breakString);

			return $result;
		}

		/**
		 * Clear the event log.
		 */
		public function clearEventLog() {
			$this->socket->write(chr(0x0C)); // Ctrl-L
			$this->socket->write("D");
			$this->socket->write("YES\n");
			$this->socket->write(chr(0x1B)); // ESCAPE

			$this->getStreamData($this->breakString);
		}
	}
?>
