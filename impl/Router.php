<?php
	/**
	 * Class to interact with a router.
	 */
	abstract class Router extends NetworkDevice {
		/**
		 * Create the router.
		 *
		 * @param $host Host to connect to.
		 * @param $user Username to use.
		 * @param $pass Password to use.
		 */
		public function __construct($host, $user, $pass) {
			parent::__construct($host, $user, $pass);
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
		 * Get the entries in the named prefix list.
		 *
		 * @param $name Name of prefix list
		 * @param $type Type of prefix list
		 * @return Array of keys => value pairs where the key is the sequence number
		 *         and the value is "{permit,deny} mask"
		 */
		public function getPrefixList($name, $type = 'ipv4') { }
	}
?>
