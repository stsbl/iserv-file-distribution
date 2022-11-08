<?php

// src/Stsbl/FileDistributionBundle/Crud/Batch/GrantInternetAction.php

namespace Stsbl\FileDistributionBundle\Crud\Batch;

use Doctrine\Common\Collections\ArrayCollection;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\CrudBundle\Entity\FlashMessageBag;
use Stsbl\FileDistributionBundle\Entity\FileDistribution;
use Stsbl\FileDistributionBundle\Security\Privilege;
use Symfony\Component\Security\Core\User\UserInterface;

/*
 * The MIT License
 *
 * Copyright 2021 Felix Jacobi.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * FileDistribution grant internet batch
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensourc.org/licenses/MIT>
 */
final class GrantInternetAction extends AbstractFileDistributionAction
{
    use Traits\InternetTimeFormTrait;

    /**
     * {@inheritDoc}
     */
    protected $privileges = Privilege::INET_ROOMS;

    /**
     * {@inheritdoc}
     */
    public function execute(ArrayCollection $entities): FlashMessageBag
    {
        /** @var FileDistribution[] $entities */
        $hosts = [];

        foreach ($entities as $entity) {
            $hosts[] = $entity->getHost();
        }

        $messages = [];

        if ($this->until === null) {
            throw new \InvalidArgumentException('until is not defined!');
        }

        if ($this->until === 'today') {
            $overrideUntil = new \DateTime('tomorrow midnight');
        } else {
            $overrideUntil = new \DateTime(sprintf('now + %d minutes', (int)$this->until));
        }


        $internet = $this->crud->internet();

        if (null === $internet) {
            $bag = new FlashMessageBag();
            $bag->addError(_('The internet is not available.'));

            return $bag;
        }

        $internet->grant(new ArrayCollection($hosts), $overrideUntil);

        foreach ($hosts as $entity) {
            $messages[] = $this->createFlashMessage('success', __('Granted internet access for %s.', (string)$entity));
        }

        $bag = new FlashMessageBag();
        // add messages created during work
        foreach ($messages as $message) {
            $bag->add($message);
        }

        return $bag;
    }

    /**
     * {@inheritodc}
     */
    public function getName(): string
    {
        return 'inetgrant';
    }

    /**
     * {@inheritodc}
     */
    public function getLabel(): string
    {
        return _('Grant');
    }

    /**
     * {@inheritdoc}
     */
    public function getTooltip(): string
    {
        return _('Grant internet access to the selected hosts.');
    }

    /**
     * {@inheritdoc}
     */
    public function getListIcon(): string
    {
        return 'pro-ok-sign';
    }

    /**
     * {@inheritdoc}
     */
    public function getConfirmClass(): string
    {
        return 'primary';
    }

    /**
     * {@inheritdoc}
     */
    public function getGroup(): string
    {
        return _('Internet');
    }

    /**
     * {@inheritDoc}
     */
    public function isAllowedToExecute(CrudInterface $object, UserInterface $user): bool
    {
        return $this->crud->authorizationChecker()->isGranted(Privilege::INET_ROOMS);
    }
}
