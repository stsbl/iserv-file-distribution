<?php
// src/Stsbl/FileDistributionBundle/Crud/Batch/Message.php
namespace Stsbl\FileDistributionBundle\Crud\Batch;

use Doctrine\Common\Collections\ArrayCollection;
use IServ\HostBundle\Security\Privilege;

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
 * file distribution rpc message batch
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class MessageAction extends AbstractFileDistributionAction 
{
    protected $privileges = Privilege::BOOT;
    
    /**
     * @var string
     */
    private $message;
    
    /**
     * Set message
     * 
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }
    
    /**
     * {@inheritdoc}
     */
    public function execute(ArrayCollection $entities) 
    {
        if ($this->message === null) {
            throw new \InvalidArgumentException('No message set, you need to set it via setMessage()!');
        }
        
        /* @var $entities array<\Stsbl\FileDistributionBundle\Entity\Host> */
        $messages = [];
        
        foreach ($entities as $entity) {
            $messages[] = $this->createFlashMessage('success', __('Sent message to %s.', (string)$entity->getName()));
        }
        
        $bag = $this->getFileDistributionManager()->msg($entities, $this->message);
        // add messsages created during work
        foreach ($messages as $message) {
            $bag->add($message);
        }        
        
        return $bag;        
    }

    /**
     * {@inheritdoc}
     */
    public function getName() 
    {
        return 'message';
    }
    
    /**
     * {@inheritodc}
     */
    public function getLabel() 
    {
        return _('Send message');
    }
    
    /**
     * {@inheritdoc}
     */
    public function getTooltip() 
    {
        return _('Send message to the selected hosts.');
    }

    /**
     * {@inheritdoc}
     */
    public function getListIcon()
    {
        return 'pro-message-flag';
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
        return _('Communication');
    }
}
