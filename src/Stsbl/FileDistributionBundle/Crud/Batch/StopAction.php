<?php
// src/Stsbl/FileDistributionBundle/Crud/Batch/StopAction.php
namespace Stsbl\FileDistributionBundle\Crud\Batch;

use Doctrine\Common\Collections\ArrayCollection;
use IServ\CoreBundle\Service\Shell;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\CrudBundle\Entity\FlashMessageBag;
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
 * FileDistribution stop batch
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class StopAction extends AbstractFileDistributionAction
{
    /**
     * {@inheritodc}
     */
    public function execute(ArrayCollection $entities) 
    {
        if (is_null($this->shell)) {
            throw new \RuntimeException(sprintf('shell was not injected into %s, you need to set it via %s.', get_class($this), get_class($this),'::setShell()'));
        }
        
        /* @var $entities \Stsbl\FileDistributionBundle\Entity\FileDistribution[] */
        $bag = new FlashMessageBag();
        $user = $this->crud->getUser();
        
        foreach ($entities as $entity) {
            /* @var $entity \Stsbl\FileDistributionBundle\Entity\Host */
            if ($this->isAllowedToExecute($entity, $user)) {
                $delete = $this->crud->getFileDistributionForHost($entity);
                $this->crud->delete($delete);
                $bag->addMessage('success', __('Disabled file distribution for %s.', (string)$entity->getName()));
            } else {
                $bag->addMessage('error', __('You are not allowed to disable file distirbution for %s.', (string)$entity->getName()));
            }
        }
        
        $bag = $this->convertShellErrorOutput($bag);
        $this->shell->exec('sudo', ['/usr/lib/iserv/file_distribution_config']);
        
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
    public function getListIcon()
    {
        return 'pro-stop-sign';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getConfirmClass()
    {
        return 'danger';
    }

    /**
     * @param CrudInterface $fileDistribution
     * @param UserInterface $user
     */
    public function isAllowedToExecute(CrudInterface $object, UserInterface $user) 
    {
        return $this->crud->isAllowedToStop($object, $user);
    }
}
