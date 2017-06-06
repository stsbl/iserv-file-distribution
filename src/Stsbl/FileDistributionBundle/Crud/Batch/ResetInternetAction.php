<?php
// src/Stsbl/FileDistributionBundle/Crud/Batch/GrantInternetAction.php
namespace Stsbl\FileDistributionBundle\Crud\Batch;

use Doctrine\Common\Collections\ArrayCollection;
use IServ\CrudBundle\Entity\CrudInterface;
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
 * FileDistribution reset internet batch
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensourc.org/licenses/MIT>
 */
class ResetInternetAction extends AbstractFileDistributionAction
{
    use Traits\NoopFormTrait;
    
    protected $privileges = Privilege::INET_ROOMS;
    
    /**
     * {@inheritdoc}
     */
    public function execute(ArrayCollection $entities)
    {
        /* @var $entities \Stsbl\FileDistributionBundle\Entity\Host[] */
        $messages = [];
        
        foreach ($entities as $e) {
            $e->setOverrideRoute(null);
            $e->setOverrideUntil(null);
            $e->setOverrideBy(null);
            
            $this->crud->getEntityManager()->persist($e);
            $this->crud->getEntityManager()->flush();
            
            $messages[] = $this->createFlashMessage('success', __('Reset internet access for %s.', (string)$e));
        }
        
        $bag = $this->getFileDistributionManager()->activation();
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
        return 'inetreset';
    }
    
    /**
     * {@inheritodc}
     */
    public function getLabel() 
    {
        return _('Reset');
    }
    
    /**
     * {@inheritdoc}
     */
    public function getTooltip() 
    {
        return _('Reset internet access of the selected hosts to the default value.');
    }

    /**
     * {@inheritdoc}
     */
    public function getListIcon()
    {
        return 'pro-rewind';
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
        return _('Internet');
    }

    /**
     * @param CrudInterface $object
     * @param UserInterface $user
     */
    public function isAllowedToExecute(CrudInterface $object, UserInterface $user) 
    {
        return $this->crud->getAuthorizationChecker()->isGranted(Privilege::INET_ROOMS);
    }
}
