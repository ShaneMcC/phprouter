<?php

	namespace shanemcc\PhpRouter\Sockets;

	/**
	 * Class to interact with a socket.
	 */
	abstract class RouterSocket {
		/** Host to connect to. */
		private $host;
		/** Port to connect to. */
		private $port;
		/** Username to use. */
		private $user;
		/** Password to use. */
		private $pass;
		/** Authentication Provider. */
		private $auth;

		/**
		 * Create the router.
		 *
		 * @param string $host Host to connect to.
		 * @param string $user Username to use.
		 * @param string $pass Password to use.
		 * @param AuthenticationProvider $auth AuthenticationProvider class that can handle dealing
		 *              with non-standard auth for protocols that do not include
		 *              authentication.
		 */
		public function __construct($host, $user, $pass, $auth = null) {
			$this->user = $user;
			$this->pass = $pass;

			$bits = explode(':', $host);
			$this->host = $bits[0];
			$this->port = count($bits) > 1 ? $bits[1] : -1;

			$this->auth = $auth;
		}

		/**
		 * Get the host for this router.
		 *
		 * @return string The host to connect to.
		 */
		public function getHost() { return $this->host; }

		/**
		 * Get the port for this router to connect on.
		 *
		 * @param int $default Default port if none is specified.
		 * @return int The port to connect to if specified, else $default
		 */
		public function getPort($default = -1) {
			return $this->port > 0 ? $this->port : $default;
		}

		/**
		 * Get the username for this router.
		 *
		 * @return string The username to connect to.
		 */
		public function getUser() { return $this->user; }

		/**
		 * Get the password for this router.
		 *
		 * @return string The password to connect to.
		 */
		public function getPass() { return $this->pass; }

		/**
		 * Get the AuthenticationProvider for this router.
		 *
		 * @return AuthenticationProvider The AuthenticationProvider.
		 */
		public function getAuthenticationProvider() { return $this->auth; }

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
		 * @param string $data Data to write.
		 */
		public abstract function write($data);

		/**
		 * Read data from the router, this will block if there is nothing to read.
		 *
		 * @param int $maxBytes Max bytes to read
		 * @return string data read from router.
		 */
		public abstract function read($maxBytes = 1);
	}
