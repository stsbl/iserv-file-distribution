<?php

declare(strict_types=1);

namespace Stsbl\FileDistributionBundle\Admin;

use Doctrine\ORM\EntityRepository;
use IServ\AdminBundle\Admin\AdminServiceCrud;
use IServ\CoreBundle\Service\Logger;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\CrudBundle\Mapper\AbstractBaseMapper;
use IServ\CrudBundle\Mapper\FormMapper;
use IServ\CrudBundle\Routing\RoutingDefinition;
use Stsbl\FileDistributionBundle\Controller\FileDistributionController;
use Stsbl\FileDistributionBundle\Entity\FileDistributionRoom;
use Stsbl\FileDistributionBundle\Security\Privilege;
use Symfony\Component\Security\Core\User\UserInterface;

/*
 * The MIT License
 *
 * Copyright 2021 Felix Jacobi.
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
final class IncludeRoomAdmin extends AdminServiceCrud
{
    /**
     * {@inheritDoc}
     */
    protected static $entityClass = FileDistributionRoom::class;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->title = _('Rooms');
        $this->itemTitle = _('Room');
        $this->id = 'filedistribution_rooms';
        $this->options['help'] = 'https://it.stsbl.de/documentation/mods/stsbl-iserv-file-distribution';
        $this->templates['crud_index'] = '@StsblFileDistribution/Crud/file_distribution_rooms_index.html.twig';
    }

    /**
     * {@inheritDoc}
     */
    public function isAllowedTo(string $action, UserInterface $user, CrudInterface $object = null): bool
    {
        return self::ACTION_ADD === $action;
    }

    /**
     * {@inheritdoc}
     */
    public function configureFields(AbstractBaseMapper $mapper): void
    {
        $options = [
            'label' => _('Room'),
        ];

        if ($mapper instanceof FormMapper) {
            $options['query_builder'] = static function (EntityRepository $er) {
                $subQb = $er->createQueryBuilder('fr');

                $subQb
                    ->resetDqlParts()
                    ->select('fr')
                    ->from('StsblFileDistributionBundle:FileDistributionRoom', 'fr')
                    ->where($subQb->expr()->eq('fr.room', 'r.id'))
                ;

                return $er->createQueryBuilder('r')
                    ->where($subQb->expr()->not($subQb->expr()->exists($subQb)))
                    ->orderBy('r.name', 'ASC')
                ;
            };
        }

        $mapper->add('room', null, $options);
    }

    /**
     * {@inheritDoc}
     */
    public static function defineRoutes(): RoutingDefinition
    {
        return parent::createRoutes('rooms', 'room')
            ->useControllerForAction(self::ACTION_INDEX, FileDistributionController::class . '::roomIndexAction')
            ->setPathPrefix('/admin/filedistribution/')
            ->setNamePrefix('admin_filedistribution_')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function prepareBreadcrumbs(): array
    {
        $ret = parent::prepareBreadcrumbs();
        $ret[_('File distribution')] = $this->router()->generate('fd_filedistribution_index');

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthorized(): bool
    {
        return $this->isGranted(Privilege::FD_ROOMS);
    }

    /* LOGGING */

    /**
     * {@inheritdoc}
     */
    public function postPersist(CrudInterface $object): void
    {
        $this->logger()->writeForModule(sprintf('Raum "%s" zur Raumliste hinzugefügt', (string)$object->getRoom()), 'File distribution');
    }

    /**
     * {@inheritdoc}
     */
    public function postRemove(CrudInterface $object): void
    {
        $this->logger()->writeForModule(sprintf('Raum "%s" aus der Raumliste entfernt', (string)$object->getRoom()), 'File distribution');
    }

    private function logger(): Logger
    {
        return $this->locator->get(Logger::class);
    }

    public static function getSubscribedServices(): array
    {
        $deps = parent::getSubscribedServices();
        $deps[] = Logger::class;

        return $deps;
    }
}
