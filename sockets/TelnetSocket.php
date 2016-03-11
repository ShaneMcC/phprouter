<?php
	/**
	 * Class to interact with a socket via Telnet.
	 */
	class TelnetSocket extends Socket {
		/** Telnet Connection. */
		private $connection;

		/* {@inheritDoc} */
		public function connect() {
			if ($this->connection != null) { return; }

			$this->connection = new Net_Telnet(array('host' => $this->getHost(),
				                                     'port' => $this->getPort(23),
				                                     'debug' => true,
				                                    ));
			$this->connection->connect();

			$this->getAuthenticationProvider()->handleAuth($this);
		}

		/* {@inheritDoc} */
		public function disconnect() {
			if ($this->connection != null) { $this->connection->disconnect(); }

			$this->connection = null;
		}

		/* {@inheritDoc} */
		public function write($data) {
			if ($this->connection == null) { throw new Exception('Socket not connected'); }

			$this->connection->net_write($data);
		}

		/* {@inheritDoc} */
		public function read($maxBytes = 1) {
			if ($this->connection == null) { throw new Exception('Socket not connected'); }

			$this->connection->read_stream(null, $maxBytes);
			$data = $this->connection->get_data();
			return $data;
		}
	}
?>
