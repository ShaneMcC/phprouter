<?php
	/**
	 * Class to interact with a switch.
	 */
	abstract class NetSwitch extends NetworkDevice {
		/**
		 * Create the NetSwitch.
		 *
		 * @param $host Host to connect to.
		 * @param $user Username to use.
		 * @param $pass Password to use.
		 */
		public function __construct($host, $user, $pass) {
			super::__construct($host, $user, $pass);
		}
	}
?>
