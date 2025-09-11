# Flysystem v3 OpenStack Swift adapter

[![Source code](https://img.shields.io/badge/source-GitHub-blue)](https://github.com/webalternatif/flysystem-openstack-swift)
[![Software license](https://img.shields.io/github/license/webalternatif/flysystem-openstack-swift)](https://github.com/webalternatif/flysystem-openstack-swift/blob/main/LICENSE)
[![GitHub issues](https://img.shields.io/github/issues/webalternatif/flysystem-openstack-swift)](https://github.com/webalternatif/flysystem-openstack-swift/issues)
[![Test status](https://img.shields.io/github/actions/workflow/status/webalternatif/flysystem-openstack-swift/test.yml?branch=main&label=tests)](https://github.com/webalternatif/flysystem-openstack-swift/actions/workflows/test.yml)
[![Psalm coverage](https://shepherd.dev/github/webalternatif/flysystem-openstack-swift/coverage.svg)](https://psalm.dev)
[![Psalm level](https://shepherd.dev/github/webalternatif/flysystem-openstack-swift/level.svg)](https://psalm.dev)

A [Flysystem][1] v3 adapter for OpenStack Swift, using
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

$adapter = new OpenStackSwiftAdapter($openstack, '{containerName}');

$flysystem = new Filesystem($adapter);
```

### Uploading large objects

To use the `createLargeObject` method of the underlying OpenStack library to
upload [large objects][4] (which is mandatory for files over 5 GB), you must use
the `writeStream` method and define the `segment_size` config option.

The `segment_container` option is also available if you want to upload segments
in another container.

#### Example

```php
use Webf\Flysystem\OpenStackSwift\Config;

$flysystem->writeStream($path, $content, ([
    Config::OPTION_SEGMENT_SIZE => 52428800, // 50 MiB
    Config::OPTION_SEGMENT_CONTAINER => 'test_segments',
]);
```

### Generating temporary URLs

This adapter supports generating temporary URLs as described in
[Flysystem's documentation][5].

To do so, you must :
- set a secret key at the _account_ or _container_ level of your OpenStack Swift
  instance (see details in the [OpenStack documentation][6]),
- provide this secret key as third argument (`$tempUrlKey`) when creating the
  adapter.

#### Available options

When calling `Filesystem::temporaryUrl()`, you can pass the following options as
third argument (`$config`):

| Option key  | Description                                                                                                    | Type     | Default value |
|-------------|----------------------------------------------------------------------------------------------------------------|----------|---------------|
| `digest`    | The digest algorithm to use for the HMAC cryptographic signature (given as first parameter of [hash_hmac][7]). | `string` | `'sha256'`    |
| `file_name` | A string to override the default file name (which is based on the object name) when the file is downloaded.    | `string` | `null`        |
| `prefix`    | If `true`, a prefix-based temporary URL will be generated.                                                     | `bool`   | `false`       |

Those option keys are available as public constants in the
`Webf\Flysystem\OpenStackSwift\Config` class.

More information about those options can be found in the
[OpenStack documentation][8].

#### Example

```php
use League\Flysystem\Filesystem;
use Webf\Flysystem\OpenStackSwift\OpenStackSwiftAdapter;

// ... (see above)

$adapter = new OpenStackSwiftAdapter($openstack, '{containerName}', '{tempUrlKey}');
$flysystem = new Filesystem($adapter);

$flysystem->temporaryUrl($path, new DateTime('+1 hour'), [
    // options...
]);
```

## Tests

This library uses the `FilesystemAdapterTestCase` provided by
[`league/flysystem-adapter-test-utilities`][9], so it performs integration tests
that need a real OpenStack Swift container.

To run tests, duplicate the `phpunit.xml.dist` file into `phpunit.xml` and fill
all the environment variables, then run:

```bash
$ composer test
```

This will run [Psalm][10] and [PHPUnit][11], but you can run them individually
like this:

```bash
$ composer psalm
$ composer phpunit
```

[1]: https://flysystem.thephpleague.com
[2]: https://github.com/php-opencloud/openstack
[3]: https://github.com/chrisnharvey/flysystem-openstack-swift
[4]: https://php-openstack-sdk.readthedocs.io/en/latest/services/object-store/v1/objects.html#create-a-large-object-over-5gb
[5]: https://flysystem.thephpleague.com/docs/usage/temporary-urls
[6]: https://docs.openstack.org/swift/latest/api/temporary_url_middleware.html#secret-keys
[7]: https://www.php.net/manual/en/function.hash-hmac.php
[8]: https://docs.openstack.org/swift/latest/api/temporary_url_middleware.html
[9]: https://github.com/thephpleague/flysystem-adapter-test-utilities
[10]: https://psalm.dev
[11]: https://phpunit.de
