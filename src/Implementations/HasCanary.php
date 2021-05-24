<?php

	namespace shanemcc\PhpRouter\Implementations;

	/**
	 * Interface to show that a device type supports "Canary" lines.
	 *
	 * These are lines that can be sent to the device for tracking purposes
	 * but that won't actually do anything.
	 */
	interface HasCanary {
		/**
		 * Get a device-compatible canary.
		 */
		public function getCanary();
	}
