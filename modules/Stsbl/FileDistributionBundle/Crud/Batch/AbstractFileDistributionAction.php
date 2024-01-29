<?php

declare(strict_types=1);

namespace Stsbl\FileDistributionBundle\Crud\Batch;

use IServ\CrudBundle\Contracts\CrudContract;
use IServ\CrudBundle\Crud\Batch\AbstractBatchAction;
use IServ\CrudBundle\Crud\Batch\FormExtendingBatchActionInterface;
use IServ\CrudBundle\Entity\FlashMessage;
use Stsbl\FileDistributionBundle\Crud\FileDistributionCrud;
use Stsbl\FileDistributionBundle\Service\FileDistributionManager;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Session\Session;

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
 * FileDistribution abstract batch
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
abstract class AbstractFileDistributionAction extends AbstractBatchAction implements FormExtendingBatchActionInterface
{
    /**
     * @var FileDistributionCrud
     */
    protected $crud;

    /**
     * @var string|array
     */
    protected $privileges;

    /**
     * @var Session
     */
    protected $session;

    /**
     * {@inheritdoc}
     */
    public function __construct(CrudContract $crud, $enabled = true)
    {
        // check for valid crud
        if (!$crud instanceof FileDistributionCrud) {
            throw new \InvalidArgumentException(sprintf('This batch action only supports the CRUD %s or childs of it.', FileDistributionCrud::class));
        }

        $this->session = $crud->session();
        parent::__construct($crud, $enabled);
    }

    /**
     * Gets the file distribution manager and validates the required privilege.
     */
    protected function getFileDistributionManager(): FileDistributionManager
    {
        if ($this->privileges && !$this->checkPrivileges()) {
            throw new AccessDeniedException(sprintf('You need the %s privileges for this action!', (is_array($this->privileges) ? \implode(', ', $this->privileges) : $this->privileges)));
        }

        return $this->crud->fileDistributionManager();
    }

    /**
     * Checks if required privileges are granted.
     */
    protected function checkPrivileges(): bool
    {
        if (is_string($this->privileges)) {
            return $this->crud->authorizationChecker()->isGranted($this->privileges);
        }

        if (is_array($this->privileges)) {
            $granted = true;
            foreach ($this->privileges as $p) {
                if (!$this->crud->authorizationChecker()->isGranted($p)) {
                    $granted = false;
                    break;
                }
            }
            return $granted;
        }

        throw new \InvalidArgumentException(sprintf('Unexpected type %s for privileges variable!', gettype($this->privileges)));
    }

    /**
     * Create new flash message
     */
    protected function createFlashMessage(string $type, string $message): FlashMessage
    {
        return new FlashMessage($type, $message);
    }
}
