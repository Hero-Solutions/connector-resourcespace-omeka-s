<?php

namespace App\Controller;

use App\Entity\Search;
use App\ResourceSpace\ResourceSpace;
use App\Util\HttpUtil;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TmsFileFinderController extends AbstractController
{
    /**
     * @Route("/tms_filefinder.php")
     * @Route("/tms_filefinder", name="tms file finder")
     */
    public function new(Request $request) : Response
    {
        set_time_limit(0);
        $params = $this->container->get('parameter_bag');
        $search = new Search();
        $form = $this->createFormBuilder($search, [ 'attr' => ['id' => 'tms_filefinder_form' ]])
            ->add('username', TextType::class, [ 'label' => 'ResourceSpace gebruikersnaam', 'data' => $this->getParameter('resourcespace_api')['username']])
            ->add('key', TextType::class, [ 'label' => 'ResourceSpace API key', 'required' => false, 'empty_data' => '', 'attr' => [ 'placeholder' => 'Leeg laten voor default username & API key', 'autocomplete' => 'off' ]])
            ->add('input', TextType::class, [ 'label' => 'Zoekopdracht' ])
            ->add('field', ChoiceType::class, [ 'label' => 'Veld', 'choices' => array_merge(['Alle velden' => '@all_fields'], $params->get('tms_filefinder_fields'))])
            ->add('submit', SubmitType::class, [ 'label' => 'Query verzenden' ])
            ->getForm();
        $form->handleRequest($request);
        $maxResults = $this->getParameter('filefinder_max_results');

        $message = '';
        $tooMany = false;

        $searchResults = array();
        if($form->isSubmitted() && $form->isValid()) {
            $search = $form->getData();
            $resourceSpace = new ResourceSpace($params);
            if($search->getUsername() != $resourceSpace->getApiUsername()) {
                $resourceSpace->setApiUsername($search->getUsername());
            }
            if(!empty($search->getKey())) {
                $resourceSpace->setApiKey($search->getKey());
            }
            $pending = $params->get('tms_filefinder_pending');
             if(!empty($search->getInput())) {
                if ($search->getField() == '@all_fields') {
                    $resources = $resourceSpace->findResource($search->getInput(), $pending);
                } else {
                    $resources = $resourceSpace->findResource($search->getField() . ':' . $search->getInput(), $pending);
                }
                $confirm = false;
                if($request->get('confirm') != null) {
                    if(!empty($request->get('confirm'))) {
                        $confirm = true;
                    }
                }
                $totalResults = count($resources);
                if(empty($totalResults)) {
                    $message = 'Geen resultaten gevonden.';
                } else if(!$confirm && $totalResults >= $maxResults) {
                    $message = $totalResults . ' resultaten gevonden, deze hoeveelheid resultaten zal de zoekopdracht sterk vertragen.';
                    $tooMany = true;
                } else {
                    $message = $totalResults . ' resultaten';
                    foreach ($resources as $resource) {
                        $screenUrl = $resourceSpace->getResourcePath($resource['ref'], 'scr', 0);
                        $screenPath = '';
                        if (!empty($screenUrl)) {
                            if (!HttpUtil::urlExists($screenUrl)) {
                                $screenUrl = '';
                            } else {
                                $screenPath = substr($screenUrl, strpos($screenUrl, '/filestore'));
                            }
                        }
                        $originalUrl = $resourceSpace->getResourcePath($resource['ref'], '', 0, $resource['file_extension']);
                        $originalPath = '';
                        if (!empty($originalUrl)) {
                            if (!HttpUtil::urlExists($originalUrl)) {
                                $originalUrl = '';
                            } else {
                                $originalPath = substr($originalUrl, strpos($originalUrl, '/filestore'));
                            }
                        }

                        $searchResults[] = array(
                            'resource_id' => $resource['ref'],
                            'resource' => $resourceSpace->getResourceMetadata($resource['ref']),
                            'file_path_thumbnail' => $resourceSpace->getResourcePath($resource['ref'], 'thm', 0),
                            'file_url_screen' => $screenUrl,
                            'file_path_screen' => $screenPath,
                            'file_url_original' => $originalUrl,
                            'file_path_original' => $originalPath
                        );
                    }
                }
            }
        }

        return $this->render('finder.html.twig', [
            'form' => $form->createView(),
            'message' => $message,
            'too_many' => $tooMany,
            'search_results' => $searchResults
        ]);
    }
}
