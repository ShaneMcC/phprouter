<?php

	namespace shanemcc\PhpRouter\Sockets;

	/**
	 * Interface to provide authentication implementation for protocols that
	 * do not include authentication by default.
	 */
	interface AuthenticationProvider {

		/**
		 * Handle non-standard authentication.
		 *
 		 * @param RouterSocket $socket Socket requesting authentication
		 * @return bool True or False if authentication succeeded.
		 */
		public function handleAuth($socket);
	}
