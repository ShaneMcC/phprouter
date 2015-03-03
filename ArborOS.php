<?php
	/**
	 * Class to interact with an ArborOS device, over ssh.
	 */
	class ArborOS extends Router {
		/* {@inheritDoc} */
		public function connect() {
			$this->socket->connect();
			$this->getStreamData("\n");
			$this->socket->write("\n");

			$this->getStreamData(array("/# "), true);
			$this->socket->write("\n");
			$this->getStreamData("\n");
			$data = $this->getStreamData(array("/# "), true);
			$this->breakString = rtrim($data, "\n");

			$this->execIncludeCommand = false;
			$this->execCommandChunkSize = 4000;
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
				$this->socket->write($logMessage);
				$this->socket->write("\n.\n");
				$this->exec('');

				return true;
			}
		}
	}
?>
