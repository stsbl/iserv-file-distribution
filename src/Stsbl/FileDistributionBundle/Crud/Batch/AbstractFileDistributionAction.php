<?php
// src/Stsbl/FileDistributionBundle/Crud/Batch/AbstractFileDistributionAction.php
namespace Stsbl\FileDistributionBundle\Crud\Batch;

use Doctrine\ORM\EntityManager;
use IServ\CoreBundle\Service\Shell;
use IServ\CoreBundle\Security\Core\SecurityHandler;
use IServ\CrudBundle\Crud\AbstractCrud;
use IServ\CrudBundle\Crud\Batch\AbstractBatchAction;
use IServ\CrudBundle\Entity\FlashMessageBag;
use Stsbl\FileDistributionBundle\Crud\FileDistributionCrud;
use Stsbl\FileDistributionBundle\Service\Rpc;
use Symfony\Component\HttpFoundation\Session\Session;

/*
 * The MIT License
 *
 * Copyright 2017 Felix Jacobi.
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
abstract class AbstractFileDistributionAction extends AbstractBatchAction 
{
    /**
     * @var Shell
     */
    protected $shell;
    
    /**
     * @var EntityManager
     */
    protected $em;
    
    /**
     * @var SecurityHandler
     */
    protected $securityHandler;
    
    /**
     * @var Session
     */
    protected $session;
    
    /**
     * @var Rpc
     */
    protected $rpc;

    /**
     * {@inheritdoc}
     */
    public function __construct(AbstractCrud $crud, $enabled = true) {
        // check for valid crud
        if (!$crud instanceof FileDistributionCrud) {
            throw new \InvalidArgumentException(sprintf('This batch action only supports the CRUD %s or childs of it.', FileDistributionCrud::class));
        }
        
        parent::__construct($crud, $enabled);
        
        // inject required components into class
        /* @var $crud FileDistributionCrud */
        $this->shell = $crud->getShell();
        $this->em = $crud->getEntityManager();
        $this->securityHandler = $crud->getSecurityHandler();
        $this->session = $crud->getSession();
        $this->rpc = $crud->getRpc();
    }
    
    /**
     * Convert shell error output into a flash message
     * 
     * @param FlashMessageBag $bag
     * @param array $errors
     */
    protected function handleShellErrorOutput(FlashMessageBag $bag, array $errors)
    {
        if (count($errors) > 0) {
            $bag->addMessage('error', implode("\n", $errors));
        }
        
        return $bag;
    }
}
