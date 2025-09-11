<?php

declare(strict_types=1);

namespace Webf\Flysystem\OpenStackSwift;

use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\StreamWrapper;
use League\Flysystem\Config as BaseConfig;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToCheckDirectoryExistence;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\UrlGeneration\TemporaryUrlGenerator;
use OpenStack\Common\Error\BadResponseError;
use OpenStack\ObjectStore\v1\Models\Container;
use OpenStack\ObjectStore\v1\Models\StorageObject;
use OpenStack\OpenStack;

final class OpenStackSwiftAdapter implements FilesystemAdapter, TemporaryUrlGenerator
{
    private ?Container $container = null;

    public function __construct(
        private OpenStack $openStack,
        private string $containerName,
        private ?string $tempUrlKey = null,
    ) {
    }

    private function getContainer(): Container
    {
        if (null === $this->container) {
            $this->container = $this->openStack
                ->objectStoreV1()
                ->getContainer($this->containerName)
            ;
        }

        return $this->container;
    }

    #[\Override]
    public function fileExists(string $path): bool
    {
        try {
            return $this->getContainer()->objectExists($path);
        } catch (\Exception $e) {
            throw UnableToCheckFileExistence::forLocation($path, $e);
        }
    }

    #[\Override]
    public function directoryExists(string $path): bool
    {
        try {
            return $this->getContainer()
                ->listObjects(['prefix' => $path.'/'])
                ->valid()
            ;
        } catch (\Exception $e) {
            throw UnableToCheckDirectoryExistence::forLocation($path, $e);
        }
    }

