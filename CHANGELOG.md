## v0.3.0 (unreleased)

### 💥 Breaking changes

* Bump league/flysystem to version `^3.0`

## v0.2.1 (December 30, 2021)

### ✨ New features

* Add support of PHP 8.1

## v0.2.0 (August 30, 2021)

### 💥 Breaking changes

* `Webf\Flysystem\OpenStackSwift\OpenStackSwiftAdapter::__construct` now takes an `OpenStack\OpenStack` instance and a `string` (for container name) as parameters.
  ```diff
    $openstack = new OpenStack([/* ... */]);
  - $container = $openstack->objectStoreV1()->getContainer('{containerName}');
  - $adapter = new OpenStackSwiftAdapter($container);
  + $adapter = new OpenStackSwiftAdapter($openstack, '{containerName}');
  ```

## v0.1.0 (August 29, 2021)

First version.
