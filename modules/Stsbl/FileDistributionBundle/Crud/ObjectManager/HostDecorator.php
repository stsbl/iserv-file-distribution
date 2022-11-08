<?php

declare(strict_types=1);

namespace Stsbl\FileDistributionBundle\Crud\ObjectManager;

/*
 * The MIT License
 *
 * Copyright 2022 Felix Jacobi.
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

use Doctrine\ORM\EntityManagerInterface;
use IServ\CoreBundle\Entity\User;
use IServ\CoreBundle\Service\BundleDetector;
use IServ\HostBundle\Entity\Host;
use IServ\HostBundle\Entity\HostsDhcp;
use IServ\HostBundle\Entity\SambaUser;
use IServ\LockBundle\Entity\Lock;
use Stsbl\FileDistributionBundle\Entity\Exam;
use Stsbl\FileDistributionBundle\Entity\FileDistribution as FileDistributionEntity;
use Stsbl\FileDistributionBundle\Entity\SoundLock;
use Stsbl\FileDistributionBundle\FileDistribution\FileDistribution;
use Webmozart\Assert\Assert;

/**
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
final class HostDecorator
{
    public function __construct(
        private readonly BundleDetector $bundleDetector,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function decorateHost(Host $host): FileDistribution
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();

        $queryBuilder->select('(SELECT u FROM ' . User::class . ' u WHERE u.username = (SELECT MAX(s.act) FROM ' . SambaUser::class . ' s WHERE s.ip=parent.ip AND s.since=(SELECT MAX(v.since) FROM ' . SambaUser::class . ' v WHERE v.ip = parent.ip))) AS sambaUser')
            ->addSelect('(SELECT MAX(hd.date) FROM ' . HostsDhcp::class . ' hd WHERE hd.ip = parent.ip) AS lastseenDhcp')
            ->addSelect('GREATEST(parent.lastseenPing, GREATEST(parent.lastseenLog, (SELECT MAX(hd2.date) FROM ' . HostsDhcp::class . ' hd2 WHERE hd2.ip = parent.ip)))')
            ->addSelect('(SELECT f.id FROM ' . FileDistributionEntity::class . ' f WHERE f.ip = parent.ip) AS fileDistribution')
            ->addSelect('(SELECT sl.ip FROM ' . SoundLock::class . ' sl WHERE sl.ip = parent.ip)')
        ;

        if ($this->bundleDetector->isLoaded('IServExamBundle')) {
            $queryBuilder->addSelect('(SELECT e.title FROM ' . Exam::class . ' e WHERE e.ip = parent.ip) AS exam');
        }

        if ($this->bundleDetector->isLoaded('IServLockBundle')) {
            $queryBuilder->addSelect('(SELECT IDENTITY(l.user) FROM ' . Lock::class . ' l WHERE l.ip = parent.ip)');
        }

        $queryBuilder
            ->from(Host::class, 'parent')
            ->where($queryBuilder->expr()->eq('parent.id', ':id'))
            ->setParameter('id', $host->getId())
        ;

        $result = $queryBuilder->getQuery()->getResult();
        Assert::isArray($result);

        foreach ($result as $row) {
            return FileDistribution::createFromRow($host, $row);
        }

        throw new \RuntimeException(sprintf('Could not decorate host "%s".', $host->getName()));
    }
}
