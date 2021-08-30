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
