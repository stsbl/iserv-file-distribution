<?php
// src/Stsbl/FileDistributionBundle/Admin/IncludeRoomAdmin.php
namespace Stsbl\FileDistributionBundle\Admin;

use IServ\AdminBundle\Admin\AbstractAdmin;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\CrudBundle\Mapper\AbstractBaseMapper;
use IServ\CrudBundle\Mapper\FormMapper;
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
 * IncludeRoom Admin
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class IncludeRoomAdmin extends AbstractAdmin
{
    /**
     * {@inheritdoc}
     */
    protected function configure() 
    {
        parent::configure();
        
        $this->title = _('Rooms');
        $this->itemTitle = _('Room');
        $this->id = 'filedistribution_rooms';
        $this->options['help'] = 'https://it.stsbl.de/documentation/mods/stsbl-iserv-file-distribution';
        $this->templates['crud_index'] = 'StsblFileDistributionBundle:Crud:file_distribution_rooms_index.html.twig';
    }
    
    /**
     * {@inheritdoc}
     */
    public function isAllowedToView(CrudInterface $object = null, UserInterface $user = null) 
    {
        // disable show action, it is useless here
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isAllowedToAdd(UserInterface $user = null) 
    {
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function isAllowedToEdit(CrudInterface $object = null, UserInterface $user = null)
    {
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function isAllowedToDelete(CrudInterface $object = null, UserInterface $user = null) 
    {
        return $this->isAllowedToEdit($object, $user);
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureFields(AbstractBaseMapper $mapper) 
    {
        $options = [
            'label' => _('Room'),
        ];
        
        if ($mapper instanceof FormMapper) {
            /* @var $qb \Doctrine\ORM\QueryBuilder */
            $qb = $this->getObjectManager()->createQueryBuilder($this->class);
            $subQb = clone $qb;
            
            $subQb
                ->select('fr')
                ->from('StsblFileDistributionBundle:FileDistributionRoom', 'fr')
                ->where('fr.room = r.name')
            ;        
            
            $choices = $qb
                ->select('r')
                ->from('IServRoomBundle:Room', 'r')
                ->where($qb->expr()->not($qb->expr()->exists($subQb)))
                ->getQuery()
                ->getResult()
            ;
            $options['choices'] = $choices;
        }
        $mapper->add('room', null, $options);
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getRoutePattern($action, $id, $entityBased = true)
    {
        if ('index' === $action) {
            return sprintf('/%s%s', $this->routesPrefix, 'filedistribution/rooms');
        } else if ('batch' === $action || 'batch/confirm' === $action) {
            return sprintf('/%s%s/%s', $this->routesPrefix, 'filedistribution/rooms', $action);
        } else {
            return sprintf('/%s%s/%s', $this->routesPrefix, 'filedistribution/room', $action);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    protected function buildRoutes() 
    {
        parent::buildRoutes();
        
        $this->routes[self::ACTION_INDEX]['_controller'] = 'StsblFileDistributionBundle:FileDistribution:roomIndex';
    }
    
    /**
     * {@inheritdoc}
     */
    public function prepareBreadcrumbs() 
    {
        $ret = parent::prepareBreadcrumbs();
        $ret[_('File distribution')] = $this->router->generate('fd_filedistribution_index');
        return $ret;
    }
}
