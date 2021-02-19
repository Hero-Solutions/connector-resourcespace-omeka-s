<?php

namespace App\Controller;

use App\Entity\CsvImport;
use App\ResourceSpace\ResourceSpace;
use App\Util\HttpUtil;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ResourceSpaceCsvController extends AbstractController
{
    /**
     * @Route("/resourcespace_csv", name="ResourceSpace CSV")
     */
    public function new(Request $request) : Response
    {
        set_time_limit(0);
        $csvImport = new CsvImport();
        $params = $this->container->get('parameter_bag');
        $form = $this->createFormBuilder($csvImport)
            ->add('imageType', ChoiceType::class, [ 'label' => 'Gewenst afbeeldingstype', 'choices' => $params->get('image_types') ])
            ->add('extraColumns', ChoiceType::class, [ 'label' => 'Extra rij/kolom per afbeelding:', 'expanded' => true, 'multiple' => false, 'choices' => [ 'Extra rij' => false, 'Extra kolom' => true ]])
            ->add('imageCount', ChoiceType::class, [ 'label' => false, 'expanded' => true, 'multiple' => true, 'choices' => [ 'Tel aantal resultaten (label "Matches")' => true ]])
            ->add('extraInfo', ChoiceType::class, [ 'label' => 'Voeg extra ResourceSpace velden toe:', 'expanded' => true, 'multiple' => true, 'choices' => array_merge(['ResourceSpace ID' => 'resourcespace_id'], $params->get('csv_fields'))])
            ->add('file', FileType::class, [ 'label' => 'CSV importbestand' ])
            ->add('submit', SubmitType::class, [ 'label' => 'Query verzenden', 'attr' => [ 'onClick' => 'saveCookie()'] ])
            ->getForm();
        $form->handleRequest($request);

        $searchResults = array();
        if($form->isSubmitted() && $form->isValid()) {
            $csvImport = $form->getData();
            $file = $csvImport->getFile();
            $imageType = $csvImport->getImageType();
            $extraColumns = $csvImport->getExtraColumns();
            $imageCount = $csvImport->getImageCount();
            $extraInfo = $csvImport->getExtraInfo();
            $resourceSpace = new ResourceSpace($params);
            $omekaSCsvFields = $params->get('omeka_s_csv_fields');

            $fh = fopen($file, 'r');
            $records = array();
            $header = fgetcsv($fh, 8192);
            $i = 1;
            $maxCount = 1;
            while(($row = fgetcsv($fh, 8192)) !== false) {
                if(count($header) != count($row)) {
                    echo 'Wrong column count: should be ' . count($header) . ', is ' . count($row) . ' at row ' . $i . PHP_EOL;
                }
                $line = array_combine($header, $row);
                $fileUrls = array();
                $metadata = array();
                foreach ($omekaSCsvFields as $columnName => $resourceSpaceName) {
                    if(array_key_exists($columnName, $line)) {
                        $results = $resourceSpace->findResource($resourceSpaceName . ':' . $line[$columnName], '0');
                        foreach ($results as $result) {
                            $data = array();
                            $resourceData = null;
                            foreach($extraInfo as $field) {
                                if($field == 'resourcespace_id') {
                                    $data[$field] = $result['ref'];
                                } else {
                                    if($resourceData == null) {
                                        $resourceData = $resourceSpace->getResourceMetadata($result['ref']);
                                    }
                                    if (array_key_exists($field, $resourceData)) {
                                        $data[$field] = $resourceData[$field];
                                    } else {
                                        $data[$field] = '';
                                    }
                                }
                            }
                            $metadata[] = $data;
                            $fileUrl = $resourceSpace->getResourcePath($result['ref'], $imageType, 0);
                            if (empty($fileUrl) || !HttpUtil::urlExists($fileUrl)) {
                                $fileUrl = $resourceSpace->getResourcePath($result['ref'], '', 0, $result['file_extension']);
                            }
                            if (!empty($fileUrl)) {
                                $fileUrls[] = $fileUrl;
                            } else {
                                $fileUrls[] = '';
                            }
                        }
                        if (!empty($results)) {
                            break;
                        }
                    }
                }
                if($imageCount) {
                    $row[] = count($fileUrls);
                }
                if(empty($fileUrls)) {
                    foreach($extraInfo as $field) {
                        $row[] = '';
                    }
                    $row[] = '';
                    $records[] = $row;
                } else if(count($fileUrls) == 1) {
                    foreach($extraInfo as $field) {
                        if(array_key_exists($field, $metadata[0])) {
                            $row[] = $metadata[0][$field];
                        } else {
                            $row[] = '';
                        }
                    }
                    $row[] = $fileUrls[0];
                    $records[] = $row;
                } else if($extraColumns) {
                    if(count($fileUrls) > $maxCount) {
                        $maxCount = count($fileUrls);
                    }
                    foreach($extraInfo as $field) {
                        if(array_key_exists($field, $metadata[0])) {
                            $row[] = $metadata[0][$field];
                        } else {
                            $row[] = '';
                        }
                    }
                    foreach ($fileUrls as $fileUrl) {
                        $row[] = $fileUrl;
                    }
                    $records[] = $row;
                } else {
                    for ($i = 0; $i < count($fileUrls); $i++) {
                        $arr = array();
                        foreach($extraInfo as $field) {
                            if(array_key_exists($field, $metadata[$i])) {
                                $arr[] = $metadata[$i][$field];
                            } else {
                                $arr[] = '';
                            }
                        }
                        $arr[] = $fileUrls[$i];
                        $records[] = array_merge($row, $arr);
                    }
                }
                $i++;
            }
            if($imageCount) {
                $header[] = 'Matches';
            }
            if(in_array('resourcespace_id', $extraInfo)) {
                $header[] = 'ResourceSpace ID';
            }
            foreach($params->get('csv_fields') as $key => $value) {
                if(in_array($value, $extraInfo)) {
                    $header[] = $key;
                }
            }
            $header[] = 'Media';
            if($extraColumns) {
                for ($i = 0; $i < $maxCount - 1; $i++) {
                    $header[] = 'Media' . ($i + 2);
                }
            }

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
