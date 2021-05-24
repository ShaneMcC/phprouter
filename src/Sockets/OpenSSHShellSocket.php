<?php

	namespace shanemcc\PhpRouter\Sockets;

	use Exception;

	/**
	 * Class to interact with a socket via SSH, shelling out to ssh.
	 *
	 * This is not as well tested as other RouterSocket Implemenatations.
	 */
	class OpenSSHShellSocket extends RouterSocket {
		/** SSH Connection */
		private $connection;

		/** Params */
		private $params = '';

		/** Env */
		private $env = [];

		private $lastError = 'Socket not connected';

		/**
		 * Allow passing alternative params to openSSH. (Unsupported)
		 *
		 * @param $params Paramaters to pass. This string is used as-is, so any
		 *                arguments should be escaped before being passed to
		 *                this function.
		 */
		public function setParams($params) {
			$this->params = $params;
		}

		/**
		 * Allow passing alternative environment to openSSH.
		 *
		 * @param $params Environment vars to pass.
		 */
		public function setEnv($env) {
			$this->env = $env;
		}

		/* {@inheritDoc} */
		public function connect() {
			if ($this->connection != null) { return; }
			if (!file_exists('/usr/bin/sshpass') || !file_exists('/usr/bin/ssh')) { throw new Exception('This requires /usr/bin/sshpass and /usr/bin/ssh to exist.'); }

			$descriptorspec = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];

			$cmd = 'exec /usr/bin/sshpass -e /usr/bin/ssh -t -t -q';
			$cmd .= ' -o UserKnownHostsFile=/dev/null';
			$cmd .= ' -o StrictHostKeyChecking=no';
			$cmd .= ' -o ControlMaster=no -o ControlPath=none';
			$cmd .= ' -o UserKnownHostsFile=/dev/null';
			$cmd .= ' -o PreferredAuthentications=password,keyboard-interactive -o PubkeyAuthentication=no -o GSSAPIAuthentication=no';
			if (!empty($this->params)) {
				$cmd .= ' ' . $this->params;
			}
			$cmd .= ' ' . escapeshellarg($this->getHost());
			$cmd .= ' -p ' . escapeshellarg($this->getPort(22));
			$cmd .=' -l ' . escapeshellarg($this->getUser());
			$cmd .=' 2>&1';

			$cwd = '/';

			$env = $this->env;
			$env['SSHPASS'] = $this->getPass();

			$pipes = [];
			$proc = proc_open($cmd, $descriptorspec, $pipes, $cwd, $env);
			if ($proc) {
				$this->connection = ['pipes' => $pipes, 'proc' => $proc];
			} else {
				$this->lastError = 'Failed to open SSH.';
				throw new Exception($this->lastError);
			}
		}

		/* {@inheritDoc} */
		public function disconnect() {
			if ($this->connection != null) {
				fclose($this->connection['pipes'][0]);
				fclose($this->connection['pipes'][1]);
				fclose($this->connection['pipes'][2]);

				proc_terminate($this->connection['proc']);
				$this->connection = null;
			}
		}

		/* {@inheritDoc} */
		public function write($data) {
			$this->checkProcess();

			fwrite($this->connection['pipes'][0], $data);
		}

		/* {@inheritDoc} */
		public function read($maxBytes = 1) {
			$this->checkProcess();

			if (feof($this->connection['pipes'][1])) {
				$this->lastError = 'Socket closed.';
				throw new Exception($this->lastError);
			}

			stream_set_blocking($this->connection['pipes'][1], true);
			$data = fread($this->connection['pipes'][1], $maxBytes);
			stream_set_blocking($this->connection['pipes'][1], false);

			return $data;
		}

		/**
		 * Read stderr, this will block if there is nothing to read.
		 *
		 * @param $maxBytes Max bytes to read
		 * @return data read.
		 */
		public function readErr($maxBytes = 1) {
			$this->checkProcess();

			stream_set_blocking($this->connection['pipes'][2], true);
			$data = fread($this->connection['pipes'][2], $maxBytes);
			stream_set_blocking($this->connection['pipes'][2], false);
			return $data;
		}

		/**
		 * Get process status for our sub-process.
		 *
		 * @return proc_get_status array.
		 */
		public function procStatus() {
			$this->checkProcess();
			return proc_get_status($this->connection['proc']);
		}

		/**
		 * Check that our underlying process is still alive and valid.
		 *
		 * @return True if alive and valid, else an exception is thrown.
		 */
		private function checkProcess() {
			if ($this->connection == null) {
				throw new Exception($this->lastError);
			} else {
				$status = proc_get_status($this->connection['proc']);

				if (!$status['running']) {
					$this->disconnect();
					$this->lastError = 'Socket closed with exit-code: ' . $status['exitcode'];
					throw new Exception($this->lastError);
				}
			}

			return TRUE;
		}
	}
