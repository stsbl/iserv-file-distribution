<?php

declare(strict_types=1);

namespace Stsbl\FileDistributionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\CoreBundle\Entity\User;
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
 * Temporary entity for exam mode, so that we can detect if a computer is in exam mode
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 *
 * @deprecated Just for transitional purposes. Do not use for any new code!
 *
 * @ORM\Entity(repositoryClass="Stsbl\FileDistributionBundle\Repository\ExamRepository")
 * @ORM\Table(name="exam")
 * @DoctrineAssert\UniqueEntity("ip", message="The computer is already in exam mode.")
 */
class Exam implements CrudInterface
{
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
     * @ORM\Column(type="text", nullable=false)
     * @Assert\NotBlank()
     * @Assert\Regex("/^([ !#&()*+,\-.0-9:<=>?\@A-Z\[\]^_a-z{|}~$])/", message="The title contains invalid characters.")
     *
     * @var string
     */
    private $title;

    /**
     * DO NOT ADD ANY REFERENCES to Host here, because Symfony do not like it!
     *
     * //@ORM\OneToOne(targetEntity="\IServ\HostBundle\Entity\Host", fetch="EAGER")
     * //@ORM\JoinColumn(name="ip", referencedColumnName="ip", onDelete="CASCADE")
     * @ORM\Column(type="text", nullable=false)
     * @Assert\NotBlank()
     * @Assert\Ip()
     * @ORM\Id
     *
     * @var string
     */
    private $ip;

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return $this->ip;
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): string
    {
        return $this->ip;
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
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
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
    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
