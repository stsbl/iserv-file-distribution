<?php
// src/Stsbl/FileDistributionBundle/Controller/FileDistributionController.php
namespace Stsbl\FileDistributionBundle\Controller;

use IServ\CrudBundle\Controller\CrudController;
use IServ\CrudBundle\Table\ListHandler;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

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
 * FileDistribution default controller
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class FileDistributionController extends CrudController
{
    /**
     * {@inheritdoc}
     */
    public function indexAction(Request $request) 
    {
        $ret = parent::indexAction($request);
        
        if (is_array($ret)) {
            $session = $request->getSession();
            
            $ret['display_msg'] = $session->has('fd_title');
            
            if ($ret['display_msg']) {
                $title = $session->get('fd_title');
                $ret['path'] = ['title' => $title, 'encoded' => base64_encode(sprintf('Files/File-Distribution/%s', $title))];
                $session->remove('fd_title');
            } else {
                $ret['title'] = null;
            }
        }
        
        return $ret;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function prepareBatchActions($items, $confirm = false, $enabled = null, ListHandler $listHandler = null)
    {
        $ret = parent::prepareBatchActions($items, $confirm, $enabled, $listHandler);

        if (is_array($ret)) {
            if ($confirm) {
                /* @var $multiSelectForm \Symfony\Component\Form\Form */
                $multiSelectForm = $ret['form'];
                
                $multiSelectForm->add('title', TextType::class, [
                    'label' => false,
                    'attr' => [
                        'placeholder' => _('Title for this file distribution')
                    ]
                ]);
                
                $ret['form'] = $multiSelectForm;
            }
        }
        
        return $ret;
    }
    
    /**
     * {@inheritdoc}
     */
    public function confirmBatchAction(Request $request)
    {
        // FIXME: Cleanup ugly list handler injection for non-Doctrine based MultiSelect (#1251)
        if (false === $multiSelect = $this->prepareBatchActions(null, false, null, $this->crud->getListHandler($request))) {
            throw new \RuntimeException(sprintf('There are no batch actions defined in `%s`.', get_class($this->crud)));
        }

        // Handle multi select form action
        /* @var $form \Symfony\Component\Form\Form */
        $form = $multiSelect['form'];
        $form->handleRequest($request);
        $batchAction = null;

        if ($form->isValid()) {
            $data = $form->getData();
            //dump($data);
            if ((is_array($data['multi']) && !empty($data['multi']) || (is_object($data['multi']) && !$data['multi']->isEmpty()))) {
                // Normalize items
                $items = is_array($data['multi']) ? $data['multi'] : $data['multi']->toArray();

                // Check which batch action is executed
                /* @var $action \IServ\CrudBundle\Crud\Batch\BatchActionInterface */
                if (!empty($data['grouped_actions'])) {
                    $batchAction = $data['grouped_actions'];
                }
                else {
                    foreach ($this->crud->getBatchActions(null) as $action) {
                        if ($action->getName() == $form->getClickedButton()->getName()) {
                            $batchAction = $action;
                            break;
                        }
                    }
                }
                if (!isset($batchAction)) {
                    throw new \RuntimeException('No valid batch action found!');
                }

                // Check for each item if user is allowed to execute the batch action on it
                $badItems = array();
                foreach ($data['multi'] as $key => $item) {
                    if (!$batchAction->isAllowedToExecute($item, $this->getUser())) {
                        $badItems[] = $item;
                        unset($items[$key]);
                    }
                }

                // Create a confirmation form based on the committed data based on the multi selection.
                /* @var $confirmForm Form */
                // TODO: Create a new MultiHiddenEntity form type
                $confirm = $this->prepareBatchActions($items, true, null, $this->crud->getListHandler($request));
                $confirmForm = $confirm['form'];

                // Remove all currently unused batch actions from the confirmation form
                foreach ($this->crud->getBatchActions(null) as $action) {
                    if ($action !== $batchAction) {
                        $confirmForm->get('actions')->remove($action->getName());
                    }
                }
                
                // display title only on enable file distribution
                if (!$confirmForm->get('actions')->has('enable')) {
                    $confirmForm->remove('title');
                }

            } else {
                $this->addFlash('warning', _('No element selected!'));

                return $this->redirect($this->crud->generateUrl('index'));
            }
        } else {
            throw new \RuntimeException('Confirmation batch action form not valid: ' . (string)$form->getErrors(true, false));
        }

        // Execute batch action if it doesn't require confirmation
        if (null !== $batchAction && !$batchAction->requiresConfirmation()) {
            // Don't use forward() to ease handling!
            return $this->batchAction($request);
        }

        // Prepare form view
        $confirm['form'] = $confirm['form']->createView();

        // Track path
        $this->prepareBreadcrumbs();
        $this->addBreadcrumb($this->crud->getTitle(), $this->crud->generateUrl('index'));
        $this->addBreadcrumb(_('Confirm action'));

        return array(
            '_template' => $this->crud->getTemplate('crud_batch_confirm'),
            'admin' => $this->crud,
            'items' => $items,
            'disallowedItems' => $badItems,
            'fields' => $this->crud->getFields(),
            'confirmForm' => $confirm,
            'batchAction' => $batchAction,
        );
    }
    
    /**
     * {@inheritdoc}
     */
    public function batchAction(Request $request)
    {
        // Init multi select
        if (false === $multiSelect = $this->prepareBatchActions(null, true, null, $this->crud->getListHandler($request))) {
            throw new \RuntimeException(sprintf('There are no batch actions defined in `%s`.', get_class($this->crud)));
        }

        // Handle form action
        /* @var $form \Symfony\Component\Form\Form */
        $form = $multiSelect['form'];
        $form->handleRequest($request);

        if ($form->getClickedButton()->getName() === 'cancel') {
            return $this->redirect($this->crud->generateUrl('index'));
        }

        if ($form->isValid()) {
            $data = $form->getData();
            if (is_array($data['multi'])) {
                $data['multi'] = new ArrayCollection($data['multi']);
            }
            if (!$data['multi']->isEmpty()) {
                //dump($data);

                // Get batch action
                foreach ($this->crud->getBatchActions(null) as $action) {
                    if ($form->get('actions')->get($action->getName())->isClicked()) {
                        if ($action->getName() === 'enable') {
                            // set title on enable
                            $action->setTitle($form->getData()['title']);
                        }
                        
                        // Run action, collect feedback and return to list afterwards.
                        $message = $action->execute($data['multi']);

                        if ($message) {
                            $this->addFlash($message);
                        }

                        return $this->redirect($this->crud->generateUrl('index'));
                    }
                }

                throw new \RuntimeException('No valid batch action was found on submit!');
            }
        }

        return $this->redirect($this->crud->generateUrl('index'));
    }
}
