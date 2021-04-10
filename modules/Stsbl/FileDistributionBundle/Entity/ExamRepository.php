<?php
// src/Stsbl/FileDistributionBundle/Entity/ExamRepository.php
namespace Stsbl\FileDistributionBundle\Entity;

use Doctrine\ORM\NoResultException;
use IServ\CrudBundle\Doctrine\ORM\EntitySpecificationRepository;

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
 * Repository class for FileDistribution
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 *
 * @deprecated Just for transitional purposes. Do not use for any new code!
 */
class ExamRepository extends EntitySpecificationRepository
{
    /**
     * Checks if there's at least on file distribution.
     * 
     * @return boolean
     */
    public function exists()
    {
        $qb = $this->createQueryBuilder('c')
            ->select('1')
            ->setMaxResults(1)
        ;
        
        try {
            $qb->getQuery()->getSingleScalarResult();
            return true;
        } catch (NoResultException $e) {
            return false;
        }
    }
}
