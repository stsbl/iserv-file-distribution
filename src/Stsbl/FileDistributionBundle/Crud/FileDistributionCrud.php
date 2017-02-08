<?php
// src/Stsbl/FileDistributionBundle/Crud/FileDistributionCrud.php
namespace Stsbl\FileDistributionBundle\Crud;

use IServ\CoreBundle\Service\Shell;
use IServ\CrudBundle\Crud\AbstractCrud;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\CrudBundle\Mapper\ListMapper;
use IServ\CrudBundle\Mapper\ShowMapper;
use IServ\CrudBundle\Table\Filter;
use IServ\CrudBundle\Table\ListHandler;
use Stsbl\FileDistributionBundle\Crud\Batch\StopAction;
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
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->title = _('File distribution');
        $this->itemTitle = _('Device');
        $this->id = 'filedistribution';
        $this->routesNamePrefix = 'fd_';
        $this->options['help'] = 'https://it.stsbl.de/documentation/mods/stsbl-iserv-file-distribution';
        $this->options['sort'] = 'title';
        $this->templates['crud_index'] = 'StsblFileDistributionBundle:Crud:file_distribution_index.html.twig';
    }
    
    /**
     * {@inheritdoc}
     */
    protected function buildRoutes() 
    {
        parent::buildRoutes();
        
        $this->routes[self::ACTION_INDEX]['_controller'] = 'StsblFileDistributionBundle:FileDistribution:index';
        $this->routes[self::ACTION_SHOW]['_controller'] = 'StsblFileDistributionBundle:FileDistribution:show';
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
        /* @var $object \Stsbl\FileDistributionBundle\Entity\FileDistribution */
        if ($user !== $object->getUser()) {
            return false;
        }
        
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function configureListFields(ListMapper $listMapper) 
    {
        $listMapper
            ->addIdentifier('hostname', null, [
                'label' => _('Host'),
                'responsive' => 'all'
            ])
            ->add('title', null, [
                'label' => _('File distribution'),
                'group' => true,
                'sortOrder' => [3, 1]
            ])
            ->add('plainTitle', null, [
                'label' => _('File distribution')
            ])
            ->add('user', null, [
                'label' => _('Owner')
            ])
        ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureShowFields(ShowMapper $showMapper) 
    {
        $showMapper
            ->add('hostname', null, [
                'label' => _('Host')
            ])
            ->add('title', null, [
                'label' => _('Title'),
                'group' => true
            ])
            ->add('user', null, [
                'label' => _('Owner')
            ])
        ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureListFilter(ListHandler $listHandler)
    {
        $qb = $this->getObjectManager()->createQueryBuilder($this->class);
                   
        $qb->select('p')
            ->from('StsblFileDistributionBundle:FileDistribution', 'p')
        ;
        
        $allFilter = new Filter\ListExpressionFilter(_('All'), $qb->expr()->exists($qb));
        $allFilter    
            ->setName('all_titles')
        ;
        
        $listHandler->addListFilter($allFilter);
        
        $titles = [];
        
        /* @var $r \Stsbl\FileDistributionBundle\Entity\FileDistribution */
        foreach ($qb->getQuery()->getResult() as $r) {
            $titles[$r->getTitle()] = ['plain' => $r->getPlainTitle(), 'display' => $r->getTitle(), 'user' => $r->getUser()];
        }
        
        foreach ($titles as $title) {
            $titleFilter = new Filter\ListExpressionFilter($title['display'], 'parent.user = :user and parent.title = :title');
            $titleFilter
                ->setName('title_'. strtolower($title['plain'].'_'.$title['user']->getUsername()))
                ->setParameters(['user' => $title['user'], 'title' => $title['plain']]);
            ;
            
            $listHandler->addListFilter($titleFilter);
        }
        
        $listHandler->setDefaultFilter('all_titles');
    }
    
    /**
     * {@inheritdoc}
     */
    public function loadBatchActions()
    {
        $res = parent::loadBatchActions();
        
        $stopAction = new StopAction($this);
        $stopAction->setShell($this->shell);
        
        $res->add($stopAction);
        
        return $res;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getIndexActions() 
    {
        $links = parent::getIndexActions();
        
        $links['enable'] = [$this->getRouter()->generate('fd_filedistribution_enable'), _('Enable'), 'pro-folder-plus'];
        
        return $links;
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
