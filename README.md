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

#### As docker-container

Images published on [hub.docker.com](https://hub.docker.com/r/dannecron/coverage-merger)

```shell
docker pull dannecron/coverage-merger:latest
```

### Usage

It's cli-app with single access point:

```shell
# if installed globally
$COMPOSER_HOME/vendor/bin/merger
# if installed locally
./vendor/bin/merger
# if pulled from docker hub
docker run --rm dannecron/coverage-merger:latest
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
