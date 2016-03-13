# PHP Router Hackiness

This is set of classes designed to allow managing support multiple types of network-device via PHP.

It was designed to allow pulling data from routers in quick-and-dirty scripts.

## Supported Devices

* HP Procurve *(via telnet or ssh)*
* Cisco IOS *(via telnet or ssh)*
* Cisco IOSXR *(via telnet or ssh)*
* ArborOS *(via ssh)*
* APC PDU *(via telnet)*

Currently, support is limited to what I have access to. If you want support for something that isn't, then please provide access to the device in question. (Read-Only access is all that is required.)

## Usage

Usage is simple, clone the repo into a subfolder and then `include('phprouter/PHPRouter.php');` and begin using.

Example usage:

```
<?php
	$router = new CiscoRouter('192.168.0.1', 'admin', 'password');
	$router->connect();
	$router->enable('enable');
	$config = $router->exec('show run');
	$router->disconnect();

	echo $config;
?>
```

Some of the router types have helper functions (ie, the ones I've needed!), eg ArborOS has `saveConfig($logMessage)` and `hasPendingConfig()`, and IOS/IOSXR devices have `getPrefixList($name, $type)`

If you are using ssh-based routers, you will need the [ssh2 module](http://php.net/manual/en/book.ssh2.php) for php.

## Debian / Ubuntu
```
apt-get install libssh2-php
```

## Redhat / CentOS
```
yum install php-pecl-ssh2
```

## Other
```
pecl install ssh2
```

## Comments, Bugs, Feature Requests etc.

Bugs and Feature Requests should be raised on the [issue tracker on github](https://github.com/ShaneMcC/phprouter/issues). I'm happy to recieve code pull requests via github.

Comments can be emailed to [shanemcc@gmail.com](shanemcc@gmail.com)
