<?php

	namespace shanemcc\PhpRouter\Sockets;

	use Exception;

	/**
	 * Class to interact with a socket via SSH
	 */
	class SSHSocket extends RouterSocket {
		/** SSH Connection */
		private $connection;

		/** SSH Connection stream. */
		private $stream;

		/** Env */
		private $env = array();

		/** TermType */
		private $termType = 'vanilla';

		/** Term Width */
		private $termWidth = 80;

		/** Term Height */
		private $termHeight = 25;

		/** Have we changed any defaults for the ssh session? */
		private $isDefault = true;

		/**
		 * Allow passing alternative environment to openSSH.
		 *
		 * @param $params Environment vars to pass.
		 */
		public function setEnv($env) {
			$this->env = $env;
			$this->isDefault = false;
		}

		/**
		 * Set termType for session.
		 *
		 * @param $termType for session.
		 */
		public function setTermType($termType) {
			$this->termType = $termType;
			$this->isDefault = false;
		}

		/**
		 * Set termWidth for session.
		 *
		 * @param $termWidth for session.
		 */
		public function setTermWidth($termWidth) {
			$this->termWidth = $termWidth;
			$this->isDefault = false;
		}

		/**
		 * Set termHeight for session.
		 *
		 * @param $termHeight for session.
		 */
		public function setTermHeight($termHeight) {
			$this->termHeight = $termHeight;
			$this->isDefault = false;
		}

		/* {@inheritDoc} */
		public function connect() {
			if ($this->connection != null) { return; }
			$conn = ssh2_connect($this->getHost(), $this->getPort(22));
			if ($conn !== false) {
				if (ssh2_auth_password($conn, $this->getUser(), $this->getPass())) {
					$this->connection = $conn;
					if ($this->isDefault) {
						$this->stream = ssh2_shell($this->connection);
					} else {
						$this->stream = ssh2_shell($this->connection, $this->termType, $this->env, $this->termWidth, $this->termHeight, SSH2_TERM_UNIT_CHARS);
					}
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
