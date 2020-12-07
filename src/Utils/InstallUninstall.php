<?php declare(strict_types=1);

namespace Sas\Esd\Utils;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

class InstallUninstall
{
    /**
     * @var EntityRepositoryInterface
     */
    private $mailTemplateTypeRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $mailTemplateRepository;

    public function __construct(
        EntityRepositoryInterface $mailTemplateTypeRepository,
        EntityRepositoryInterface $mailTemplateRepository
    ) {
        $this->mailTemplateTypeRepository = $mailTemplateTypeRepository;
        $this->mailTemplateRepository = $mailTemplateRepository;
    }

    public function uninstall(Context $context): void
    {
        EsdMailTemplate::removeMailTemplate($this->mailTemplateTypeRepository, $this->mailTemplateRepository, $context);
    }
}
