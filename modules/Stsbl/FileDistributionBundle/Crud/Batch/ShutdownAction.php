<?php

declare(strict_types=1);

namespace Stsbl\FileDistributionBundle\Crud\Batch;

use Doctrine\Common\Collections\ArrayCollection;
use IServ\ComputerBundle\Security\Privilege;
use IServ\CrudBundle\Entity\FlashMessageBag;
use Stsbl\FileDistributionBundle\Entity\FileDistribution;

final class ShutdownAction extends AbstractFileDistributionAction
{
    use Traits\NoopFormTrait;

    /**
     * {@inheritDoc}
     */
    protected $privileges = Privilege::BOOT;

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'shutdown';
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel(): string
    {
        return _('Shutdown');
    }

    /**
     * {@inheritDoc}
     */
    public function getTooltip(): string
    {
        return _('Shuts the selected computers down.');
    }

    /**
     * {@inheritDoc}
     */
    public function getListIcon(): string
    {
        return 'off';
    }

    /**
     * {@inheritDoc}
     */
    public function getGroup(): string
    {
        return _('Start & Shutdown');
    }

    /**
     * {@inheritDoc}
     */
    public function execute(ArrayCollection $entities): FlashMessageBag
    {
        /** @var FileDistribution[] $entities */
        $hosts = [];

        foreach ($entities as $entity) {
            $hosts[] = $entity->getHost();
        }
        $messages = [];

        foreach ($hosts as $entity) {
            $messages[] = $this->createFlashMessage('success', __('Sent shutdown command to %s.', (string)$entity->getName()));
        }

        $bag = $this->getFileDistributionManager()->shutdown($hosts);
        // add messages created during work
        foreach ($messages as $message) {
            $bag->add($message);
        }

        return $bag;
    }
}
