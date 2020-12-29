<?php

namespace App\Controller;

use App\Entity\Search;
use App\ResourceSpace\ResourceSpace;
use App\Util\HttpUtil;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
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
        $form = $this->createFormBuilder($search)
            ->add('input', TextType::class, [ 'label' => 'Zoekopdracht' ])
            ->add('field', ChoiceType::class, [ 'label' => 'Veld', 'choices' => array_merge(['Alle velden' => '@all_fields'], $params->get('tms_filefinder_fields'))])
            ->add('submit', SubmitType::class, [ 'label' => 'Query verzenden' ])
            ->getForm();
        $form->handleRequest($request);

        $searchResults = array();
        if($form->isSubmitted() && $form->isValid()) {
            $search = $form->getData();
            $resourceSpace = new ResourceSpace($params);
            $pending = $params->get('tms_filefinder_pending');
            if($search->getField() == '@all_fields') {
                $resources = $resourceSpace->findResource($search->getInput(), $pending);
            } else {
                $resources = $resourceSpace->findResource( $search->getField() . ':' . $search->getInput(), $pending);
            }
            foreach($resources as $resource) {
                $screenUrl = $resourceSpace->getResourcePath($resource['ref'], 'scr', 0);
                $screenPath = '';
                if(!empty($screenUrl)) {
                    if(!HttpUtil::urlExists($screenUrl)) {
                        $screenUrl = '';
                    } else {
                        $screenPath = substr($screenUrl, strpos($screenUrl, '/filestore'));
                    }
                }
                $originalUrl = $resourceSpace->getResourcePath($resource['ref'], '', 0, $resource['file_extension']);
                $originalPath = '';
                if(!empty($originalUrl)) {
                    if(!HttpUtil::urlExists($originalUrl)) {
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

        return $this->render('finder.html.twig', [
            'form' => $form->createView(),
            'search_results' => $searchResults
        ]);
    }
}
