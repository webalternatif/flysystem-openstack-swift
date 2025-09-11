<?php

namespace Tests\Webf\Flysystem\OpenStackSwift;

use League\Flysystem\AdapterTestUtilities\FilesystemAdapterTestCase;
use League\Flysystem\Config;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\StorageAttributes;
use League\Flysystem\Visibility;
use OpenStack\Common\Error\BadResponseError;
use OpenStack\ObjectStore\v1\Models\Container;
use OpenStack\ObjectStore\v1\Models\StorageObject;
use OpenStack\OpenStack;
use Webf\Flysystem\OpenStackSwift\OpenStackSwiftAdapter;

/**
 * @internal
 *
 * @covers \Webf\Flysystem\OpenStackSwift\OpenStackSwiftAdapter
 */
class OpenStackSwiftAdapterTest extends FilesystemAdapterTestCase
{
    private static ?Container $container = null;
    private static ?string $containerName = null;

    protected static function createFilesystemAdapter(): FilesystemAdapter
    {
        $openstack = new OpenStack([
            'authUrl' => $_ENV['OPENSTACK_AUTH_URL'],
            'region' => $_ENV['OPENSTACK_REGION'],
            'user' => [
                'name' => $_ENV['OPENSTACK_USERNAME'],
                'password' => $_ENV['OPENSTACK_PASSWORD'],
                'domain' => ['id' => 'default'],
            ],
            'scope' => ['project' => ['id' => $_ENV['OPENSTACK_PROJECT_ID']]],
        ]);

        self::$container = $openstack->objectStoreV1()->createContainer([
            'name' => self::$containerName,
        ]);

        self::$container->execute(
            [
                'method' => 'POST',
                'path' => self::$containerName,
                'params' => [
                    'tempUrlKey' => [
                        'location' => 'header',
                        'sentAs' => 'X-Container-Meta-Temp-URL-Key',
                    ],
                ],
            ],
            ['tempUrlKey' => $containerTempUrlKey = uniqid()]
        );

        return new OpenStackSwiftAdapter($openstack, self::$containerName, $containerTempUrlKey);
    }

    public static function setUpBeforeClass(): void
    {
        if (null === self::$containerName) {
            self::$containerName = uniqid($_ENV['OPENSTACK_CONTAINER_NAME_PREFIX'] ?? '');
        }

        parent::setUpBeforeClass();
    }

    public static function tearDownAfterClass(): void
    {
        if (null !== self::$container) {
            try {
                /** @var StorageObject $object */
                foreach (self::$container->listObjects() as $object) {
                    try {
                        $object->delete();
                    } catch (BadResponseError $e) {
                        if (404 !== $e->getResponse()->getStatusCode()) {
                            throw $e;
                        }
                    }
                }

                self::$container->delete();
            } catch (BadResponseError $e) {
                if (404 !== $e->getResponse()->getStatusCode()) {
                    throw $e;
                }
            }

            self::$container = null;
        }

        parent::tearDownAfterClass();
    }

    /**
     * Visibility-related assertions are commented because they are not
     * supported by OpenStack Swift.
     *
     * @test
     */
    public function overwriting_a_file(): void
    {
        $this->runScenario(function () {
            $this->givenWeHaveAnExistingFile('path.txt', 'contents', ['visibility' => Visibility::PUBLIC]);
            $adapter = $this->adapter();

            $adapter->write('path.txt', 'new contents', new Config(['visibility' => Visibility::PRIVATE]));

            $contents = $adapter->read('path.txt');
            $this->assertEquals('new contents', $contents);
            // $visibility = $adapter->visibility('path.txt')->visibility();
            // $this->assertEquals(Visibility::PRIVATE, $visibility);
        });
    }

    public function checking_if_a_directory_exists_after_creating_it(): void
    {
        // Directory creation is not supported
    }

    /**
     * Directories may not be provided in deep listings. See {@link https://github.com/thephpleague/flysystem-adapter-test-utilities/issues/3}.
     *
     * @test
     */
    public function listing_contents_recursive(): void
    {
        $this->runScenario(function () {
            $adapter = $this->adapter();
            $adapter->createDirectory('path', new Config());
            $adapter->write('path/file.txt', 'string', new Config());

            $listing = $adapter->listContents('', true);
            /** @var StorageAttributes[] $items */
            $items = iterator_to_array($listing);
            // $this->assertCount(2, $items, $this->formatIncorrectListingCount($items));

            $paths = array_map(
                fn (StorageAttributes $item) => $item->path(),
                $items
            );
            sort($paths);

            switch (count($paths)) {
                case 1:
                    $this->assertEquals(['path/file.txt'], $paths);
                    break;
                case 2:
                    $this->assertEquals(['path', 'path/file.txt'], $paths);
                    break;
                default:
                    $this->fail(sprintf("%s\n\nThere must be exactly 1 or 2 items.", $this->formatIncorrectListingCount($items)));
            }
        });
    }

    public function setting_visibility(): void
    {
        // OpenStack Swift does not support per-file visibility.
    }

