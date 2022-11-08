<?php

declare(strict_types=1);

namespace Stsbl\FileDistributionBundle\Crud\Batch;

use Doctrine\Common\Collections\ArrayCollection;
use IServ\CrudBundle\Entity\FlashMessageBag;
use IServ\HostBundle\Entity\Host;
use Stsbl\FileDistributionBundle\FileDistribution\FileDistribution;
use Stsbl\FileDistributionBundle\Security\Privilege;

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
 * FileDistribution soundunlock batch
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
final class SoundUnlockAction extends AbstractFileDistributionAction
{
    use Traits\NoopFormTrait;

    protected $privileges = [Privilege::USE_FD, Privilege::BOOT];

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

        foreach ($hosts as $entity) {
            $messages[] = $this->createFlashMessage('success', __('Enabled sound on %s.', (string)$entity->getName()));
        }

        $bag = $this->getFileDistributionManager()->soundUnlock($hosts);
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
        return 'soundunlock';
    }
 /**
     * {@inheritodc}
     */
    public function getLabel(): string
    {
        return _('Enable');
    }

    /**
     * {@inheritdoc}
     */
    public function getTooltip(): string
    {
        return _('Enable sound on the selected hosts.');
    }

    /**
     * {@inheritdoc}
     */
    public function getListIcon(): string
    {
        return 'pro-volume-up';
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
        return _('Sound');
    }
}
