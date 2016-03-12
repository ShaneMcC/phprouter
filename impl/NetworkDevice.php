<?php
	/**
	 * Class to interact with a NetworkDevice.
	 */
	abstract class NetworkDevice implements AuthenticationProvider {
		/** Socket */
		protected $socket = null;
		/** Break String. */
		protected $breakString = array(">\n", "#\n");
		/** Debuging enabled? */
		protected $debug = false;
		/* When running an exec, should we include the command we just ran in the first getStreamData? */
		protected $execIncludeCommand = true;
		/* When running an exec, should we write in chunks? (0 or less == no)*/
		protected $execCommandChunkSize = 4000;
		/** Delay in ms between each chunk. */
		protected $chunkDelay = 1000;
		/* Delay after running any command with exec before we look for output. */
		protected $execDelay = 0;
		/* Does the OS wrap the commandline that was executed when echoing it back? */
		protected $execCommandWraps = true;
		/** Does this socket have a "pager" that we can't turn off? */
		protected $hasPager = false;
		/** Pager String. */
		protected $pagerString = "\n--- More ---";
		/** Pager Response */
		protected $pagerResponse = ' ';
		/** Last breakstring that was matched. */
		private $lastBreakStringMatched = '';

		/** Used by exec and getStreamData based on execCommandWraps. */
		private $streamDataTrimLineBreak = false;

		/**
		 * Create the NetworkDevice.
		 *
		 * @param $host Host to connect to.
		 * @param $user Username to use (if using SSH)
		 * @param $pass Password to use (if using SSH)
		 * @param $type Type of socket connection, 'ssh' or 'telnet'
		 */
		public function __construct($host, $user, $pass, $type = 'ssh') {
			if ($type == 'ssh') {
				$this->socket = new SSHSocket($host, $user, $pass, $this);
			} else if ($type == 'telnet') {
				$this->socket = new TelnetSocket($host, $user, $pass, $this);
			} else if ($type == 'raw') {
				$this->socket = new RawSocket($host, $user, $pass, $this);
			}
		}

		/**
		 * Connect the socket and login to the NetworkDevice ready to run commands.
		 */
		public abstract function connect();

		/**
		 * Disconnect from the NetworkDevice.
		 */
		public function disconnect() {
			$this->socket->disconnect();
		}

		/** {@inheritDoc} */
		public function handleAuth($socket) {
			throw new Exception("handleAuth not implemented.");
		}

		/**
		 * Is debugging enabled.
		 *
		 * @return Is debugging enabled
		 */
		public function isDebug() { return $this->debug; }

		/**
		 * Set debugging on or off.
		 *
		 * @param $value New value for debugging.
		 */
		public function setDebug($value) { $this->debug = $value; }

		/**
		 * Encode a string for non-confusing CLI output.
		 *
		 * @param $str String to encode
		 * @return Encoded string.
		 */
		private function debugEncode($str) {
			return str_replace("\n", '\n', $str);
//		      return urlencode($str);
		}

		/**
		 * Get some incoming data waiting on the stream.
		 *
		 * @param $break When the last bit of the buffer is equal to this string,
		 *	       then we will return.
		 * @param $includeBreakData Should the contents of $break be included in the
		 *			  returned data.
		 * @return Data from the stream.
		 */
		public function getStreamData($break = null, $includeBreakData = false) {
			// We don't do anything if we don't have valid break data.
			if ($break == null || $break == "") { return; }

			// Data collected so far.
			$data = '';

			// Keep going until we have the break data.
			while (true) {
				// Read some data
				try {
					$buf = $this->socket->read(1);
				} catch (Exception $e) {
					// Socket probably closed, so just return whatever we have.
					$foundBreakData = FALSE;
					break;
				}
				if ($buf == "\r") { continue; } // Ignore stupid things.
				if ($this->streamDataTrimLineBreak && $buf == "\n") { continue; } // Trim Line Break

				$data .= $buf;

				$foundBreakData = "";
				// Check if we have the breakdata we need.
				if (is_array($break)) {
					$i = 0;
					foreach ($break as $b) {
						$foundBreakData = $b;
						$doBreak = substr($data, 0 - strlen($foundBreakData)) == $foundBreakData;
						if ($this->isDebug()) { echo "--- ", $i++, " [", ($doBreak ? 'TRUE' : 'FALSE'), "] {", $this->debugEncode(substr($data, 0 - strlen($foundBreakData))), "} == {", $this->debugEncode($foundBreakData), "}\n"; }
						if ($doBreak) { break; }
					}
				} else {
					$foundBreakData = $break;
					$doBreak = substr($data, 0 - strlen($foundBreakData)) == $foundBreakData;
					if ($this->isDebug()) { echo "[", ($doBreak ? 'TRUE' : 'FALSE'), "] {", $this->debugEncode(substr($data, 0 - strlen($foundBreakData))), "} == {", $this->debugEncode($foundBreakData), "}\n"; }
				}

				// Abort if we have break data.
				if ($doBreak) { break; }

				// Look for pager data.
				if ($this->hasPager) {
					$pagers = is_array($this->pagerString) ? $this->pagerString : array($this->pagerString);

					foreach ($pagers as $p) {
						$doPager = substr($data, 0 - strlen($p)) == $p;
						if ($doPager) {
							$data = substr($data, 0, 0 - strlen($p));
							$this->socket->write($this->pagerResponse);
							break;
						}
					}
				}
			}

			// Do we want to include the break data?
			if (!$includeBreakData) {
				$data = substr($data, 0, 0 - strlen($foundBreakData));
			}

			$this->lastBreakStringMatched = $foundBreakData;

			// Return the data.
			return $data;
		}

		/**
		 * Get the next bit of incoming data waiting on the stream using the
		 * default breakString.
		 *
		 * @param $includeBreakData Should the contents of $break be included in the
		 *			  returned data.
		 * @return Data from the stream.
		 */
		public function getNextStreamData($includeBreakData = false) {
			return $this->getStreamData($this->breakString, $includeBreakData);
		}

		/**
		 * Write the given data to the underlying socket.
		 *
		 * @param $data Data to write.
		 */
		public function write($data) {
			$this->socket->write($data);
		}

		/**
		 * Return the last matching break string.
		 *
		 * @return Last matching breakstring from getStreamData
		 */
		public function getLastBreakString() {
			return $this->lastBreakStringMatched;
		}

		/**
		 * Run the given command, and return the output.
		 *
		 * @param $cmd Command to run
		 * @param $debug Show command run, and output.
		 * @return String containing the output of the command.
		 */
		public function exec($cmd, $debug = false) {
			$needChunking = ($this->execCommandChunkSize > 0 && strlen($cmd) > $this->execCommandChunkSize);
			if ($needChunking) {
				foreach (str_split($cmd, $this->execCommandChunkSize) as $chunk) {
					$this->socket->write($chunk);
					usleep($this->chunkDelay * 1000);
				}
			} else {
				$this->socket->write($cmd);
			}

			$this->socket->write("\n");
			if ($needChunking) { usleep($this->chunkDelay * 1000); }

			if ($this->execIncludeCommand) {
				$this->streamDataTrimLineBreak = $this->execCommandWraps;
				$this->getStreamData($cmd);
				$this->streamDataTrimLineBreak = false;
			}
			$this->getStreamData("\n");
			usleep($this->execDelay * 1000);
			$data = rtrim($this->getStreamData($this->breakString), "\n");

			if ($this->isDebug() || $debug) {
				echo "-------------------------------", "\n";
				echo $cmd, "\n";
				echo "----------", "\n";
				echo $data, "\n";
				echo "-------------------------------", "\n";
			}

			return $data;
		}

		/**
		 * Enable admin commands and update the breakstring if needed.
		 *
		 * @param $password Password for enable.
		 */
		public function enable($password = '') { }
	}
?>
