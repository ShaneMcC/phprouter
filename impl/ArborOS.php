<?php
	/**
	 * Class to interact with an ArborOS device, over ssh.
	 */
	class ArborOS extends NetworkDevice {
		/* {@inheritDoc} */
		public function connect() {
			$this->socket->connect();
			$this->getStreamData("\n");

			$this->getStreamData("/# ", true);
			$this->socket->write("\n");
			$this->getStreamData("\n");
			$data = $this->getStreamData("/# ", true);
			$this->breakString = rtrim($data, "\n");

//			$this->execIncludeCommand = false;
			$this->execCommandWraps = true;
			$this->execCommandChunkSize = 4000;
			$this->chunkDelay = 1000;
			$this->execDelay = 1000;
		}

		public function hasPendingConfig() {
			$diff = $this->exec('config diff');
			return !empty($diff);
		}

		public function saveConfig($logMessage = '') {
			// Remove special characters from log message.
			$logMessage = preg_replace('#["\|!\[\]\\\]#', '', $logMessage);

			// Simple config write if there is no log message.
			if (empty($logMessage)) {
				$this->exec('config write');
				return true;
			} else {
				$this->socket->write("config write log\n");
				usleep($this->execDelay * 1000);
				$this->socket->write($logMessage);
				$this->socket->write("\n.\n");
				usleep($this->execDelay * 1000);
				$this->exec('');

				return true;
			}
		}
	}
