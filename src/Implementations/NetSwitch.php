<?php

	namespace shanemcc\PhpRouter\Implementations;

	use shanemcc\PhpRouter\Sockets\RouterSocket;

	/**
	 * Class to interact with a network switch.
	 */
	abstract class NetSwitch extends NetworkDevice {
		/**
		 * Create the NetSwitch.
		 *
		 * @param string $host Host to connect to.
		 * @param string $user Username to use.
		 * @param string $pass Password to use.
		 * @param string|RouterSocket $type Type of socket connection, 'ssh', 'telnet' or 'raw'
		 */
		public function __construct($host, $user, $pass, $type = 'ssh') {
			parent::__construct($host, $user, $pass, $type);
		}
	}
