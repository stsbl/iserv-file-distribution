<?php

declare(strict_types=1);

namespace Stsbl\FileDistributionBundle\Entity\Specification;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use IServ\CrudBundle\Doctrine\Specification\AbstractSpecification;
use Stsbl\FileDistributionBundle\Entity\Host;

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
 * Specification to filter allowed file distribution hosts.
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
final class FileDistributionSpecification extends AbstractSpecification
{
    /**
     * @var bool
     */
    private $invert;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(bool $invert, EntityManagerInterface $em)
    {
        $this->invert = $invert;
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     */
    public function match(QueryBuilder $qb, $dqlAlias)
    {
        $subQb = $this->em->createQueryBuilder();
        $subQb
            ->select('fr')
            ->from('StsblFileDistributionBundle:FileDistributionRoom', 'fr')
            ->where('fr.room = ' . $dqlAlias . '.room')
        ;

        if (true === $this->invert) {
            return $qb->expr()->andX($qb->expr()->not($qb->expr()->exists($subQb)), $qb->expr()->eq($dqlAlias . '.controllable', 'true'));
        }

        return $qb->expr()->andX($qb->expr()->exists($subQb), $qb->expr()->eq($dqlAlias . '.controllable', 'true'));
    }

    /**
     * {@inheritdoc}
     */
    public function supports($className)
    {
        return Host::class === $className;
    }

}
