<?php

declare(strict_types=1);

namespace Stsbl\FileDistributionBundle\Crud\Batch;

use Doctrine\Common\Collections\ArrayCollection;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\CrudBundle\Entity\FlashMessageBag;
use Stsbl\FileDistributionBundle\Security\Privilege;
use Symfony\Component\Security\Core\User\UserInterface;

final class ExamOffAction extends AbstractFileDistributionAction
{
    use Traits\NoopFormTrait;

    /**
     * {@inheritDoc}
     */
    protected $privileges = Privilege::EXAM;

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'exam_off';
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel(): string
    {
        return _('Deactivate');
    }

    /**
     * {@inheritDoc}
     */
    public function getTooltip(): string
    {
        return _('Deactivate exam mode on the selected computers.');
    }

    /**
     * {@inheritDoc}
     */
    public function getListIcon(): string
    {
        return 'pro-disk-save';
    }

    /**
     * {@inheritDoc}
     */
    public function getGroup(): string
    {
        return _('Exam Mode');
    }

    /**
     * {@inheritDoc}
     */
    public function execute(ArrayCollection $entities): FlashMessageBag
    {
        $messages = [];

        foreach ($entities as $key => $entity) {
            $messages[] = $this->createFlashMessage('success', __('Deactivated exam mode on %s.', (string)$entity->getName()));
        }

        $bag = $this->getFileDistributionManager()->examOff($entities);
        // add messages created during work
        foreach ($messages as $message) {
            $bag->add($message);
        }

        return $bag;
    }

    /**
     * {@inheritDoc}
     */
    public function isAllowedToExecute(CrudInterface $object, UserInterface $user): bool
    {
        return $this->crud->authorizationChecker()->isGranted(Privilege::EXAM);
    }
}
