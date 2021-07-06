<?php
	/**
	 * Class to interact with an iosxr router, over ssh.
	 */
	class IOSXERouter extends CiscoRouter {
        protected $hasPager = true;
        protected $pagerString = "\n --More-- ";
        protected $breakString = array('#');
	}
