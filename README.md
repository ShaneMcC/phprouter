# PHPRouter

Provides a library designed to allow reading/writing data to/from multiple types of network-device via PHP to aid in automation.

It was originally designed just for reading data from routers in scripts but evolved to support other device types and usages.

## Supported Devices

|  Device Type          |      Telnet?       |        SSH?        |   
| --------------------- |:------------------:|:------------------:|
| HP Procurve           | :white_check_mark: | :white_check_mark: |
| Cisco IOS             | :white_check_mark: | :white_check_mark: |
| Cisco IOSXR           | :white_check_mark: | :white_check_mark: |
| ArborOS               |        :x:         | :white_check_mark: |
| APC PDU               | :white_check_mark: |        :x:         |

Currently, support is limited to what I have access to. If you want support for something that isn't, then please feel free to submit a pull-request, or provide access to the device in question. (Read-Only should be sufficient in most cases.)

## Usage

Usage is simple, clone the repo into a subfolder, run `git submodule update --init` to pull in the submodules and then `include('phprouter/PHPRouter.php');` and begin using.

Example usage:

```php
<?php
	$router = new CiscoRouter('192.168.0.1', 'admin', 'password');
	$router->connect();
	$router->enable('enable');
	$config = $router->exec('show run');
	$router->disconnect();

	echo $config;
?>
```

Further more in-depth example usage is documented [on the wiki](https://github.com/ShaneMcC/phprouter/wiki/Example-Usage---Reading-and-Writing-Data)

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
