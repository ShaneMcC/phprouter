<?php
	/**
	 * Class to interact with a socket.
	 */
	abstract class Socket {
		/** Host to connect to. */
		private $host;
		/** Username to use. */
		private $user;
		/** Password to use. */
		private $pass;

		/**
		 * Create the router.
		 *
		 * @param $host Host to connect to.
		 * @param $user Username to use.
		 * @param $pass Password to use.
		 */
		public function __construct($host, $user, $pass) {
			$this->host = $host;
			$this->user = $user;
			$this->pass = $pass;
		}

		/**
		 * Get the host for this router.
		 *
		 * @return The host to connect to.
		 */
		public function getHost() { return $this->host; }

		/**
		 * Get the username for this router.
		 *
		 * @return The username to connect to.
		 */
		public function getUser() { return $this->user; }

		/**
		 * Get the password for this router.
		 *
		 * @return The password to connect to.
		 */
		public function getPass() { return $this->pass; }

		/**
		 * Connect to the socket.
		 */
		public abstract function connect();

		/**
		 * Disconnect to the socket.
		 */
		public abstract function disconnect();

		/**
		 * Write the given data to the router. Output will be waiting on the stream.
		 *
		 * @param $data Data to write.
		 */
		public abstract function write($data);

		/**
		 * Read data from the router, this will block if there is nothing to read.
		 *
		 * @param $maxBytes Max bytes to read
		 * @return data read from router.
		 */
		public abstract function read($maxBytes = 1);
	}
?>
