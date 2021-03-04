<?php

	// Sockets
	require_once(dirname(__FILE__) . '/sockets/AuthenticationProvider.php');
	require_once(dirname(__FILE__) . '/sockets/RouterSocket.php');
	require_once(dirname(__FILE__) . '/sockets/SSHSocket.php');
	require_once(dirname(__FILE__) . '/sockets/OpenSSHShellSocket.php');
	require_once(dirname(__FILE__) . '/sockets/RawSocket.php');
	if (file_exists(dirname(__FILE__) . '/thirdparty/net_telnet/Net/Telnet.php')) {
		require_once(dirname(__FILE__) . '/thirdparty/net_telnet/Net/Telnet.php');
	}
	require_once(dirname(__FILE__) . '/sockets/TelnetSocket.php');

	// Classes
	require_once(dirname(__FILE__) . '/impl/NetworkDevice.php');
	require_once(dirname(__FILE__) . '/impl/Router.php');
	require_once(dirname(__FILE__) . '/impl/NetSwitch.php');

	// Implementations
	require_once(dirname(__FILE__) . '/impl/HPProcurve.php');
	require_once(dirname(__FILE__) . '/impl/CiscoTrait.php');
	require_once(dirname(__FILE__) . '/impl/CiscoRouter.php');
	require_once(dirname(__FILE__) . '/impl/CiscoSwitch.php');
	require_once(dirname(__FILE__) . '/impl/IOSXRRouter.php');
	require_once(dirname(__FILE__) . '/impl/ArborOS.php');
	require_once(dirname(__FILE__) . '/impl/APCPDU.php');
