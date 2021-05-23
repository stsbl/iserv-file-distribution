<?php

declare(strict_types=1);

namespace Stsbl\FileDistributionBundle\Entity;

use Doctrine\ORM\NoResultException;
use IServ\CrudBundle\Doctrine\ORM\EntitySpecificationRepository;
use Stsbl\FileDistributionBundle\Crud\FileDistributionCrud;

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
 * Repository class for FileDistributionRoom
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
final class FileDistributionRoomRepository extends EntitySpecificationRepository
{
   /**
    * Checks if there is at least one room which is not excluded.
    */
    public function isRoomAvailable(): bool
    {
        $qb = $this->createQueryBuilder('c')
            ->resetDQLParts() // needed to clear
            ->select('1')
            ->from('IServRoomBundle:Room', 'r')
        ;

        $subQb = $this->createQueryBuilder('c');

        $subQb
            ->where($subQb->expr()->eq('c.room', 'r.id'))
        ;

        if (FileDistributionCrud::getRoomMode() === true) {
            $qb->where($qb->expr()->not($qb->expr()->exists($subQb)));
        } else {
            $qb->where($qb->expr()->exists($subQb));
        }

        if (count($qb->getQuery()->getResult()) > 0) {
            return true;
        }

        return false;
    }
}
