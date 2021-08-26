<?php

declare(strict_types=1);

namespace Webf\Flysystem\OpenStackSwift;

use League\Flysystem\Config as BaseConfig;

class Config extends BaseConfig
{
    public const OPTION_SEGMENT_SIZE = 'segment_size';
    public const OPTION_SEGMENT_CONTAINER = 'segment_container';
}
