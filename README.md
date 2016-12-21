# MODX CLI

Run certain MODX tasks from the command line.

*This package is currently under development*

## Install

When this package is finished, you should be able to install it system wide using composer. After that you should be able to do:

```
modxc
```

## Commands

Here is a list of commands to be implemented.

### package:search

```
Usage:
 package:search [options] [--] <name>
Arguments:
 name                  Package name
```

*Example*

```
modxc package:search tiny
modxc package:search pdoTools
```

### package:install

Install a package.

- name/index-number: required, default parameter
- version: optional, latest by default

*Examples*
```
modxc package:install 2
modxc package:install tinyemc --version=1.1.1-pl
```
