<?php
	/**
	 * Class to interact with a socket via SSH
	 */
	class SSHSocket extends Socket {
		/** SSH Connection */
		private $connection;

		/** SSH Connection stream. */
		private $stream;

		/* {@inheritDoc} */
		public function connect() {
			if ($this->connection != null) { return; }
			$conn = ssh2_connect($this->getHost(), $this->getPort(22));
			if ($conn !== false) {
				if (ssh2_auth_password($conn, $this->getUser(), $this->getPass())) {
					$this->connection = $conn;
					$this->stream = ssh2_shell($this->connection);
				} else { throw new Exception("Unable to authenticate."); }
			} else { throw new Exception("Unable to connect."); }
		}

		/* {@inheritDoc} */
		public function disconnect() {
			if ($this->stream != null) { fclose($this->stream); }

			$this->stream = null;
			$this->connection = null;
		}

		/* {@inheritDoc} */
		public function write($data) {
			if ($this->stream == null) { throw new Exception('Socket not connected'); }

			fwrite($this->stream, $data);
		}

		/* {@inheritDoc} */
		public function read($maxBytes = 1) {
			if ($this->stream == null) { throw new Exception('Socket not connected'); }

			stream_set_blocking($this->stream, true);
			$data = fread($this->stream, $maxBytes);
			stream_set_blocking($this->stream, false);
			return $data;
		}
	}
?>
