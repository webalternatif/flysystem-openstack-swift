# Flysystem v2 OpenStack Swift adapter

[![Source code](https://img.shields.io/badge/source-GitHub-blue)](https://github.com/webalternatif/flysystem-openstack-swift)
[![Software license](https://img.shields.io/github/license/webalternatif/flysystem-openstack-swift)](https://github.com/webalternatif/flysystem-openstack-swift/blob/master/LICENSE)
[![GitHub issues](https://img.shields.io/github/issues/webalternatif/flysystem-openstack-swift)](https://github.com/webalternatif/flysystem-openstack-swift/issues)
[![Test status](https://img.shields.io/github/workflow/status/webalternatif/flysystem-openstack-swift/test?label=tests)](https://github.com/webalternatif/flysystem-openstack-swift/actions/workflows/test.yml)
[![Psalm coverage](https://shepherd.dev/github/webalternatif/flysystem-openstack-swift/coverage.svg)](https://psalm.dev)
[![Psalm level](https://shepherd.dev/github/webalternatif/flysystem-openstack-swift/level.svg)](https://psalm.dev)

A [Flysystem][1] v2 adapter for OpenStack Swift, using
[`php-opencloud/openstack`][2].

If you're looking for a Flysystem v1 adapter, see
[`chrisnharvey/flysystem-openstack-swift`][3].

## Installation

```bash
$ composer require webalternatif/flysystem-openstack-swift
```

## Usage

```php
use League\Flysystem\Filesystem;
use OpenStack\OpenStack;
use Webf\Flysystem\OpenStackSwift\OpenStackSwiftAdapter;

$openstack = new OpenStack([
    'authUrl' => '{authUrl}',
    'region' => '{region}',
    'user' => [
        'id' => '{userId}',
        'password' => '{password}',
    ],
    'scope' => ['project' => ['id' => '{projectId}']],
]);

$container = $openstack->objectStoreV1()->getContainer('{containerName}');

$adapter = new OpenStackSwiftAdapter($container);

$flysystem = new Filesystem($adapter);
```

### Uploading large objects

In order to use the `createLargeObject` method of the underlying OpenStack
library to upload [large objects][4] (which is mandatory for files over 5 GB),
you must use the `writeStream` method and define the `segment_size` config
option.

The `segment_container` option is also available if you want to upload segments
in another container.

#### Example

```php
use Webf\Flysystem\OpenStackSwift\Config;

$flysystem->writeStream($path, $content, new Config([
    Config::OPTION_SEGMENT_SIZE => 52428800, // 50 MiB
    Config::OPTION_SEGMENT_CONTAINER => 'test_segments',
]));
```

## Tests

This library uses the `FilesystemAdapterTestCase` provided by
[`league/flysystem-adapter-test-utilities`][5], so it performs integration tests
that need a real OpenStack Swift container.

To run tests, duplicate the `phpunit.xml.dist` file into `phpunit.xml` and fill
all the environment variables, then run:

```bash
$ composer test
```

This will run [Psalm][6] and [PHPUnit][7], but you can run them individually
like this:

```bash
$ composer psalm
$ composer phpunit
```

[1]: https://flysystem.thephpleague.com
[2]: https://github.com/php-opencloud/openstack
[3]: https://github.com/chrisnharvey/flysystem-openstack-swift
[4]: https://php-openstack-sdk.readthedocs.io/en/latest/services/object-store/v1/objects.html#create-a-large-object-over-5gb
[5]: https://github.com/thephpleague/flysystem-adapter-test-utilities
[6]: https://psalm.dev
[7]: https://phpunit.de
