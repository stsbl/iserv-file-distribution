<?php
// src/Stsbl/FileDistributionBundle/Controller/FileDistributionController.php
namespace Stsbl\FileDistributionBundle\Controller;

use Doctrine\ORM\NoResultException;
use IServ\CoreBundle\Form\Type\BooleanType;
use IServ\CoreBundle\Service\Logger;
use IServ\CoreBundle\Util\Sudo;
use IServ\CrudBundle\Controller\StrictCrudController;
use IServ\HostBundle\Service\HostStatus;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Stsbl\FileDistributionBundle\Crud\FileDistributionCrud;
use Stsbl\FileDistributionBundle\Entity\Host;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\NotBlank;

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
 * FileDistribution default controller
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class FileDistributionController extends StrictCrudController
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
            
            $ret['ip'] = $request->getClientIp();
            $ret['room_available'] = $this->getDoctrine()->getRepository('StsblFileDistributionBundle:FileDistributionRoom')->isRoomAvailable();
        }
        
        return $ret;
    }
    
    /**
     * {@inheritdoc}
     */
    public function confirmBatchAction(Request $request)
    {
        $ret = parent::confirmBatchAction($request);
        
        if (is_array($ret)) {
            $ret['ip'] = $request->getClientIp();
        }
        
        return $ret;
    }
    
    /**
     * Looks up for existing file distributions owned by user
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
        
        $this->get(\IServ\CoreBundle\Service\Sudo::class);
        
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
            ->where($qb->expr()->eq('f.user', ':user'))
            ->andWhere($qb->expr()->like('f.title', ':query'))
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
     * Looks up for existing file distributions owned by user
     *
     * @param Request $request
     * @return JsonResponse
     * @Route("filedistribution/lookup/exam", name="fd_filedistribution_lookup_exam", options={"expose": true})
     * @Security("is_granted('PRIV_EXAM')")
     */
    public function lookupExamAction(Request $request)
    {
        if (!file_exists('/var/lib/dpkg/info/iserv-exam.list')) {
            throw $this->createNotFoundException('iserv-exam is currently not installed.');
        }

        $query = $request->get('query');

        if (empty($query)) {
            throw new \RuntimeException('query should not be empty.');
        }

        $this->get(\IServ\CoreBundle\Service\Sudo::class);

        $suggestions = [];
        $home = $this->getUser()->getHome();
        $directory = $home.'/Exam/';

        $directories = Sudo::glob($directory.'*/');

        // add existing directories to suggestions
        foreach ($directories as $directory) {
            $basename = basename($directory);
            if (Sudo::is_dir($directory.'Assignment') && Sudo::is_dir($directory.'Return') && preg_match(sprintf('/^(.*)%s(.*)$/', $query), $basename)) {
                $suggestions[$basename] = [
                    'type' => 'existing',
                    'label' => $basename,
                    'value' => $basename,
                    'extra' => _('Existing exam directory')
                ];
            }
        }

        $em = $this->getDoctrine()->getManager();

        /* @var $qb \Doctrine\ORM\QueryBuilder */
        $qb = $em->createQueryBuilder(self::class);

        // get current file distributions from database
        $qb
            ->select('e')
            ->from('StsblFileDistributionBundle:Exam', 'e')
            ->where($qb->expr()->eq('e.user', ':user'))
            ->andWhere($qb->expr()->like('e.title', ':query'))
            ->setParameter(':user', $this->getUser())
            ->setParameter(':query', '%'.$query.'%')
        ;

        /* @var $results \Stsbl\FileDistributionBundle\Entity\Exam[] */
        $results = $qb->getQuery()->getResult();

        foreach ($results as $result) {
            $suggestions[$result->getTitle()] = [
                'type' => 'running',
                'label' => $result->getTitle(),
                'value' => $result->getTitle(),
                'extra' => _('Running exam')
            ];
        }

        asort($suggestions);

        return new JsonResponse($suggestions);
    }

    /**
     * Looks up current host name
     *
     * @Route("filedistribution/lookup/hostname", name="fd_filedistribution_lookup_hostname", options={"expose": true})
     * @Security("is_granted('PRIV_FILE_DISTRIBUTION') and is_granted('PRIV_COMPUTER_BOOT')")
     * @param Request $request
     * @return JsonResponse
     */
    public function lookupHostNameAction(Request $request)
    {
        /** @var Host $host */
        $host = $this->getDoctrine()->getManager()->getRepository('StsblFileDistributionBundle:Host')->findOneByIp($request->getClientIp());

        $name = null;
        if ($host != null) {
            $name = $host->getName();
        }

        return new JsonResponse($name);
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
        
        $mode = FileDistributionCrud::getRoomMode();

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
            
            // log if mode is changed
            if ($mode !== FileDistributionCrud::getRoomMode()) {
                if ($mode === true) {
                    $text = 'Raumverfügbarkeit geändert auf "Alle, außer den folgenden"';
                } else {
                    $text = 'Raumverfügbarkeit geändert auf "Folgende"';
                }
                $this->get(Logger::class)->writeForModule($text, 'File distribution');
            }
            
            $content = json_encode(['invert' => $mode]);
            
            file_put_contents(self::ROOM_CONFIG_FILE, $content);
            $this->addFlash('success', _('Room settings updated successful.'));
        }
        
        $ret['room_inclusion_form'] = $form->createView();
        
        return $ret;
    }

    /**
     * @return Response
     * @Route("filedistribution/update.js", name="fd_filedistribution_update")
     * @Security("is_granted('PRIV_FILE_DISTRIBUTION') and is_granted('PRIV_COMPUTER_BOOT')")
     */
    public function updateAction()
    {
        $params = [
            'status' => $this->get(HostStatus::class)->getAll(),
        ];

        $render = $this->renderView('StsblFileDistributionBundle:FileDistribution:update.js.twig', $params);
        $response = new Response($render);
        $response->headers->set('Content-Type', 'text/javascript');
        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        $deps = parent::getSubscribedServices();
        $deps[] = \IServ\CoreBundle\Service\Sudo::class;
        $deps[] = HostStatus::class;
        $deps[] = Logger::class;

        return $deps;
    }
}
