## Coverage merger

Library/tool to merge two or more coverage files into single one.

Supported formats:
* clover
* junit (todo)

### Install

#### As global composer dependency

```shell
composer global require dannecron/coverage-merger
```

#### As local composer dev-dependency

```shell
composer require --dev dannecron/coverage-merger
```

### Usage

It's cli-app with single access point:

```shell
# if installed globally
$COMPOSER_HOME/vendor/bin/merger
# if installed locally
./vendor/bin/merger
```

```text
clover-merger, version 1.0.0

Commands:
*
  clover    Merge clover coverage files into single one

Run `<command> --help` for specific help
```

### Additional info

Clover merge logic based on [d0x2f/CloverMerge](https://github.com/d0x2f/CloverMerge).
