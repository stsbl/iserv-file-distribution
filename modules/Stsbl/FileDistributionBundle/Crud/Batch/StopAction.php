<?php
declare(strict_types=1);

namespace Stsbl\FileDistributionBundle\Crud\Batch;

use Doctrine\Common\Collections\ArrayCollection;
use IServ\CrudBundle\Crud\Batch\GroupableBatchActionInterface;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\CrudBundle\Entity\FlashMessageBag;
use Stsbl\FileDistributionBundle\Security\Privilege;
use Symfony\Component\Security\Core\User\UserInterface;

/*
 * The MIT License
 *
 * Copyright 2018 Felix Jacobi.
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
 * FileDistribution stop batch
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class StopAction extends AbstractFileDistributionAction implements GroupableBatchActionInterface
{
    use Traits\NoopFormTrait;
    
    protected $privileges = [Privilege::USE_FD, Privilege::BOOT];
    
    /**
     * {@inheritodc}
     */
    public function execute(ArrayCollection $entities): FlashMessageBag
    {
        /* @var $entities \Stsbl\FileDistributionBundle\Entity\FileDistribution[] */
        $user = $this->crud->getUser();
        $messages = [];
        
        foreach ($entities as $key => $entity) {
            /* @var $entity \Stsbl\FileDistributionBundle\Entity\Host */
            if (!$this->isAllowedToExecute($entity, $user)) {
                // remove unallowed hosts
                $messages[] = $this->createFlashMessage(
                    'error',
                    __('You are not allowed to disable file distribution for %s.', $entity->getName())
                );
                unset($entities[$key]);
            } else {
                $messages[] = $this->createFlashMessage(
                    'success',
                    __('Disabled file distribution for %s.', $entity->getName())
                );
            }
        }

        $bag = $this->getFileDistributionManager()->disableFileDistribution($entities);
        // add messsages created during work
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
        return 'stop';
    }
    
    /**
     * {@inheritodc}
     */
    public function getLabel(): string
    {
        return _('Stop');
    }
    
    /**
     * {@inheritdoc}
     */
    public function getTooltip(): string
    {
        return _('Stop the running file distribution for the selected hosts.');
    }

    /**
     * {@inheritdoc}
     */
    public function getListIcon(): string
    {
        return 'pro-disk-save';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getConfirmClass(): string
    {
        return 'danger';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getGroup(): string
    {
        return _('File distribution');
    }
    /**
     * {@inheritDoc}
     */
    public function isAllowedToExecute(CrudInterface $object, UserInterface $user): bool
    {
        return $this->crud->isAllowedToStop($object, $user);
    }
}
