<?php
	/**
	 * Class to interact with a NetworkDevice.
	 */
	abstract class NetworkDevice implements AuthenticationProvider {
		/** @var RouterSocket */
		protected $socket = null;
		/** Break String. */
		protected $breakString = [">\n", "#\n"];
		/** Debuging enabled? */
		protected $debug = false;
		/* When running an exec, should we include the command we just ran in the first getStreamData? */
		protected $execIncludeCommand = true;
		/* How much of a command do we care about when execing? -1 == all */
		protected $execPartialCommand = -1;
		/* When running an exec, should we write in chunks? (0 or less == no)*/
		protected $execCommandChunkSize = 4000;
		/** Delay in ms between each chunk. */
		protected $chunkDelay = 1000;
		/** Sleep delay when looking for breakdata, microseconds. */
		protected $breakSleep = 0;
		/* Delay after running any command with exec before we look for output. */
		protected $execDelay = 0;
		/* Does the OS wrap the commandline that was executed when echoing it back? */
		protected $execCommandWraps = true;
		/** Should we swallow ansi control codes? */
		protected $swallowControlCodes = false;
		/** Should any blocks of swallowed ansi codes be replaced with "\n" */
		protected $insertNewLineAfterANSI = false;
		/** If false, we won't insert a new line if the last successful character was a new line. */
		protected $insertNewLineAfterANSIAlways = false;
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
		/** Used by getNextChar/swallowANSI to insert characters. */
		private $nextCharBuffer = [];
		/* Last character we successfully read from the socket/buffer. */
		private $lastChar = '';

		/** Socket creation options from __construct. */
		private $socketOpts = [];

		/**
		 * Create the NetworkDevice.
		 *
		 * @param string $host Host to connect to.
		 * @param string $user Username to use (if using SSH)
		 * @param string $pass Password to use (if using SSH)
		 * @param string|RouterSocket $type Type of socket connection, 'ssh', 'telnet', 'raw' or
		 *              an instance of `RouterSocket`. If an instance of `RouterSocket` is
		 *              provided, then other parameters are ignored and the
		 *              socket is assumed to alrady know them and currently be
		 *              not-connected.
		 */
		public function __construct($host, $user, $pass, $type = 'ssh') {
			$this->socketOpts = [$host, $user, $pass, $type];
			$this->createNewSocket();
		}

		protected function createNewSocket() {
			[$host, $user, $pass, $type] = $this->socketOpts;

			if ($type instanceof RouterSocket) {
				$this->socket = $type;
			} else if ($type == 'ssh') {
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
		 * @return bool Is debugging enabled
		 */
		public function isDebug() { return $this->debug; }

		/**
		 * Set debugging on or off.
		 *
		 * @param bool $value New value for debugging.
		 */
		public function setDebug($value) { $this->debug = $value; }

		/**
		 * Encode a string for non-confusing CLI output.
		 *
		 * @param string $str String to encode
		 * @return string Encoded string.
		 */
		private function debugEncode($str) {
			return str_replace("\n", '\n', $str);
			// return urlencode($str);
		}

		/**
		 * Get next character of input from socket.
		 * This will look for characters in the nextCharBuffer before actually
		 * checking the socket, to allow us to insert characters where needed.
		 *
		 * @return string Next character of input from socket.
		 */
		private function getNextChar() {
			if (count($this->nextCharBuffer) > 0) {
				return array_shift($this->nextCharBuffer);
			} else {
				return $this->socket->read(1);
			}
		}

		/**
		 * Get some incoming data waiting on the stream.
		 *
		 * @param string|null $break When the last bit of the buffer is equal to this string,
		 *	       then we will return.
		 * @param bool $includeBreakData Should the contents of $break be included in the
		 *			  returned data.
		 * @return string Data from the stream.
		 */
		public function getStreamData($break = null, $includeBreakData = false) {
			// We don't do anything if we don't have valid break data.
			if ($break == null || $break == "") { return ''; }

			// Data collected so far.
			$data = '';

			// Keep going until we have the break data.
			while (true) {
				// Read some data
				try {
					$buf = $this->getNextChar();

					if ($this->swallowControlCodes !== false && ($buf == chr(0x1b) || $buf == chr(0x9B)) ) {
						$buf = $this->swallowANSI();
					}
				} catch (Exception $e) {
					// Socket probably closed, so just return whatever we have.
					$foundBreakData = FALSE;
					break;
				}

				if ($buf == "") { continue; } // Ignore empty return from swallowANSI();
				if ($buf == "\r") { continue; } // Ignore stupid things.
				if ($buf == "\x08") {
					// Backspace Character, remove some output, and continue.
					$data = substr($data, 0, -1);
					continue;
				}
				if ($this->streamDataTrimLineBreak && $buf == "\n") { continue; } // Trim Line Break
				$this->lastChar = $buf;
				$data .= $buf;

				$foundBreakData = "";
				// Check if we have the breakdata we need.
				$breakOptions = is_array($break) ? $break : [$break];

				$i = 0;
				foreach ($breakOptions as $b) {
					$foundBreakData = $b;
					$doBreak = substr($data, 0 - strlen($foundBreakData)) == $foundBreakData;
					if ($this->isDebug()) { echo "--- ", $i++, " [", ($doBreak ? 'TRUE' : 'FALSE'), "] {", $this->debugEncode(substr($data, 0 - strlen($foundBreakData))), "} == {", $this->debugEncode($foundBreakData), "}\n"; }
					if ($doBreak) { break; }
				}
				if ($this->breakSleep > 0) { usleep($this->breakSleep); }

				// Abort if we have break data.
				if ($doBreak) { break; }

				// Look for pager data.
				if ($this->hasPager) {
					$pagers = is_array($this->pagerString) ? $this->pagerString : [$this->pagerString];

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
		 * Swallow any incoming ansi escape codes.
		 *
		 * @return string First non-escape code character we encounter.
		 */
		function swallowANSI() {
			$code = '';
			$start = true;
			while (true) {
				$next = $this->getNextChar();
				if ($start && $next == '[') {
					$start = false;
					continue;
				} else {
					$start = false;
				}
				$code .= $next;

				// Swallow until ord($next) is between 64 and 126 (which means
				// it is the last character in the sequence)
				//
				// https://en.wikipedia.org/wiki/ANSI_escape_code#Sequence_elements
				if (ord($next) >= 64 && ord($next) < 127) {
					if ($this->isDebug()) { echo '@@@@ Swallow: ' . $code . "\n"; }

					// Get the next character.
					$next = $this->getNextChar();

					// Check if we have an escape code, if we do, start again
					// else return it as the next non-escape char.
					if ($next == chr(0x1b) || $next == chr(0x9B)) {
						$start = true;
						$code = '';
					} else if ($this->insertNewLineAfterANSI) {
						$this->nextCharBuffer[] = $next;
						if ($this->lastChar == "\n" && !$this->insertNewLineAfterANSIAlways) {
							return "";
						} else {
							return "\n";
						}
					} else {
						return $next;
					}
				}
			}
		}

		/**
		 * Get the next bit of incoming data waiting on the stream using the
		 * default breakString.
		 *
		 * @param bool $includeBreakData Should the contents of $break be included in the
		 *			  returned data.
		 * @return string Data from the stream.
		 */
		public function getNextStreamData($includeBreakData = false) {
			return $this->getStreamData($this->breakString, $includeBreakData);
		}

		/**
		 * Write the given data to the underlying socket.
		 *
		 * @param string $data Data to write.
		 */
		public function write($data) {
			$this->socket->write($data);
		}

		/**
		 * Write the given data to the underlying socket, with a new line
		 * automatically added.
		 *
		 * @param string $data Data to write.
		 */
		public function writeln($data) {
			$this->write($data);
			$this->write("\n");
		}

		/**
		 * Return the last matching break string.
		 *
		 * @return string Last matching breakstring from getStreamData
		 */
		public function getLastBreakString() {
			return $this->lastBreakStringMatched;
		}

		/**
		 * Run the given command, and return the output.
		 *
		 * @param string $cmd Command to run
		 * @param bool $debug Show command run, and output.
		 * @return string String containing the output of the command.
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
				if ($this->execPartialCommand > 0) {
					$this->getStreamData(substr($cmd, 0, $this->execPartialCommand));
				} else if ($this->execPartialCommand == -1) {
					$this->getStreamData($cmd);
				}
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
		 * @param string $password Password for enable if required.
		 * @param string $username Username for enable if required.
		 */
		public function enable($password = '', $username = '') { }
	}
