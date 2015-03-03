<?php
	/**
	 * Class to interact with a router.
	 */
	abstract class Router {
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

		/**
		 * Create the router.
		 *
		 * @param $host Host to connect to.
		 * @param $user Username to use.
		 * @param $pass Password to use.
		 */
		public function __construct($host, $user, $pass) {
			// TODO: Socket Types.
			$this->socket = new SSHSocket($host, $user, $pass);
		}

		/**
		 * Connect the socket and login to the router ready to run commands.
		 */
		public abstract function connect();

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
//			return urlencode($str);
		}

		/**
		 * Get some incoming data waiting on the stream.
		 *
		 * @param $break When the last bit of the buffer is equal to this string,
		 *               then we will return.
		 * @param $includeBreakData Should the contents of $break be included in the
		 *                          returned data.
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
				$buf = $this->socket->read(1);
				if ($buf == "\r") { continue; } // Ignore stupid things.
				$data .= $buf;

				// Check if we have the breakdata we need.
				if (is_array($break)) {
					$i = 0;
					foreach ($break as $b) {
						$doBreak = substr($data, 0 - strlen($b)) == $b;
						if ($this->isDebug()) { echo "--- ", $i++, " [", ($doBreak ? 'TRUE' : 'FALSE'), "] {", $this->debugEncode(substr($data, 0 - strlen($b))), "} == {", $this->debugEncode($b), "}\n"; }
						if ($doBreak) { break; }
					}
				} else {
					$doBreak = substr($data, 0 - strlen($break)) == $break;
					if ($this->isDebug()) { echo "[", ($doBreak ? 'TRUE' : 'FALSE'), "] {", $this->debugEncode(substr($data, 0 - strlen($break))), "} == {", $this->debugEncode($break), "}\n"; }
				}

				// Abort if we have break data.
				if ($doBreak) { break; }
			}

			// Do we want to include the break data?
			if (!$includeBreakData) {
				$data = substr($data, 0, 0 - strlen($break));
			}

			// Return the data.
			return $data;
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
				$this->getStreamData($cmd . "\n");
			} else {
				$this->getStreamData("\n");
			}
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
		 * Get the entries in the named prefix list.
		 *
		 * @param $name Name of prefix list
		 * @param $type Type of prefix list
		 * @return Array of keys => value pairs where the key is the sequence number
		 *         and the value is "{permit,deny} mask"
		 */
		public function getPrefixList($name, $type = 'ipv4') { }

		/**
		 * Enable admin commands and update the breakstring if needed.
		 *
		 * @param $password Password for enable.
		 */
		public function enable($password = '') { }
	}
?>
