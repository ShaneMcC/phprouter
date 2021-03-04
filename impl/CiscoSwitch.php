<?php
	/**
	 * Class to interact with a cisco router.
	 */
	class CiscoSwitch extends NetSwitch implements HasCanary {
		use CiscoTrait;
	}
