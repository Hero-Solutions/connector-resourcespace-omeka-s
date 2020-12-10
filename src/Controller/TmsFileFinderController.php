<?php

namespace App\Controller;

use App\Entity\Search;
use App\ResourceSpace\ResourceSpace;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
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
        $search = new Search();
        $form = $this->createFormBuilder($search)
            ->add('id', TextType::class, [ 'label' => 'Zoekopdracht' ])
            ->add('pending', CheckboxType::class, [ 'required' => false ])
            ->add('submit', SubmitType::class, [ 'label' => 'Query verzenden' ])
            ->getForm();
        $form->handleRequest($request);
        $searchResults = array();
        if($form->isSubmitted() && $form->isValid()) {
            $search = $form->getData();
            $resourceSpace = new ResourceSpace($this->container->get('parameter_bag'));
            $results = $resourceSpace->findResourceWithId($search->getId(), $search->getPending());
            foreach($results as $result) {
                $searchResults[] = array(
                    'resource_id' => $result['ref'],
                    'original_filename' => $result['field51'],
                    'inventory_number' => $result['field105'],
                    'creator' => $result['field89'],
                    'artwork_creator' => $result['field96'],
                    'file_path_thumbnail' => $resourceSpace->getResourceThmUrl($result['ref'], 'thm'),
                    'file_path_screen' => $resourceSpace->getResourceThmUrl($result['ref'], 'src'),
                    'file_path_original' => $resourceSpace->getResourceThmUrl($result['ref'], ''),
                );
            }
        }

        return $this->render('search.html.twig', [
            'form' => $form->createView(),
            'search_results' => $searchResults
        ]);
    }
}