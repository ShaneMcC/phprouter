<?php
	/**
	 * Class to interact with a socket via Raw Socket.
	 */
	class RawSocket extends Socket {
		/** Raw Connection stream. */
		private $stream;

		/* {@inheritDoc} */
		public function connect() {
			if ($this->stream != null) { return; }

			$this->stream = stream_socket_client(sprintf('tcp://%s:%d', $this->getHost(), $this->getPort(23)), $errno, $errstr, 10);

			if ($this->stream !== false) {
				$this->getAuthenticationProvider()->handleAuth($this);
			}
		}

		/* {@inheritDoc} */
		public function disconnect() {
			if ($this->stream != null) { fclose($this->stream); }

			$this->stream = null;
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
