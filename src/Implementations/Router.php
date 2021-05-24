<?php

	namespace shanemcc\PhpRouter\Implementations;

	use shanemcc\PhpRouter\Sockets\RouterSocket;

	/**
	 * Class to interact with a router.
	 */
	abstract class Router extends NetworkDevice {
		/**
		 * Create the router.
		 *
		 * @param $host Host to connect to.
		 * @param $user Username to use.
		 * @param $pass Password to use.
		 * @param $type Type of socket connection, 'ssh', 'telnet' or 'raw'
		 */
		public function __construct($host, $user, $pass, $type = 'ssh') {
			parent::__construct($host, $user, $pass, $type);
		}

		/**
		 * Get the entries in the named prefix list.
		 *
		 * @param $name Name of prefix list
		 * @param $type Type of prefix list
		 * @return Array of keys => value pairs where the key is the sequence number
		 *         and the value is "{permit,deny} mask"
		 */
		public function getPrefixList($name, $type = 'ipv4') { }
	}
