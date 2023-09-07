<?php declare(strict_types=1);

namespace Sas\Esd\Tests\Stubs;

if (class_exists('Shopware\Tests\Unit\Common\Stubs\SystemConfigService\StaticSystemConfigService')) {
    class StaticSystemConfigService extends \Shopware\Tests\Unit\Common\Stubs\SystemConfigService\StaticSystemConfigService
    {
    }
} else {
    class StaticSystemConfigService extends \Shopware\Core\Test\Stub\SystemConfigService\StaticSystemConfigService
    {
    }
}
