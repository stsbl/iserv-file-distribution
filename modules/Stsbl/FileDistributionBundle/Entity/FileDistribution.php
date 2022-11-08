<?php

declare(strict_types=1);

namespace Stsbl\FileDistributionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use IServ\CoreBundle\Entity\User;
use IServ\CrudBundle\Entity\CrudInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints as DoctrineAssert;
use Symfony\Component\Validator\Constraints as Assert;

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
 * StsblFileDistributionBundle:FileDistribution
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 * @ORM\Entity(repositoryClass="FileDistributionRepository")
 * @ORM\Table(name="file_distribution")
 * @DoctrineAssert\UniqueEntity("ip", message="File distribution is already enabled for this host.")
 */
class FileDistribution implements CrudInterface
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="text", nullable=false)
     * @Assert\NotBlank()
     * @Assert\Regex("/^([ !#&()*+,\-.0-9:<=>?\@A-Z\[\]^_a-z{|}~$])/", message="The title contains invalid characters.")
     *
     * @var string
     */
    private $title;

    /**
     * @ORM\ManyToOne(targetEntity="\IServ\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="act", referencedColumnName="act", onDelete="CASCADE")
     * @Assert\NotBlank()
     *
     * @var User
     */
    private $user;

    /**
     * @ORM\Column(type="text", nullable=false)
     *
     * @var string
     */
    private $act;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     *
     * @var boolean
     */
    private $isolation;

    /**
     * @ORM\Column(type="inet", nullable=false)
     * @Assert\NotBlank()
     * @Assert\Ip()
     *
     * @var string
     */
    private $ip;

    /**
     * @ORM\Column(name="FolderAvailability", type="text", nullable=false)
     * @Assert\Choice(choices = {"keep", "readonly", "replace"}, message = "Choose a valid folder availability.")
     *
     * @var string
     */
    private $folderAvailability;

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return $this->title;
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return $this
     */
    public function setIp(string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * @return $this
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return sprintf('%s - %s', $this->title, (string)$this->user);
    }

    public function getPlainTitle(): string
    {
        return $this->title;
    }

    /**
     * @return $this
     */
    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return $this
     */
    public function setAct(string $act): self
    {
        $this->act = $act;

        return $this;
    }

    public function getAct(): string
    {
        return $this->act;
    }

    /**
     * @return $this
     */
    public function setIsolation(bool $isolation): self
    {
        $this->isolation = $isolation;

        return $this;
    }

    public function getIsolation(): bool
    {
        return $this->isolation;
    }

    /**
     * @return $this
     */
    public function setFolderAvailability(string $folderAvailability): self
    {
        $this->folderAvailability = $folderAvailability;

        return $this;
    }

    public function getFolderAvailability(): string
    {
        return $this->folderAvailability;
    }
}
