<?php
// src/Stsbl/FileDistributionBundle/Crud/Batch/EnableAction.php
namespace Stsbl\FileDistributionBundle\Crud\Batch;

use Doctrine\Common\Collections\ArrayCollection;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\CrudBundle\Entity\FlashMessageBag;
use IServ\HostBundle\Security\Privilege as HostPrivilege;
use Stsbl\FileDistributionBundle\Security\Privilege;
use Symfony\Component\Security\Core\User\UserInterface;

/*
 * The MIT License
 *
 * Copyright 2017 Felix Jacobi.
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
 * FileDistribution enable batch
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class EnableAction extends AbstractFileDistributionAction 
{
    protected $privileges = [Privilege::USE_FD, HostPrivilege::BOOT];
    
    /**
     * @var string
     */
    private $title;
    
    /**
     * @var boolean
     */
    private $isolation;
    
    /**
     * Set file distribution title
     * 
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }
    
    /**
     * Set file distribution isolation
     * 
     * @param boolean $isolation
     */
    public function setIsolation($isolation)
    {
        $this->isolation = $isolation;
    }
    
    /**
     * {@inheritodc}
     */
    public function execute(ArrayCollection $entities) 
    {      
        /* @var $entities \Stsbl\FileDistributionBundle\Entity\FileDistribution[] */
        $user = $this->crud->getUser();
        $messages = [];
        $error = false;
        
        if ($this->isolation === null) {
            throw new \InvalidArgumentException('Parameter isolation is not set!');
        }
        
        foreach ($entities as $key => $entity) {
            $skipOwnHost = false;
            
            if (empty($this->title)) {
               $messages[] = $this->createFlashMessage('error', _('Title should not be empty!'));
               $error = true;
               break;
            } 
            
            if ($entity->getIp() === $this->crud->getRequest()->getClientIp() && count($entities) > 1) {
                $messages[] = $this->createFlashMessage('warning', _('Skipping own host!'));
                unset($entities[$key]);
                $skipOwnHost = true;
            }
            
            if (!$this->isAllowedToExecute($entity, $user)) {
                // remove unallowed hosts
                $messages[] = $this->createFlashMessage('error', __('You are not allowed to enable file distribution for %s.', (string)$entity->getName()));
                unset($entities[$key]);
            } else if (!$skipOwnHost) {
                $messages[] = $this->createFlashMessage('success', __('Enabled file distribution for %s.', (string)$entity->getName()));
            }
        }
        
        // only execute rpc, if we have no errors and at least one entity
        if (!$error && count($entities) > 0) {
            $bag = $this->getFileDistributionManager()->enableFileDistribution($entities, $this->title, $this->isolation);
        } else {
            $bag = new FlashMessageBag();
        }
        // add messsages created during work
        foreach ($messages as $message) {
            $bag->add($message);
        }
        
        $this->session->set('fd_title', $this->title);
        
        return $bag;
    }

    /**
     * {@inheritodc}
     */
    public function getName()
    {
        return 'enable';
    }
    
    /**
     * {@inheritodc}
     */
    public function getLabel() 
    {
        return _('Start');
    }
    
    /**
     * {@inheritdoc}
     */
    public function getTooltip() 
    {
        return _('Start a file distribution for the selected hosts.');
    }

    /**
     * {@inheritdoc}
     */
    public function getListIcon()
    {
        return 'pro-disk-open';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getConfirmClass()
    {
        return 'primary';
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
     */
    public function isAllowedToExecute(CrudInterface $object, UserInterface $user) 
    {
        return $this->crud->isAllowedToEnable($object, $user);
    }
}
