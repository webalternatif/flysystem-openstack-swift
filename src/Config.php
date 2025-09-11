<?php

declare(strict_types=1);

namespace Webf\Flysystem\OpenStackSwift;

use League\Flysystem\Config as BaseConfig;

final class Config extends BaseConfig
{
    // Options used by OpenStackSwiftAdapter::writeStream()
    public const OPTION_SEGMENT_SIZE = 'segment_size';
    public const OPTION_SEGMENT_CONTAINER = 'segment_container';

    // Options used by OpenStackSwiftAdapter::createFileAttributesFrom()
    public const OPTION_DIGEST = 'digest';
    public const OPTION_FILE_NAME = 'file_name';
    public const OPTION_PREFIX = 'prefix';
}
