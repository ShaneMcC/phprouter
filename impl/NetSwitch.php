<?php
	/**
	 * Class to interact with a network switch.
	 */
	abstract class NetSwitch extends NetworkDevice {
		/**
		 * Create the NetSwitch.
		 *
		 * @param $host Host to connect to.
		 * @param $user Username to use.
		 * @param $pass Password to use.
		 * @param $type Type of socket connection, 'ssh', 'telnet' or 'raw'
		 */
		public function __construct($host, $user, $pass, $type = 'ssh') {
			parent::__construct($host, $user, $pass, $type);
		}
	}
