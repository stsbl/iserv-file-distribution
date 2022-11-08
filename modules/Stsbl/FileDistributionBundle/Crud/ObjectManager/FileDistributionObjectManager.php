<?php

declare(strict_types=1);

namespace Stsbl\FileDistributionBundle\Crud\ObjectManager;

use Doctrine\Common\Collections\Criteria;
use IServ\CrudBundle\Crud\ObjectManagerInterface;
use IServ\CrudBundle\Doctrine\ORM\DoctrineObjectManagerInterface;
use IServ\CrudBundle\Doctrine\Specification\SpecificationInterface;
use IServ\HostBundle\Entity\Host;
use Stsbl\FileDistributionBundle\FileDistribution\FileDistribution;
use Webmozart\Assert\Assert;

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

/**
 * @implements DoctrineObjectManagerInterface<FileDistribution>
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 *
 */
final class FileDistributionObjectManager implements DoctrineObjectManagerInterface, ObjectManagerInterface
{
    public function __construct(
        private readonly DoctrineObjectManagerInterface&ObjectManagerInterface $inner,
        private readonly HostDecorator $hostDecorator,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function createQueryBuilder($class)
    {
        return $this->inner->createQueryBuilder(Host::class);
    }

    /**
     * @inheritDoc
     */
    public function findBySpecification(
        $class,
        SpecificationInterface $specification,
        array $orderBy = [],
        $limit = null,
        $offset = null
    ) {
        $hosts = $this->inner->findBySpecification(Host::class, $specification, $orderBy, $limit, $offset);

        return $this->decorateAll($hosts);
    }

    /**
     * @inheritDoc
     */
    public function findByCriteria($class, Criteria $criteria, array $orderBy = [], $limit = null, $offset = null)
    {
        $hosts = $this->inner->findByCriteria(Host::class, $criteria, $orderBy, $limit, $offset);

        return $this->decorateAll($hosts);
    }

    /**
     * @inheritDoc
     */
    public function findByDql($class, $dql, $parameters = [], array $orderBy = [], $limit = null, $offset = null)
    {
        $hosts = $this->inner->findByDql(Host::class, $dql, $parameters, $orderBy, $limit, $offset);

        return $this->decorateAll($hosts);
    }

    /**
     * @inheritDoc
     */
    public function setFilters(array $filters)
    {
        $this->inner->setFilters($filters);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getRepository($class)
    {
        return $this->inner->getRepository($class);
    }

    /**
     * @inheritDoc
     */
    public function getId($class)
    {
        return $this->inner->getId(Host::class);
    }

    /**
     * @inheritDoc
     */
    public function refresh(object $object): void
    {
        Assert::isInstanceOf($object, FileDistribution::class);
        /** @var FileDistribution $object */

        $this->inner->refresh($object->getHost());
    }

    public function create($object)
    {
        Assert::isInstanceOf($object, FileDistribution::class);
        /** @var FileDistribution $object */

        return $this->inner->create($object->getHost());
    }

    public function update($object)
    {
        Assert::isInstanceOf($object, FileDistribution::class);
        /** @var FileDistribution $object */

        return $this->inner->update($object->getHost());
    }

    public function delete($object)
    {
        Assert::isInstanceOf($object, FileDistribution::class);
        /** @var FileDistribution $object */

        return $this->inner->delete($object->getHost());
    }

    public function getNewInstance($class)
    {
        return new FileDistribution(
            new Host(),
            null,
            null,
            null,
            null,
            null,
            null,
            null,
        );
    }

    public function find($class, $id)
    {
        $host = $this->inner->find(Host::class, $id);

        if (null !== $host) {
            return $this->hostDecorator->decorateHost($host);
        }

        return null;
    }

    public function findBy($class, array $criteria = [], array $orderBy = [])
    {
        $hosts = $this->inner->findBy(Host::class, $criteria, $orderBy);

        return $this->decorateAll($hosts);
    }

    public function findOneBy($class, array $criteria = [])
    {
        return $this->hostDecorator->decorateHost($this->inner->findBy(Host::class, $criteria));
    }

    public function getMessages($type = null, $reset = true)
    {
        return $this->inner->getMessages($type, $reset);
    }

    /**
     * @param Host[] $hosts
     * @return FileDistribution[]
     */
    private function decorateAll(array $hosts): array
    {
        $result = [];
        foreach ($hosts as $host) {
            $result[] = $this->hostDecorator->decorateHost($host);
        }

        return $result;
    }
}