    #[\Override]
    public function write(string $path, string $contents, BaseConfig $config): void
    {
        $data = [
            'name' => $path,
            'content' => $contents,
        ];

        try {
            $this->getContainer()->createObject($data);
        } catch (\Exception $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    #[\Override]
    public function writeStream(string $path, $contents, BaseConfig $config): void
    {
        $data = [
            'name' => $path,
            'stream' => new Stream($contents),
        ];

        $segmentSize = (int) $config->get(Config::OPTION_SEGMENT_SIZE);
        /** @var string $segmentContainer */
        $segmentContainer = $config->get(
            Config::OPTION_SEGMENT_CONTAINER,
            $this->getContainer()->name
        );

        try {
            if ($segmentSize > 0) {
                $data['segmentSize'] = $segmentSize;
                $data['segmentContainer'] = $segmentContainer;

                $this->getContainer()->createLargeObject($data);
            } else {
                $this->getContainer()->createObject($data);
            }
        } catch (\Exception $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    #[\Override]
    public function read(string $path): string
    {
        $object = $this->getContainer()->getObject($path);

        try {
            $stream = $object->download();

            if ($stream->isSeekable()) {
                $stream->rewind();
            }

            return $stream->getContents();
        } catch (\Exception $e) {
            throw UnableToReadFile::fromLocation($path, $e->getMessage(), $e);
        }
    }

    #[\Override]
    public function readStream(string $path)
    {
        $object = $this->getContainer()->getObject($path);

        try {
            $stream = $object->download([
                'requestOptions' => [
                    'stream' => true,
                ],
            ]);

            if ($stream->isSeekable()) {
                $stream->rewind();
            }

            return StreamWrapper::getResource($stream);
        } catch (\Exception $e) {
            throw UnableToReadFile::fromLocation($path, $e->getMessage(), $e);
        }
    }

    #[\Override]
    public function delete(string $path): void
    {
        $object = $this->getContainer()->getObject($path);

        try {
            $object->delete();
        } catch (BadResponseError $e) {
            if (404 !== $e->getResponse()->getStatusCode()) {
                throw UnableToDeleteFile::atLocation($path, $e->getMessage(), $e);
            }
        } catch (\Exception $e) {
            throw UnableToDeleteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    #[\Override]
    public function deleteDirectory(string $path): void
    {
        // Make sure a slash is added to the end.
        $path = rtrim($path, '/').'/';

        /** @var iterable<StorageObject> $objects */
        $objects = $this->getContainer()->listObjects([
            'prefix' => $path,
        ]);

        try {
            foreach ($objects as $object) {
                try {
                    $object->delete();
                } catch (BadResponseError $e) {
                    if (404 !== $e->getResponse()->getStatusCode()) {
                        throw $e;
                    }
                }
            }
        } catch (\Exception $e) {
            throw UnableToDeleteDirectory::atLocation($path, $e->getMessage(), $e);
        }
    }

    #[\Override]
    public function createDirectory(string $path, BaseConfig $config): void
    {
        // TODO add option in constructor to enable creating empty files to simulate directories
    }

    #[\Override]
    public function setVisibility(string $path, string $visibility): void
    {
        throw UnableToSetVisibility::atLocation($path, 'OpenStack Swift does not support per-file visibility.');
    }

    #[\Override]
    public function visibility(string $path): FileAttributes
    {
        throw UnableToRetrieveMetadata::visibility($path, 'OpenStack Swift does not support per-file visibility.');
    }

    #[\Override]
    public function mimeType(string $path): FileAttributes
    {
        $object = $this->getContainer()->getObject($path);

        try {
            $object->retrieve();
        } catch (\Exception $e) {
            throw UnableToRetrieveMetadata::mimeType($path, $e->getMessage(), $e);
        }

        $fileAttributes = $this->createFileAttributesFrom($object);

        if (null === $fileAttributes->mimeType()) {
            throw UnableToRetrieveMetadata::mimeType($path, 'The mime-type is empty.');
        }

        return $fileAttributes;
    }

    #[\Override]
    public function lastModified(string $path): FileAttributes
    {
        $object = $this->getContainer()->getObject($path);

        try {
            $object->retrieve();
        } catch (\Exception $e) {
            throw UnableToRetrieveMetadata::lastModified($path, $e->getMessage(), $e);
        }

        $fileAttributes = $this->createFileAttributesFrom($object);

        if (null === $fileAttributes->lastModified()) {
            throw UnableToRetrieveMetadata::lastModified($path, sprintf('Unable to parse "%s" as date.', print_r($object->lastModified, true)));
        }

        return $fileAttributes;
    }

    #[\Override]
    public function fileSize(string $path): FileAttributes
    {
        $object = $this->getContainer()->getObject($path);

        try {
            $object->retrieve();
        } catch (\Exception $e) {
            throw UnableToRetrieveMetadata::fileSize($path, $e->getMessage(), $e);
        }

        $fileAttributes = $this->createFileAttributesFrom($object);

        if (null === $fileAttributes->fileSize()) {
            throw UnableToRetrieveMetadata::fileSize($path, sprintf('Invalid file size "%s".', $object->contentLength));
        }

        return $fileAttributes;
    }

    #[\Override]
    public function listContents(string $path, bool $deep): iterable
    {
        $path = trim($path, '/');
        $prefix = empty($path) ? '' : $path.'/';
        /** @var iterable<StorageObject> $objects */
        $objects = $this->getContainer()->listObjects(['prefix' => $prefix]);

        if ($deep) {
            foreach ($objects as $object) {
                yield $this->createFileAttributesFrom($object);
            }
        } else {
            $lastYieldedDirectory = null;

            foreach ($objects as $object) {
                $dirname = dirname($object->name);
                if ('.' === $dirname) {
                    // A dot is returned if there is no slash in path
                    $dirname = '';
                }

                if ($dirname === $path) {
                    yield $this->createFileAttributesFrom($object);
                } elseif (str_starts_with($object->name, empty($path) ? '' : $path.'/')) {
                    $relativeName = trim(substr($object->name, strlen($path)), '/');
                    $firstDirectory = explode('/', $relativeName)[0];

                    if ($lastYieldedDirectory !== $firstDirectory) {
                        $lastYieldedDirectory = $firstDirectory;

                        yield new DirectoryAttributes(trim(sprintf('%s/%s', $path, $firstDirectory), '/'));
                    }
                }
            }
        }
    }

    #[\Override]
    public function move(string $source, string $destination, BaseConfig $config): void
    {
        if ($source === $destination) {
            return;
        }

        try {
            $this->copy($source, $destination, $config);
            $this->delete($source);
        } catch (FilesystemException $e) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $e);
        }
    }

    #[\Override]
    public function copy(string $source, string $destination, BaseConfig $config): void
    {
        $object = $this->getContainer()->getObject($source);

        try {
            $object->copy([
                'destination' => sprintf('%s/%s', $this->getContainer()->name, $destination),
            ]);
        } catch (\Exception $e) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $e);
        }
    }

    private function createFileAttributesFrom(StorageObject $object): FileAttributes
    {
        $fileSize = (int) $object->contentLength;

        if (0 === $fileSize && '0' !== $object->contentLength) {
            $fileSize = null;
        }

        /** @var \DateTimeInterface|string $lastModified */
        $lastModified = $object->lastModified;

        if ($lastModified instanceof \DateTimeInterface) {
            $lastModified = $lastModified->getTimestamp();
        } else {
            $lastModified = strtotime($lastModified);
            if (!($lastModified > 0)) {
                $lastModified = null;
            }
        }

        $mimeType = empty($object->contentType) ? null : $object->contentType;

        return new FileAttributes(
            $object->name,
            $fileSize,
            null,
            $lastModified,
            $mimeType
        );
    }

    /**
     * Available options in {@param $config} are:
     * - {@see Config::OPTION_DIGEST}: the digest algorithm to use for the HMAC cryptographic signature (given as first
     *   parameter of {@see hash_hmac}), default: sha256.
     * - {@see Config::OPTION_FILE_NAME}: a string to override the default file name (which is based on the object name).
     * - {@see Config::OPTION_PREFIX}: if `true`, a prefix-based temporary URL will be generated, default: `false`.
     *
     * For more information, see {@see https://docs.openstack.org/swift/latest/api/temporary_url_middleware.html}.
     */
    #[\Override]
    public function temporaryUrl(string $path, \DateTimeInterface $expiresAt, BaseConfig $config): string
    {
        $expires = $expiresAt->getTimestamp();

        $queryParams = [
            'temp_url_expires' => $expires,
        ];

        $url = $this->getContainer()->getObject($path)->getPublicUri()->__toString();
        $hmacPath = preg_replace('#(.*)v1#U', '/v1', $url, 1);

        if (true === $config->get(Config::OPTION_PREFIX)) {
            $hmacPath = "prefix:{$hmacPath}";
            $queryParams['temp_url_prefix'] = $path;
        }

        $hmacBody = "GET\n{$expires}\n{$hmacPath}";
        $digest = (string) $config->get(Config::OPTION_DIGEST, 'sha256');
        $queryParams['temp_url_sig'] = hash_hmac($digest, $hmacBody, $this->tempUrlKey ?? '');

        if (null !== ($fileName = $config->get(Config::OPTION_FILE_NAME))) {
            $queryParams['filename'] = $fileName;
        }

        return sprintf(
            "{$url}?%s",
            join('&', array_map(fn ($k, $v) => "{$k}={$v}", array_keys($queryParams), $queryParams))
        );
    }
}
