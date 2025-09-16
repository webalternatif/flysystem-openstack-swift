## v0.5.0 (September 16, 2025)

### 💥 Breaking changes

* Make classes final ([#9](https://github.com/webalternatif/flysystem-openstack-swift/pull/9))

### ✨ New features

* Add support for temporary URLs ([#9](https://github.com/webalternatif/flysystem-openstack-swift/pull/9))

## v0.4.0 (February 8, 2025)

### 💥 Breaking changes

* Drop support for PHP 8.0 ([#7](https://github.com/webalternatif/flysystem-openstack-swift/pull/7))

### ✨ New features

* Add support for PHP 8.4 ([#7](https://github.com/webalternatif/flysystem-openstack-swift/pull/7))

### 🐛 Bug fixes

* Fix `OpenStackSwiftAdapter::move` deleting the file if source and destination are the same (detected by https://github.com/thephpleague/flysystem-adapter-test-utilities/commit/83b40c1a8a8a40be435a9683a7554396134ab1c4) ([#7](https://github.com/webalternatif/flysystem-openstack-swift/pull/7))
* Fix `OpenStackSwiftAdapter::directoryExists` returning `true` for files (detected by https://github.com/thephpleague/flysystem-adapter-test-utilities/commit/ab92311b06ca0bdb5a16e93dc29be7774c1c6f2a) ([#7](https://github.com/webalternatif/flysystem-openstack-swift/pull/7))

## v0.3.2 (May 6, 2024)

### ✨ New features

* Add support for PHP 8.3 ([#6](https://github.com/webalternatif/flysystem-openstack-swift/pull/6))

## v0.3.1 (December 15, 2022)

### ✨ New features

* Add support for PHP 8.2 ([#2](https://github.com/webalternatif/flysystem-openstack-swift/pull/2))

### 🐛 Bug fixes

* Transform all exceptions to FilesystemException ([ece0468](https://github.com/webalternatif/flysystem-openstack-swift/commit/ece0468d73b67b47d2d6b86e87f7bc4d61d0966b))

## v0.3.0 (April 2, 2022)

### 💥 Breaking changes

* Bump league/flysystem to version `^3.0` ([52b80e2](https://github.com/webalternatif/flysystem-openstack-swift/commit/52b80e2d876b61bfbf57a77d95c75ee9a30378bf))

## v0.2.1 (December 30, 2021)

### ✨ New features

* Add support for PHP 8.1 ([78c83c5](https://github.com/webalternatif/flysystem-openstack-swift/commit/78c83c525f0d1f42ffa8ac954a6efb11d261df5a))

## v0.2.0 (August 30, 2021)

### 💥 Breaking changes

* `Webf\Flysystem\OpenStackSwift\OpenStackSwiftAdapter::__construct` now takes an `OpenStack\OpenStack` instance and a `string` (for container name) as parameters. ([f0881a3](https://github.com/webalternatif/flysystem-openstack-swift/commit/f0881a3a6dcd13e609031595e4fffb3680b915ed))
  ```diff
    $openstack = new OpenStack([/* ... */]);
  - $container = $openstack->objectStoreV1()->getContainer('{containerName}');
  - $adapter = new OpenStackSwiftAdapter($container);
  + $adapter = new OpenStackSwiftAdapter($openstack, '{containerName}');
  ```

## v0.1.0 (August 29, 2021)

First version. ([0cd4fa2](https://github.com/webalternatif/flysystem-openstack-swift/commit/0cd4fa27f1ac8604dab16d30b21ab8f77f4167a8))
