<?php
// src/Stsbl/FileDistributionBundle/EventListener/MenuListener.php
namespace Stsbl\FileDistributionBundle\EventListener;

use IServ\AdminBundle\EventListener\AdminMenuListenerInterface;
use IServ\CoreBundle\Event\MenuEvent;
use IServ\CoreBundle\EventListener\MainMenuListenerInterface;
use IServ\CoreBundle\Menu\MenuBuilder;
use IServ\HostBundle\Security\Privilege as HostPrivilege;
use Stsbl\FileDistributionBundle\Security\Privilege;

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
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class MenuListener implements MainMenuListenerInterface, AdminMenuListenerInterface
{
    /**
     * {@inheritdoc}
     */
    public function onBuildMainMenu(MenuEvent $event) 
    {
        if ($event->getAuthorizationChecker()->isGranted(Privilege::USE_FD) && $event->getAuthorizationChecker()->isGranted(HostPrivilege::BOOT)) {
            $menu = $event->getMenu(MenuBuilder::GROUP_EDUCATION);
            
            $item = $menu->addChild('file_distribution', [
                'route' => 'fd_filedistribution_index',
                'label' => _('File distribution'),
            ]);
            
            $item
                ->setExtra('icon', 'box-share')
                ->setExtra('icon_style', 'fugue');
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function onBuildAdminMenu(MenuEvent $event) 
    {
        $menu = $event->getMenu('modules');
        
        $item = $menu->addChild('file_distribution', [
            'route' => 'admin_filedistribution_rooms_index',
            'label' => _('File distribution'),
        ]);
            
        $item
            ->setExtra('icon', 'box-share')
            ->setExtra('icon_style', 'fugue');
    }

}
