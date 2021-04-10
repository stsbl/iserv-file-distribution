<?php

namespace Stsbl\FileDistributionBundle\Repository;

use IServ\CoreBundle\Service\BundleDetector;
use IServ\CrudBundle\Entity\AbstractDecoratedServiceRepository;
use Stsbl\FileDistributionBundle\Entity\Host;
use Symfony\Bridge\Doctrine\RegistryInterface;

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
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 *
 * @deprecated Just for transitional purposes. Do not use for any new code!
 */
class HostRepository extends AbstractDecoratedServiceRepository
{
    /**
     * @var BundleDetector
     */
    private $bundleDetector;

    public function __construct(RegistryInterface $registry, BundleDetector $bundleDetector)
    {
        parent::__construct($registry, Host::class);

        $this->bundleDetector = $bundleDetector;
    }

    /**
     * Resets the internet access for the given hosts
     *
     * @return mixed
     */
    public function resetInternetAccess(\ArrayAccess $hosts)
    {
        $qb = $this->_em->createQueryBuilder();

        $qb
            ->update($this->getEntityName(), 'h')
            ->set('h.overrideUntil', 'NULL')
            ->set('h.overrideRoute', 'NULL')
            ->set('h.overrideBy', 'NULL')
            ->where($qb->expr()->in('h', ':hosts'))
            ->setParameter('hosts', $hosts)
        ;

        return $qb->getQuery()->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        $alias = 'parent';
        $qb = $this->createDecoratedQueryBuilder($alias);

        foreach ($criteria as $key => $value) {
            if ('name' === $key) { // Special casing case insensitive name
                $qb
                    ->andWhere("LOWER($alias.$key) = :$key")
                    ->setParameter($key, strtolower($value))
                ;
            } else {
                $qb
                    ->andWhere("$alias.$key = :$key")
                    ->setParameter($key, $value)
                ;
            }
        }

        if ($orderBy) {
            $decorated = $this->getDecoratorColumns();
            foreach ($orderBy as $name => $dir) {
                if (array_key_exists($name, $decorated)) {
                    $qb->addOrderBy($name, $dir);
                } else {
                    $qb->addOrderBy("$alias.$name", $dir);
                }
            }
        }

        $qb->setMaxResults($limit)->setFirstResult($offset);

        $res = $qb->getQuery()->getResult();

        return $this->decorateResults($res);
    }

    /**
     * {@inheritdoc}
     */
    public function getDecoratorColumns()
    {
        $ret = [
            'sambaUser' => '(SELECT u FROM IServCoreBundle:User u WHERE u.username = (SELECT MAX(s.act) FROM IServHostBundle:SambaUser s WHERE s.ip=parent.ip AND s.since=(SELECT MAX(v.since) FROM IServHostBundle:SambaUser v WHERE v.ip = parent.ip)))',
            'lastseenDhcp' => '(SELECT MAX(hd.date) FROM IServHostBundle:HostsDhcp hd WHERE hd.ip = parent.ip)',
            'lastseen' => "GREATEST(parent.lastseenPing, GREATEST(parent.lastseenLog, (SELECT MAX(hd2.date) FROM IServHostBundle:HostsDhcp hd2 WHERE hd2.ip = parent.ip)))",
        ];

        $ret['ipInternet'] = '(parent.ip)';
        $ret['fileDistribution'] = '(SELECT f.id FROM StsblFileDistributionBundle:FileDistribution f WHERE f.ip = parent.ip)';
        $ret['soundLock'] = '(SELECT sl.ip FROM StsblFileDistributionBundle:SoundLock sl WHERE sl.ip = parent.ip)';

        if ($this->bundleDetector->isLoaded('IServExamBundle')) {
            $ret['exam'] = '(SELECT e.title FROM StsblFileDistributionBundle:Exam e WHERE e.ip = parent.ip)';
        }

        if ($this->bundleDetector->isLoaded('IServLockBundle')) {
            $ret['locker'] = '(SELECT IDENTITY(l.user) FROM IServLockBundle:Lock l WHERE l.ip = parent.ip)';
        }

        return $ret;
    }
}
