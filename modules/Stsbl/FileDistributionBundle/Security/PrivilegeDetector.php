<?php declare(strict_types = 1);
// src/Stsbl/FileDistributionBundle/Security/PrivilegeDetector.php
namespace Stsbl\FileDistributionBundle\Security;

use IServ\HostBundle\Entity\Host;
use Stsbl\FileDistributionBundle\Entity\FileDistribution;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/*
 * The MIT License
 *
 * Copyright 2018 Felix Jacobi.
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
     * @var RegistryInterface
     */
    private $doctrine;

    public function __construct(RegistryInterface $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param Host $host
     * @return FileDistribution|null
     */
    private function getFileDistributionForHost(Host $host)/*: ?FileDistribution*/
    {
        $repository = $this->doctrine->getRepository(FileDistribution::class);

        try {
            $fileDistribution = $repository->findOneBy(['ip' => $host->getIp()]);

            return $fileDistribution;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Checks if user is allowed to stop a file distribution
     *
     * @param Host $host
     * @param UserInterface $user
     * @return bool
     */
    public function isAllowedToStop(Host $host, UserInterface $user = null): bool
    {
        $fileDistribution = $this->getFileDistributionForHost($host);

        if (null === $fileDistribution) {
            return false;
        }

        if ($user !== $fileDistribution->getUser()) {
            return false;
        }

        return true;
    }

    /**
     * Checks if user is allowed to enable a file distribution
     *
     * @param Host $host
     * @return bool
     */
    public function isAllowedToEnable(Host $host): bool
    {
        $fileDistribution = $this->getFileDistributionForHost($host);

        if ($fileDistribution === null) {
            return true;
        } else {
            return false;
        }
    }
}
