<?php
// src/Stsbl/FileDistributionBundle/Crud/Batch/EnableAction.php
namespace Stsbl\FileDistributionBundle\Crud\Batch;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use IServ\CoreBundle\Service\Shell;
use IServ\CoreBundle\Util\Sudo;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\CrudBundle\Entity\FlashMessageBag;
use Stsbl\FileDistributionBundle\Entity\FileDistribution;
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
    /**
     * @var string
     */
    private $title; 
    
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
     * {@inheritodc}
     */
    public function execute(ArrayCollection $entities) 
    {      
        /* @var $entities \Stsbl\FileDistributionBundle\Entity\FileDistribution[] */
        $bag = new FlashMessageBag();
        $user = $this->crud->getUser();
        $this->rpc->setTitle($this->title);
        
        foreach ($entities as $entity) {
            if (empty($this->title)) {
               $bag->addMessage('error', _('Title should not be empty!'));
               return $bag;
            } else if ($this->isAllowedToExecute($entity, $user)) {
                $this->rpc->addHost($entity);
                
                $bag->addMessage('success', __('Enabled file distribution for %s.', (string)$entity->getName()));
            } else {
                $bag->addMessage('error', __('You are not allowed to enable file distribution for %s.', (string)$entity->getName()));
            }
        }
        
        $this->rpc->enable();
        $bag = $this->handleShellErrorOutput($bag, $this->rpc->getErrorOutput());
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
        return _('Enable');
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
        return 'pro-folder-plus';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getConfirmClass()
    {
        return 'primary';
    }

    /**
     * @param CrudInterface $fileDistribution
     * @param UserInterface $user
     */
    public function isAllowedToExecute(CrudInterface $object, UserInterface $user) 
    {
        return $this->crud->isAllowedToEnable($object, $user);
    }
}
