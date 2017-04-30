<?php
// src/Stsbl/FileDistributionBundle/Controller/FileDistributionController.php
namespace Stsbl\FileDistributionBundle\Controller;

use IServ\CoreBundle\Form\Type\BooleanType;
use IServ\CoreBundle\Util\Sudo;
use IServ\CrudBundle\Controller\CrudController;
use IServ\CrudBundle\Table\ListHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;

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
    const ROOM_CONFIG_FILE = '/var/lib/stsbl/file-distribution/cfg/room-mode.json';
    
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
            
            $ret['status'] = $this->get('iserv.host.status')->get();
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
                
                $multiSelectForm
                    ->add('title', TextType::class, [
                        'label' => false,
                        'attr' => [
                            'placeholder' => _('Title for this file distribution'),
                            'help_text' => _('The folder path where you will find the assignment folder and the returns will be Files/File-Distribution/<Title>.')
                        ]
                    ]);
                    
                $isolationAttr = [];   
                if ($this->get('iserv.config')->get('FileDistributionHostIsolationDefault')) {
                    $isolationAttr['checked'] = 'checked';
                }
                $isolationAttr['help_text'] = _('Enable host isolation if you want to prevent that users can exchange files by sharing their accounts.');
                
                $multiSelectForm
                    ->add('isolation', CheckboxType::class, [
                        'label' => _('Enable host isolation'),
                        'attr' => $isolationAttr,
                    ])
                    ->add('rpc_message', TextareaType::class, [
                        'label' => _('Message'),
                        'attr' => [
                            'rows' => 10,
                            'cols' => 230,
                            'placeholder' => _('Enter a message...')
                        ]
                    ])
                ;
                
                
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
                
                // display title and isolation only on enable file distribution
                if (!$confirmForm->get('actions')->has('enable')) {
                    $confirmForm->remove('title');
                    $confirmForm->remove('isolation');
                }
                
                // display rpc_message only on sending message
                if (!$confirmForm->get('actions')->has('message')) {
                    $confirmForm->remove('rpc_message');
                }

            } else {
                $this->addFlash('warning', _('No host selected!'));

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
                            // set isolation on enable
                            $isolation = (boolean)$form->getData()['isolation'];
                            // assume false on empty return value
                            if (empty($isolation)) {
                                $isolation = false;
                            }
                            $action->setIsolation($isolation);
                        } else if ($action->getName() === 'message') {
                            // set message
                            $action->setMessage($form->getData()['rpc_message']);
                        }
                        
                        foreach ($data['multi'] as $k => $v) {
                            // skip host which has the client ip, but only if there are 
                            // more than one host selected.
                            if ($v->getIp() === $request->getClientIp() && count($data['multi']) > 1 && $action->getName() === 'enable') {
                                $this->addFlash('warning', _('Skipping own host!'));
                                unset($data['multi'][$k]);
                            }
                        }
                        
                        // if the own host was the only one, it was may removed above and we would
                        // have no more hosts.
                        if (count($data['multi']) > 0) {
                            // Run action, collect feedback and return to list afterwards.
                            $message = $action->execute($data['multi']);

                            if ($message) {
                                $this->addFlash($message);
                            }
                        }
                        
                        return $this->redirect($this->crud->generateUrl('index'));
                    }
                }

                throw new \RuntimeException('No valid batch action was found on submit!');
            }
        }

        return $this->redirect($this->crud->generateUrl('index'));
    }
    
    /**
     * Looksup for existing file distributions owned by user
     * 
     * @param Request $request
     * @return JsonResponse
     * @Route("filedistribution/lookup", name="fd_filedistribution_lookup", options={"expose": true})
     * @Security("is_granted('PRIV_FILE_DISTRIBUTION') and is_granted('PRIV_COMPUTER_BOOT')")
     */
    public function lookupAction(Request $request)
    {
        $query = $request->get('query');
        
        if (empty($query)) {
            throw new \RuntimeException('query should not be empty.');
        }
        
        $this->get('iserv.sudo');
        
        $suggestions = [];
        $home = $this->getUser()->getHome();
        $directory = $home.'/File-Distribution/';
        
        $directories = Sudo::glob($directory.'*/');
        
        // add existing directories to suggestions
        foreach ($directories as $directory) {
            $basename = basename($directory);
            if (Sudo::is_dir($directory.'Assignment') && Sudo::is_dir($directory.'Return') && preg_match(sprintf('/^(.*)%s(.*)$/', $query), $basename)) {
                $suggestions[$basename] = [
                    'type' => 'existing',
                    'label' => $basename,
                    'value' => $basename,
                    'extra' => _('Existing file distribution directory')
                ];
            }
        }
        
        $em = $this->getDoctrine()->getManager();
        
        /* @var $qb \Doctrine\ORM\QueryBuilder */
        $qb = $em->createQueryBuilder(self::class);
        
        // get current file distributions from database
        $qb
            ->select('f')
            ->from('StsblFileDistributionBundle:FileDistribution', 'f')
            ->where('f.user = :user')
            ->andWhere('f.title LIKE :query ')
            ->setParameter(':user', $this->getUser())
            ->setParameter(':query', '%'.$query.'%')
        ;
        
        /* @var $results \Stsbl\FileDistributionBundle\Entity\FileDistribution[] */
        $results = $qb->getQuery()->getResult();
        
        foreach ($results as $result) {
            $suggestions[$result->getPlainTitle()] = [
                'type' => 'running',
                'label' => $result->getPlainTitle(),
                'value' => $result->getPlainTitle(),
                'extra' => _('Running file distribution')
            ];
        }
        
        asort($suggestions);
        
        return new JsonResponse($suggestions);
    }
    
    /**
     * Get form for room inclusion mode
     * 
     * @return \Symfony\Component\Form\Form
     */
    private function getRoomInclusionForm()
    {
        /* @var $builder \Symfony\Component\Form\FormBuilder */
        $builder = $this->get('form.factory')->createNamedBuilder('file_distribution_room_inclusion');
        
        $content = file_get_contents(self::ROOM_CONFIG_FILE);
        $mode = json_decode($content, true)['invert'];

        if ($mode === true) {
            $mode = 1;
        } else {
            $mode = 0;
        }
        
        $builder
            ->add('mode', BooleanType::class, [
                'label' => false,
                'choices' => [
                    _('All rooms except the following') => '1',
                    _('The following rooms') => '0',
                ],
                'preferred_choices' => [(string)$mode],
                'constraints' => [new NotBlank()],
            ])
            ->add('submit', SubmitType::class, [
                'label' => _('Save'),
                'buttonClass' => 'btn-success',
                'icon' => 'pro-floppy-disk'
            ])
        ;
        
        return $builder->getForm();
    }
    
    /**
     * index action for room admin
     * 
     * @param Request $request
     * @return array
     */
    public function roomIndexAction(Request $request)
    {
        $ret = parent::indexAction($request);
        $form = $this->getRoomInclusionForm();
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $mode = (boolean)$form->getData()['mode'];
            $content = json_encode(['invert' => $mode]);
            
            file_put_contents(self::ROOM_CONFIG_FILE, $content);
            $this->get('iserv.flash')->success(_('Room settings updated successful.'));
        }
        
        $ret['room_inclusion_form'] = $form->createView();
        
        return $ret;
    }
}
