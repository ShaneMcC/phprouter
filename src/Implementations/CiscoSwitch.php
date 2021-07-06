<?php

	namespace shanemcc\PhpRouter\Implementations;

	/**
	 * Class to interact with a cisco router.
	 */
	class CiscoSwitch extends NetSwitch implements HasCanary {
		use CiscoTrait;
	}
