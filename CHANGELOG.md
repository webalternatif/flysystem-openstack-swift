## v0.3.0 (unreleased)

### 💥 Breaking changes

* Bump league/flysystem to version `^3.0` ([52b80e2](https://github.com/webalternatif/flysystem-openstack-swift/commit/52b80e2d876b61bfbf57a77d95c75ee9a30378bf))

## v0.2.1 (December 30, 2021)

### ✨ New features

* Add support of PHP 8.1 ([78c83c5](https://github.com/webalternatif/flysystem-openstack-swift/commit/78c83c525f0d1f42ffa8ac954a6efb11d261df5a))

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
