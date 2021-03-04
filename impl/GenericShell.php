<?php
	/**
	 * Class to interact with an abstract router via a generic ssh shell.
	 *
	 * Shell must support "echo" command for prompt detection.
	 */
	class GenericShell extends NetworkDevice implements HasCanary {
		private $canary;

		public function connect() {
			$this->canary = ' # ' . md5(uniqid(true));
			$this->socket->connect();

			$this->updatePrompt();
		}

		public function updatePrompt() {
			// Echo 2 halves of the canary together.
			// This ensures that getStreamData actually only reads once we're
			// properly connected, not stuff we have sent that hasn't been
			// accepted yet.
			$split = str_split($this->canary, ceil(strlen($this->canary) / 2));
			$this->write(' echo "');
			$this->write($split[0]);
			$this->write('""');
			$this->write($split[1]);
			$this->writeln('"');
			$this->getStreamData($this->canary . "\n", true);
			$this->writeln("\n");

			$data = $this->getStreamData("\n", true);
			$this->breakString = rtrim($data, "\n");
		}

		function getCanary() {
			return $this->canary;
		}
	}
