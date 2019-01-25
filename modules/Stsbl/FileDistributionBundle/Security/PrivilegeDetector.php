<?php declare(strict_types = 1);

namespace Stsbl\FileDistributionBundle\Security;

use IServ\HostBundle\Entity\Host;
use Stsbl\FileDistributionBundle\Entity\FileDistribution;
use Stsbl\FileDistributionBundle\Repository\FileDistributionRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/*
 * The MIT License
 *
 * Copyright 2019 Felix Jacobi.
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
class PrivilegeDetector
{
    /**
     * @var \Stsbl\FileDistributionBundle\Repository\FileDistributionRepository
     */
    private $fileDistributionRepo;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(FileDistributionRepository $fileDistributionRepo, TokenStorageInterface $tokenStorage)
    {
        $this->fileDistributionRepo = $fileDistributionRepo;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Checks if user is allowed to stop a file distribution
     */
    public function isAllowedToStop(Host $host): bool
    {
        $fileDistribution = $this->getFileDistributionForHost($host);

        if (null === $fileDistribution) {
            return false;
        }

        if ($this->tokenStorage->getToken()->getUser() !== $fileDistribution->getUser()) {
            return false;
        }

        return true;
    }

    /**
     * Checks if user is allowed to enable a file distribution
     */
    public function isAllowedToEnable(Host $host): bool
    {
        $fileDistribution = $this->getFileDistributionForHost($host);

        if (null === $fileDistribution) {
            return true;
        }

        return false;
    }

    public function getFileDistributionForHost(Host $object): ?FileDistribution
    {
        try {
            /** @var FileDistribution $fileDistribution */
            $fileDistribution = $this->fileDistributionRepo->findOneBy(['ip' => $object->getIp()]);

            return $fileDistribution;
        } catch (\Throwable $e) {
            return null;
        }
    }

}
