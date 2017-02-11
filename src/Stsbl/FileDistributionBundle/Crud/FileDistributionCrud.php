<?php
// src/Stsbl/FileDistributionBundle/Crud/FileDistributionCrud.php
namespace Stsbl\FileDistributionBundle\Crud;

use Doctrine\ORM\EntityManager;
use IServ\CoreBundle\Entity\Specification\PropertyMatchSpecification;
use IServ\CoreBundle\Security\Core\SecurityHandler;
use IServ\CoreBundle\Service\Shell;
use IServ\CrudBundle\Crud\AbstractCrud;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\CrudBundle\Mapper\ListMapper;
use IServ\CrudBundle\Mapper\ShowMapper;
use Stsbl\FileDistributionBundle\Crud\Batch\EnableAction;
use Stsbl\FileDistributionBundle\Crud\Batch\StopAction;
use Stsbl\FileDistributionBundle\Security\Privilege;
use Symfony\Component\HttpFoundation\Session\Session;
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
 * FileDistribution Crud
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class FileDistributionCrud extends AbstractCrud
{
    /**
     * @var Shell
     */
    private $shell;
    
    /**
     * @var EntityManager
     */
    private $em;
    
    /**
     * @var SecurityHandler
     */
    private $securityHandler;
    
    /**
     * @var Session
     */
    private $session;
    
    /* SETTERS */
    
    /**
     * Set shell
     * 
     * @param Shell $shell
     */
    public function setShell(Shell $shell)
    {
        $this->shell = $shell;
    }
    
    /**
     * Set entity manager
     * 
     * @param EntityManager $em
     */
    public function setEntityManager(EntityManager $em)
    {
        $this->em = $em;
    }
    
    /**
     * Set security handler
     * 
     * @param SecurityHandler $securityHandler
     */
    public function setSecurityHandler(SecurityHandler $securityHandler)
    {
        $this->securityHandler = $securityHandler;
    }
    
    /**
     * Set session
     * 
     * @param Session $session
     */
    public function setSession(Session $session)
    {
        $this->session = $session;
    }
    
    /* GETTERS */
    
    /**
     * Get security handler
     * 
     * @return SecurityHandler|null
     */
    public function getSecurityHandler()
    {
        return $this->securityHandler;
    }
    
    /**
     * Get session
     * 
     * @return Session|null
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        
        $this->title = _('File distribution');
        $this->itemTitle = _('Device');
        $this->id = 'filedistribution';
        $this->routesNamePrefix = 'fd_';
        $this->options['help'] = 'https://it.stsbl.de/documentation/mods/stsbl-iserv-file-distribution';
        $this->options['sort'] = 'room';
        $this->templates['crud_index'] = 'StsblFileDistributionBundle:Crud:file_distribution_index.html.twig';
        $this->templates['crud_batch_confirm'] = 'StsblFileDistributionBundle:Crud:file_distribution_batch_confirm.html.twig';
    }
    
    /**
     * {@inheritdoc}
     */
    protected function buildRoutes() 
    {
        parent::buildRoutes();
        
        $this->routes[self::ACTION_INDEX]['_controller'] = 'StsblFileDistributionBundle:FileDistribution:index';
        $this->routes[self::ACTION_SHOW]['_controller'] = 'StsblFileDistributionBundle:FileDistribution:show';
        $this->routes['batch_confirm']['_controller'] = 'StsblFileDistributionBundle:FileDistribution:confirmBatch';
        $this->routes['batch']['_controller'] = 'StsblFileDistributionBundle:FileDistribution:batch';
    }

    /**
     * {@inheritdoc}
     */
    public function isAllowedToAdd(UserInterface $user = null) 
    {
        return false;
    }
    
    /**
     * {@inheritdoc}
     */
    public function isAllowedToEdit(CrudInterface $object = null, UserInterface $user = null)
    {
        return false;
    }
    
    /**
     * {@inheritdoc}
     */
    public function isAllowedToDelete(CrudInterface $object = null, UserInterface $user = null) 
    {
        return $this->isAllowedToEdit($object, $user);
    }
    
    /** 
     * Checks if user is allowed to stop a file distribution
     * 
     * @param CrudInterface $object
     * @param UserInterface $user
     */
    public function isAllowedToStop(CrudInterface $object, UserInterface $user = null) 
    {
        $fileDistribution = $this->getFileDistributionForHost($object);
        
        if ($fileDistribution === null) {
            return false;
        }
        
        if ($this->getUser() !== $fileDistribution->getUser()) {
            return false;
        }
        
        return true;
    }
    
    /** 
     * Checks if user is allowed to enable a file distribution
     * 
     * @param CrudInterface $object
     * @param UserInterface $user
     */
    public function isAllowedToEnable(CrudInterface $object, UserInterface $user = null)
    {
        $fileDistribution = $this->getFileDistributionForHost($object);
        
        if($fileDistribution === null) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function getFilterSpecification()
    {
        // Only show controllable hosts
        return new PropertyMatchSpecification('controllable', true);
    }
    
    /**
     * @param CrudInterface $object
     * @return \Stsbl\FileDistributionBundle\Entity\FileDistribution|null
     */
    public function getFileDistributionForHost(CrudInterface $object)
    {
        /* @var $object \Stsbl\FileDistributionBundle\Entity\Host */
        /* @var $er \Doctrine\ORM\EntityRepository */
        /* @var $fileDistribution \Stsbl\FileDistributionBundle\Entity\FileDistribution */
        $er = $this->getObjectManager()->getRepository('StsblFileDistributionBundle:FileDistribution');
        try {
            $fileDistribution = $er->findOneBy(['ip' => $object->getIp()]);
            
            return $fileDistribution;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureListFields(ListMapper $listMapper) 
    {
        $listMapper
            ->addIdentifier('name', null, [
                'label' => _('Name'),
                'responsive' => 'all',
                'sortType' => 'natural',
                'template' => 'IServHostBundle:Crud:list_field_name.html.twig',
            ])
            ->add('fileDistribution', null, [
                'label' => _('File distribution'),
                'group' => true,
                'sortOrder' => [3, 1],
                'sortType' => 'natural',
                'template' => 'StsblFileDistributionBundle:List:field_filedistribution.html.twig',
            ])
            ->add('fileDistributionOwner', null, [
                'label' => _('File distribution owner'),
            ])
            ->add('room', null, [
                'label' => _('Room'),
                'group' => true,
                'sortOrder' => [3, 1],
                'sortType' => 'natural',
            ])
            ->add('internet', 'boolean', ['label' => _p('host', 'Internet')])
            ->add('sambaUser', null, ['label' => _('User')])
        ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureShowFields(ShowMapper $showMapper) 
    {
        $showMapper
            ->add('name', null, [
                'label' => _('Name')
            ])
            ->add('fileDistribution', null, [
                'label' => _('File distribution'),
                'template' => 'StsblFileDistributionBundle:Show:field_filedistribution.html.twig',
            ])
            ->add('fileDistributionOwner', null, [
                'label' => _('Owner')
            ])
            ->add('room', null, [
                'label' => _('Room'),
            ])
            ->add('internet', 'boolean', ['label' => _p('host', 'Internet')])
            ->add('sambaUser', null, ['label' => _('User')])
        ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function loadBatchActions()
    {
        parent::loadBatchActions();
        
        $enableAction = new EnableAction($this);
        $enableAction->setShell($this->shell);
        $enableAction->setEntityManager($this->em);
        
        $this->batchActions->add($enableAction);
        
        $stopAction = new StopAction($this);
        $stopAction->setShell($this->shell);
        
        $this->batchActions->add($stopAction);
        
        return $this->batchActions;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getRoutePattern($action, $id, $entityBased = true) 
    {
        if ('index' === $action) {
            return sprintf('%s', $this->id);
        } else {
            return parent::getRoutePattern($action, $id, $entityBased);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function isAuthorized() 
    {
        return $this->isGranted(Privilege::USE_FD);
    }
}
