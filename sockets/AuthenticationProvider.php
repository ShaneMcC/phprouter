<?php
	/**
	 * Interface to provide authentication implementation for protocols that
	 * do not include authentication by default.
	 */
	interface AuthenticationProvider {

		/**
		 * Handle non-standard authentication.
		 *
 		 * @param $socket Socket requesting authentication
		 */
		public function handleAuth($socket);
	}
?>