    /**
     * Visibility-related assertions are commented because they are not
     * supported by OpenStack Swift.
     *
     * @test
     */
    public function copying_a_file(): void
    {
        $this->runScenario(function () {
            $adapter = $this->adapter();
            $adapter->write(
                'source.txt',
                'contents to be copied',
                new Config([Config::OPTION_VISIBILITY => Visibility::PUBLIC])
            );

            $adapter->copy('source.txt', 'destination.txt', new Config());

            $this->assertTrue($adapter->fileExists('source.txt'));
            $this->assertTrue($adapter->fileExists('destination.txt'));
            // $this->assertEquals(Visibility::PUBLIC, $adapter->visibility('destination.txt')->visibility());
            $this->assertEquals('contents to be copied', $adapter->read('destination.txt'));
        });
    }

    /**
     * Visibility-related assertions are commented because they are not
     * supported by OpenStack Swift.
     *
     * @test
     */
    public function copying_a_file_again(): void
    {
        $this->runScenario(function () {
            $adapter = $this->adapter();
            $adapter->write(
                'source.txt',
                'contents to be copied',
                new Config([Config::OPTION_VISIBILITY => Visibility::PUBLIC])
            );

            $adapter->copy('source.txt', 'destination.txt', new Config());

            $this->assertTrue($adapter->fileExists('source.txt'));
            $this->assertTrue($adapter->fileExists('destination.txt'));
            // $this->assertEquals(Visibility::PUBLIC, $adapter->visibility('destination.txt')->visibility());
            $this->assertEquals('contents to be copied', $adapter->read('destination.txt'));
        });
    }

    /**
     * Visibility-related assertions are commented because they are not
     * supported by OpenStack Swift.
     *
     * @test
     */
    public function moving_a_file(): void
    {
        $this->runScenario(function () {
            $adapter = $this->adapter();
            $adapter->write(
                'source.txt',
                'contents to be copied',
                new Config([Config::OPTION_VISIBILITY => Visibility::PUBLIC])
            );
            $adapter->move('source.txt', 'destination.txt', new Config());
            $this->assertFalse(
                $adapter->fileExists('source.txt'),
                'After moving a file should no longer exist in the original location.'
            );
            $this->assertTrue(
                $adapter->fileExists('destination.txt'),
                'After moving, a file should be present at the new location.'
            );
            // $this->assertEquals(Visibility::PUBLIC, $adapter->visibility('destination.txt')->visibility());
            $this->assertEquals('contents to be copied', $adapter->read('destination.txt'));
        });
    }

    public function fetching_unknown_mime_type_of_a_file(): void
    {
        // OpenStack Swift gives a mime type to every files
    }

    public function creating_a_directory(): void
    {
        // Directory creation is not supported
    }

    /**
     * Directory creation is not supported.
     *
     * @test
     */
    public function file_exists_on_directory_is_false(): void
    {
        $this->runScenario(function () {
            $adapter = $this->adapter();

            $this->assertFalse($adapter->directoryExists('test'));
            $adapter->createDirectory('test', new Config());

            // $this->assertTrue($adapter->directoryExists('test'));
            $this->assertFalse($adapter->fileExists('test'));
        });
    }

    public function test_listing_slash_is_equivalent_to_listing_empty_string(): void
    {
        $this->runScenario(function () {
            $adapter = $this->adapter();
            $adapter->write('file.txt', 'string', new Config());
            $adapter->write('path/file.txt', 'string', new Config());

            $this->assertEquals(
                iterator_to_array($adapter->listContents('', false)),
                iterator_to_array($adapter->listContents('/', false))
            );

            $this->assertEquals(
                iterator_to_array($adapter->listContents('', true)),
                iterator_to_array($adapter->listContents('/', true))
            );
        });
    }

    public function test_temporary_url_cannot_be_used_to_access_another_file(): void
    {
        $adapter = $this->adapter();

        $adapter->write('some/file1.txt', 'file 1 contents', new Config(['visibility' => 'private']));
        $adapter->write('some/file2.txt', 'file 2 contents', new Config(['visibility' => 'private']));

        $expiresAt = (new \DateTimeImmutable())->add(\DateInterval::createFromDateString('1 minute'));
        $url = $adapter->temporaryUrl('some/file1.txt', $expiresAt, new Config());
        $contents = file_get_contents($url);
        self::assertEquals('file 1 contents', $contents);

        $url = str_replace('file1.txt', 'file2.txt', $url);
        self::assertFalse(@file_get_contents($url));
    }

    public function test_generating_prefixed_temporary_url(): void
    {
        $adapter = $this->adapter();

        $adapter->write('some/file1.txt', 'file 1 contents', new Config(['visibility' => 'private']));
        $adapter->write('some/file2.txt', 'file 2 contents', new Config(['visibility' => 'private']));

        $expiresAt = (new \DateTimeImmutable())->add(\DateInterval::createFromDateString('1 minute'));
        $url = $adapter->temporaryUrl('some', $expiresAt, new Config(['prefix' => true]));

        $contents = file_get_contents(str_replace('/some?', '/some/file1.txt?', $url));
        self::assertEquals('file 1 contents', $contents);

        $contents = file_get_contents(str_replace('/some?', '/some/file2.txt?', $url));
        self::assertEquals('file 2 contents', $contents);
    }
}
