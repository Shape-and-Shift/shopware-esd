<?php declare(strict_types=1);

namespace Sas\Esd\Tests\Stubs;

if (class_exists('Shopware\Tests\Unit\Common\Stubs\DataAbstractionLayer\StaticEntityRepository')) {
    class StaticEntityRepository extends \Shopware\Tests\Unit\Common\Stubs\DataAbstractionLayer\StaticEntityRepository
    {
    }
} else {
    class StaticEntityRepository extends \Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository
    {
    }
}
