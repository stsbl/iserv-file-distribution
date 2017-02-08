<?php
// src/Stsbl/FileDistributionBundle/Controller/FileDistributionController.php
namespace Stsbl\FileDistributionBundle\Controller;

use Braincrafted\Bundle\BootstrapBundle\Form\Type\BootstrapCollectionType;
use Braincrafted\Bundle\BootstrapBundle\Form\Type\FormActionsType;
use IServ\CoreBundle\Util\Sudo;
use IServ\CrudBundle\Controller\CrudController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Stsbl\FileDistributionBundle\Form\Type\FileDistributionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
            
            $ret['display_msg'] = $session->has('fd_titles');
            
            if ($ret['display_msg']) {
                $ret['paths'] = [];
                
                foreach ($session->get('fd_titles') as $title) {
                    $ret['paths'][] = ['title' => $title, 'encoded' => base64_encode(sprintf('Files/File-Distribution/%s', $title))];
                }
                
                $session->remove('fd_titles');
            } else {
                $ret['titles'] = null;
            }
        }
        
        return $ret;
    }
    
    /**
     * enable action
     * 
     * @param Request $request
     * @return array|RedirectResponse
     * @Route("/filedistribution/enable", name="fd_filedistribution_enable")
     * @Security("is_granted('PRIV_FILE_DISTRIBUTION')")
     * @Template()
     */
    public function enableAction(Request $request)
    {
        $form = $this->getForm();
        $form->handleRequest($request);
        
        if ($form->isSubmitted()) {
            $button = $form->getClickedButton()->getName();
        } else {
            $button = null;
        }
        
        if ($form->isSubmitted() && $form->isValid() && $button === 'submit') {
            /* @var $fileDistributions \Stsbl\FileDistributionBundle\Entity\FileDistribution[] */
            $fileDistrubtions = $form->getData()['distributions'];
            $em = $this->getDoctrine()->getManager();
            $messages = [];
            $titles = [];
            
            if (count($fileDistrubtions) < 1) {
                $this->get('iserv.flash')->alert(_('No hosts selected!'));
                goto render;
            }
            
            foreach ($fileDistrubtions as $d) {
                $em->persist($d);
                $messages[] = sprintf(_('Enabled file distribution for %s.'), $d->getHostname());
                
                // create directories if neccessary
                Sudo::_init($this->getUser()->getUsername(), $this->get('iserv.security_handler')->getSessionPassword());
                // adjust umask
                Sudo::umask(007);
                
                $home = $this->getUser()->getHome().'/';
                $directory = $home.'File-Distribution/'.$d->getPlainTitle().'/';
                $assignDirectory = $directory.'Assignment/';
                $returnDirectory = $directory.'Return/';
                $symlink = $home.'Files/File-Distribution';
                
                // main directory
                if (!Sudo::file_exists($directory)) {
                    Sudo::mkdir($directory, 0777, true);
                }
                
                // assign directory
                if (!Sudo::file_exists($assignDirectory)) {
                    Sudo::mkdir($assignDirectory, 0777, true);
                }
                
                // return directory
                if (!Sudo::file_exists($returnDirectory)) {
                    Sudo::mkdir($returnDirectory, 0777, true);
                }
                
                // create symlink if neccessary
                if (!Sudo::file_exists($symlink)) {
                    Sudo::symlink('../File-Distribution', $symlink);
                }
                
                $titles[] = $d->getPlainTitle();
            }
            
            $em->flush();
            
            /* @var $shell \IServ\CoreBundle\Service\Shell */
            $shell = $this->get('iserv.shell');
            $shell->exec('sudo', ['/usr/lib/iserv/file_distribution_config']);
            
            if (count($messages) > 0) {
                $this->get('iserv.flash')->success(implode("\n", $messages));
            }
            
            // save titles of enabled file distributions in session to display them on the next page
            $session = $request->getSession();
            $session->set('fd_titles', array_unique($titles));
            
            return $this->redirectToRoute('fd_filedistribution_index');
        } else if ($form->isSubmitted() && $button === 'cancel') {
            return $this->redirectToRoute('fd_filedistribution_index');
        }
        
        render:
        // track path
        $this->addBreadcrumb(_('File distribution'), $this->generateUrl('fd_filedistribution_index'));
        $this->addBreadcrumb(_('Enable'));
        
        return ['enable_form' => $form->createView(), 'help' => 'https://it.stsbl.de/documentation/mods/stsbl-iserv-file-distribution'];
    }
    
    /**
     * Get enable form
     * 
     * @return \Symfony\Component\Form\Form
     */
    private function getForm()
    {
        /* @var $builder \Symfony\Component\Form\FormBuilder */
        $builder = $this->get('form.factory')->createNamedBuilder('file-distribution');
        
        $builder
            ->add('distributions', BootstrapCollectionType::class, [
                'label' => _('Hosts'),
                'entry_type' => FileDistributionType::class,
                'prototype_name' => 'proto-entry',
                'add_button_text' => _('Add host'),
                'delete_button_text' => _('Remove host'),
                'sub_widget_col' => 8,
                // Child options
                'entry_options' => [
                    'attr' => [
                        'widget_col' => 12, // Single child field w/o label col
                    ],
                ],
            ])
            ->add('actions', FormActionsType::class)
        ;
        
        $builder->get('actions')
            ->add('submit', SubmitType::class, [
                'label' => _('Enable file distribution'),
                'buttonClass' => 'btn-success',
                'icon' => 'ok'
            ])
            ->add('cancel', SubmitType::class, [
                'label' => _('Cancel'),
                'buttonClass' => 'btn-default',
                'icon' => 'remove'
            ])
        ;
        
        return $builder->getForm();
    }
}
