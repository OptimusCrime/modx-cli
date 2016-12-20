# MODX CLI

Run certain MODX tasks from the command line.

*This package is currently under development*

## Install

When this package is finished, you should be able to install it system wide using composer. After that you should be able to do:

```
mcli
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
mcli package:search tiny
mcli package:search pdoTools
```

### package:install

Install a package.

- name/index-number: required, default parameter
- version: optional, latest by default

*Examples*
```
mcli package:install 2
mcli package:install tinyemc --version=1.1.1-pl
```
