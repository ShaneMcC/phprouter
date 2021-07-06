<?php

	// Sockets
	require_once(dirname(__FILE__) . '/Sockets/AuthenticationProvider.php');
	require_once(dirname(__FILE__) . '/Sockets/RouterSocket.php');
	require_once(dirname(__FILE__) . '/Sockets/SSHSocket.php');
	require_once(dirname(__FILE__) . '/Sockets/OpenSSHShellSocket.php');
	require_once(dirname(__FILE__) . '/Sockets/RawSocket.php');
	if (file_exists(dirname(dirname(__FILE__)) . '/thirdparty/net_telnet/Net/Telnet.php')) {
		require_once(dirname(dirname(__FILE__)) . '/thirdparty/net_telnet/Net/Telnet.php');
	}
	require_once(dirname(__FILE__) . '/Sockets/TelnetSocket.php');

	// Implementations: Interfaces
	require_once(dirname(__FILE__) . '/Implementations/HasCanary.php');

	// Implementations: Abstracts
	require_once(dirname(__FILE__) . '/Implementations/NetworkDevice.php');
	require_once(dirname(__FILE__) . '/Implementations/Router.php');
	require_once(dirname(__FILE__) . '/Implementations/NetSwitch.php');

	// Implementations
	require_once(dirname(__FILE__) . '/Implementations/HPProcurve.php');
	require_once(dirname(__FILE__) . '/Implementations/CiscoTrait.php');
	require_once(dirname(__FILE__) . '/Implementations/CiscoRouter.php');
	require_once(dirname(__FILE__) . '/Implementations/CiscoSwitch.php');
	require_once(dirname(__FILE__) . '/Implementations/IOSXRRouter.php');
	require_once(dirname(__FILE__) . '/Implementations/ArborOS.php');
	require_once(dirname(__FILE__) . '/Implementations/APCPDU.php');
	require_once(dirname(__FILE__) . '/Implementations/GenericShell.php');
