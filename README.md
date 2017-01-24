# MODX CLI

[![Build Status](https://travis-ci.org/OptimusCrime/modx-cli.svg?branch=master)](https://travis-ci.org/OptimusCrime/modx-cli)

Convenient command line tool to ease MODX development.

## Install

Install using [Composer](https://getcomposer.org) with

```
composer global require optimuscrime/modx-cli
```

You must also make sure the path where the globally downloaded packages are added to your `PATH`. [Instructions here](https://coderwall.com/p/ma_cuq/using-composer-to-manage-global-packages).

```
modxc
```

## Commands

Below is a list of currently implemented commands

### package:search

Search for a MODX package

*Known issues:*

- You can only search for packages on the official repository (modx.com/extras).

*Help*

```
Usage:
  package:search <name>

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

*Known issues:*

- You can only install for packages on the official repository (modx.com/extras) or from GitHub.
- Packages that are build for [Git-Package-Management](https://github.com/theboxer/Git-Package-Management) are not supported yet.
- You can not specify which version to install when downloading from the MODX.com repository.

*Help*

```
Usage:
  package:install <input>

Arguments:
  input                 Either index value returned by a previous search or a download link to a GitHub repository
```

*Examples*

```
modxc package:install 2
modxc package:install tinyemc
modxc package:install https://github.com/modxcms/pThumb.git
modxc package:install https://github.com/modxcms/Tagger?tag=9b4242525bb37df5a9c9e74ab3684000f9268382
modxc package:install git@github.com:modxcms/Login.git?release=1.9.2
```

*Supported GitHub links*

Following formats are supported when downloading from GitHub:

- github.com/modxcms/Login.git
- http://github.com/modxcms/Login.git
- http://github.com/modxcms/Login
- https://github.com/modxcms/Login
- https://github.com/modxcms/Login.git
- git@github.com/modxcms/Login.git
- git@github.com/modxcms/Login

In addition to these formats, you can also add a question mark and specify further:

- Branch (`branch=branch-name`)
- Tag or Release (`tag=tag-name` or `release=release-name`)
- Commit (`commit=tag-sha`)

For example:

- https://github.com/modxcms/Login?branch=develop
- https://github.com/modxcms/Login?tag=1.9.2
- https://github.com/modxcms/Login?release=1.9.0
- https://github.com/modxcms/Login?commit=e4154d89774c1b8a0b2580909fb53caea6be1e70


