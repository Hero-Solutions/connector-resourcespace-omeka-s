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
        $search = new Search();
        $form = $this->createFormBuilder($search)
            ->add('id', TextType::class, [ 'label' => 'Zoekopdracht' ])
/*            ->add('pending', ChoiceType::class, [ 'choices' => [
                    'Live (default)' => 0,
                    'Pending archive' => 1,
                    'Archived' => 2,
                    'Deleted' => 3,
                    'Pending review' => -1,
                    'Pending submission' => -2
                ]
            ])*/
            ->add('submit', SubmitType::class, [ 'label' => 'Query verzenden' ])
            ->getForm();
        $form->handleRequest($request);

        $searchResults = array();
        if($form->isSubmitted() && $form->isValid()) {
            $search = $form->getData();
            $resourceSpace = new ResourceSpace($this->container->get('parameter_bag'));
            $results = $resourceSpace->findResourceWithId($search->getId(), '0,1,2,3,-1,-2');
//            $results = $resourceSpace->findResourceWithId($search->getId(), $search->getPending());
            foreach($results as $result) {
                $screenUrl = $resourceSpace->getResourcePath($result['ref'], 'scr');
                if(!empty($screenUrl)) {
                    if(!HttpUtil::urlExists($screenUrl)) {
                        $screenUrl = '';
                    }
                }
                $originalUrl = $resourceSpace->getResourcePath($result['ref'], '', $result['file_extension']);
                if(!empty($originalUrl)) {
                    if(!HttpUtil::urlExists($originalUrl)) {
                        $originalUrl = '';
                    }
                }

                $searchResults[] = array(
                    'resource_id' => $result['ref'],
                    'original_filename' => $result['field51'],
                    'inventory_number' => $result['field105'],
                    'creator' => $result['field89'],
                    'artwork_creator' => $result['field96'],
                    'file_path_thumbnail' => $resourceSpace->getResourcePath($result['ref'], 'thm'),
                    'file_path_screen' => $screenUrl,
                    'file_path_original' => $originalUrl
                );
            }
        }

        return $this->render('finder.html.twig', [
            'form' => $form->createView(),
            'search_results' => $searchResults
        ]);
    }
}
