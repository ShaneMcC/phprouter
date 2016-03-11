<?php
	/**
	 * Class to interact with an APC PDU over Telnet or SSH.
	 */
	class APCPDU extends NetworkDevice {

		/** {@inheritDoc} */
		public function handleAuth($socket) {
			$this->getStreamData('User Name :');
			$this->socket->write($socket->getUser());
			$this->socket->write("\r\n");
			$this->getStreamData('Password  :');
			$this->socket->write($socket->getPass());
			$this->socket->write("\r\n");
			$this->getStreamData('User Name :');

			return true;
		}

		/* {@inheritDoc} */
		public function connect() {
			$this->socket->connect();
			/* $this->socket->write("\r\n");
			$this->getStreamData("\n");
			$this->socket->write("\r\n");
			$data = $this->getStreamData(array(">\n", "#\n"), true);
			$this->breakString = rtrim($data, "\n");
			$data = $this->exec('term len 0'); */
		}
	}
?>
