<?php

declare(strict_types=1);

namespace Stsbl\FileDistributionBundle\FileDistribution;

use IServ\CoreBundle\Entity\User;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\HostBundle\Entity\Host;
use IServ\HostBundle\Entity\SambaUser;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
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
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
final class FileDistribution implements CrudInterface
{
    private ?PropertyAccessor $propertyAccessor;

    public function __construct(
        private readonly Host $host,
        private readonly ?string $sambaUser,
        private readonly ?\DateTimeInterface $lastSeenDhcp,
        private readonly ?\DateTimeImmutable $lastSeen,
        private readonly ?int $fileDistribution,
        private readonly ?string $soundLock,
        private readonly ?string $exam,
        private readonly ?string $locker,

    ) {
    }

    public function getHost(): Host
    {
        return $this->host;
    }

    public function getSambaUser(): ?string
    {
        return $this->sambaUser;
    }


    public function getIpInternet(): string
    {
        return $this->host->getIp();
    }

    public function getLastSeenDhcp(): ?\DateTimeInterface
    {
        return $this->lastSeenDhcp;
    }

    public function getLastSeen(): ?\DateTimeImmutable
    {
        return $this->lastSeen;
    }

    public function getFileDistribution(): ?int
    {
        return $this->fileDistribution;
    }

    public function getSoundLock(): ?string
    {
        return $this->soundLock;
    }

    public function getExam(): ?string
    {
        return $this->exam;
    }

    public function getLocker(): ?string
    {
        return $this->locker;
    }

    public static function createFromRow(Host $host, array $row): self
    {
        $sambaUser = $row['sambaUser'] ?? null;
        $lastSeenDhcp = $row['lastseenDhcp'] ?? null;
        $lastSeen = $row['lastseen'] ?? null;
        $fileDistribution = $row['fileDistribution'] ?? null;
        $soundLock = $row['soundLock'] ?? null;
        $exam = $row['exam'] ?? null;
        $locker = $row['locker'] ?? null;

        if (null !== $sambaUser) {
            Assert::string($sambaUser);
        }
        
        if (null !== $lastSeenDhcp) {
            if (is_string($lastSeenDhcp)) {
                $lastSeenDhcp = \DateTimeImmutable::createFromFormat('Y-m-d H:i:sT', $lastSeenDhcp);
            }
            Assert::isInstanceOf($lastSeenDhcp, \DateTimeInterface::class);
        }

        if (null !== $lastSeen) {
            if (is_string($lastSeen)) {
                $lastSeen = \DateTimeImmutable::createFromFormat('Y-m-d H:i:sT', $lastSeen);
            }
            Assert::isInstanceOf($lastSeen, \DateTimeInterface::class);
        }

        if (null !== $fileDistribution) {
            Assert::integer($fileDistribution);
        }

        if (null !== $soundLock) {
            Assert::string($soundLock);
        }

        if (null !== $exam) {
            Assert::string($exam);
        }

        if (null !== $locker) {
            Assert::string($locker);
        }

        return new self(
            $host,
            $sambaUser,
            $lastSeenDhcp,
            $lastSeen,
            $fileDistribution,
            $soundLock,
            $exam,
            $locker,
        );
    }

    public function __toString(): string
    {
        return $this->host->__toString();
    }

    public function getId(): string
    {
        return (string)$this->host->getId();
    }

    public function __call(string $name, array $arguments)
    {
        return $this->host->{$name}(...$arguments);
    }

    public function __get(string $name)
    {
        return $this->propertyAccessor()->getValue($this->host, $name);
    }

    public function __set(string $name, $value): void
    {
        $this->propertyAccessor()->setValue($this->host, $name, $value);
    }

    public function __isset(string $name): bool
    {
        return $this->propertyAccessor()->isReadable($this->host, $name) &&
            $this->propertyAccessor()->getValue($this->host, $name) !== null;
    }

    private function propertyAccessor(): PropertyAccessorInterface
    {
        return $this->propertyAccessor ??= PropertyAccess::createPropertyAccessor();
    }
}
