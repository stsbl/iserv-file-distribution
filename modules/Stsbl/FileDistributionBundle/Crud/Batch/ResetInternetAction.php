<?php

declare(strict_types=1);

namespace Stsbl\FileDistributionBundle\Crud\Batch;

use Doctrine\Common\Collections\ArrayCollection;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\CrudBundle\Entity\FlashMessageBag;
use IServ\HostBundle\Entity\Host;
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
 * FileDistribution reset internet batch
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensourc.org/licenses/MIT>
 */
final class ResetInternetAction extends AbstractFileDistributionAction
{
    use Traits\NoopFormTrait;

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
        /* @var $entities Host[] */
        $messages = [];

        $internet = $this->crud->internet();

        if (null === $internet) {
            $bag = new FlashMessageBag();
            $bag->addError(_('The internet is not available.'));

            return $bag;
        }

        $internet->reset(new ArrayCollection($hosts));

        foreach ($hosts as $entity) {
            $messages[] = $this->createFlashMessage('success', __('Reset internet access for %s.', (string)$entity));
        }

        $bag = new FlashMessageBag();
        // add messages created during work
        foreach ($messages as $message) {
            $bag->add($message);
        }

        return $bag;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'inetreset';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return _('Reset');
    }

    /**
     * {@inheritdoc}
     */
    public function getTooltip(): string
    {
        return _('Reset internet access of the selected hosts to the default value.');
    }

    /**
     * {@inheritdoc}
     */
    public function getListIcon(): string
    {
        return 'repeat';
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
     * {@inheritdoc}
     */
    public function isAllowedToExecute(CrudInterface $object, UserInterface $user): bool
    {
        return $this->crud->authorizationChecker()->isGranted(Privilege::INET_ROOMS);
    }
}
