<?php
// src/Stsbl/FileDistributionBundle/Crud/Batch/StopAction.php
namespace Stsbl\FileDistributionBundle\Crud\Batch;

use Doctrine\Common\Collections\ArrayCollection;
use IServ\CrudBundle\Crud\Batch\GroupableBatchActionInterface;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\HostBundle\Security\Privilege as HostPrivilege;
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
    
    protected $privileges = [Privilege::USE_FD, HostPrivilege::BOOT];
    
    /**
     * {@inheritodc}
     */
    public function execute(ArrayCollection $entities) 
    { 
        /* @var $entities \Stsbl\FileDistributionBundle\Entity\FileDistribution[] */
        $user = $this->crud->getUser();
        $messages = [];
        
        foreach ($entities as $key => $entity) {
            /* @var $entity \Stsbl\FileDistributionBundle\Entity\Host */
            if (!$this->isAllowedToExecute($entity, $user)) {
                // remove unallowed hosts
                $messages[] = $this->createFlashMessage('error', __('You are not allowed to disable file distribution for %s.', (string)$entity->getName()));
                unset($entities[$key]);
            } else {
                $messages[] = $this->createFlashMessage('success', __('Disabled file distribution for %s.', (string)$entity->getName()));
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
    public function getName()
    {
        return 'stop';
    }
    
    /**
     * {@inheritodc}
     */
    public function getLabel() 
    {
        return _('Stop');
    }
    
    /**
     * {@inheritdoc}
     */
    public function getTooltip() 
    {
        return _('Stop the running file distribution for the selected hosts.');
    }

    /**
     * {@inheritdoc}
     */
    public function getListIcon()
    {
        return 'pro-disk-save';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getConfirmClass()
    {
        return 'danger';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getGroup()
    {
        return _('File distribution');
    }
    /**
     * @param CrudInterface $object
     * @param UserInterface $user
     * @return boolean
     */
    public function isAllowedToExecute(CrudInterface $object, UserInterface $user) 
    {
        return $this->crud->isAllowedToStop($object, $user);
    }
}
