<?php

namespace App\Controller;

use App\Entity\CsvImport;
use App\ResourceSpace\ResourceSpace;
use App\Util\HttpUtil;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OmekaSCsvController extends AbstractController
{
    /**
     * @Route("/omeka-s_csv", name="Omeka-S CSV")
     */
    public function new(Request $request) : Response
    {
        set_time_limit(0);
        $csvImport = new CsvImport();
        $form = $this->createFormBuilder($csvImport)
            ->add('file', FileType::class, [ 'label' => 'Omeka-S CSV importbestand' ])
            ->add('submit', SubmitType::class, [ 'label' => 'Query verzenden' ])
            ->getForm();
        $form->handleRequest($request);

        $searchResults = array();
        if($form->isSubmitted() && $form->isValid()) {
            $csvImport = $form->getData();
            $file = $csvImport->getFile();

            $resourceSpace = new ResourceSpace($this->container->get('parameter_bag'));

            $fh = fopen($file, 'r');
            $records = array();
            $header = fgetcsv($fh, 8192);
            $i = 1;
            while(($row = fgetcsv($fh, 8192)) !== false) {
                if(count($header) != count($row)) {
                    echo 'Wrong column count: should be ' . count($header) . ', is ' . count($row) . ' at row ' . $i . PHP_EOL;
                }
                $line = array_combine($header, $row);
                $results = $resourceSpace->findResource(urlencode($line['identifier (MODS)']), '0');
                $url = '';
                foreach($results as $result) {
                    $url = $resourceSpace->getResourcePath($result['ref'], 'scr', 0);
                    if(empty($url) || !HttpUtil::urlExists($url)) {
                        $url = $resourceSpace->getResourcePath($result['ref'], '',  0, $result['file_extension']);
                    }
                    if(!empty($url)) {
                        break;
                    }
                }
                $row[] = $url;
                $records[] = $row;
                $i++;
            }
            $header[] = 'Media';

            $fp = fopen('php://temp', 'w');
            fputcsv($fp, $header);
            foreach($records as $row) {
                fputcsv($fp, $row);
            }
            rewind($fp);
            $response = new Response(stream_get_contents($fp));
            fclose($fp);

            $filename = 'generated_omeka-s_import.csv';

            // Set headers
            $response->headers->set('Content-type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '";');

            return $response;
        }

        return $this->render('csv_import.html.twig', [
            'form' => $form->createView(),
            'search_results' => $searchResults
        ]);
    }
}
