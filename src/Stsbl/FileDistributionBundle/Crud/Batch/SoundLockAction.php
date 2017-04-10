<?php
// src/Stsbl/FileDistributionBundle/Crud/Batch/SoundLockAction.php
namespace Stsbl\FileDistributionBundle\Crud\Batch;

use Doctrine\Common\Collections\ArrayCollection;
use IServ\CrudBundle\Entity\FlashMessageBag;

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
 * FileDistribution soundlock batch
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class SoundLockAction extends AbstractFileDistributionAction
{
    /**
     * {@inheritdoc}
     */
    public function execute(ArrayCollection $entities) 
    {
        /* @var $entities array<\Stsbl\FileDistributionBundle\Entity\Host> */
        $bag = new FlashMessageBag();
        
        foreach ($entities as $entity) {
            $this->rpc->addHost($entity);
            
            $bag->addMessage('success', __('Disabled sound on %s.', (string)$entity->getName()));
        }
        
        $this->rpc->soundLock();
        $bag = $this->handleShellErrorOutput($bag, $this->rpc->getErrorOutput());
        return $bag;
    }

    /**
     * {@inheritdoc}
     */
    public function getName() 
    {
        return 'soundlock';
    }
 /**
     * {@inheritodc}
     */
    public function getLabel() 
    {
        return _('Disable sound');
    }
    
    /**
     * {@inheritdoc}
     */
    public function getTooltip() 
    {
        return _('Disable sound on the selected hosts.');
    }

    /**
     * {@inheritdoc}
     */
    public function getListIcon()
    {
        return 'pro-mute';
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
    public function requiresConfirmation() 
    {
        return false;
    }
}
