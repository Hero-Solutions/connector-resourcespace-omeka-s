<?php

namespace App\ResourceSpace;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ResourceSpace
{
    private $apiUrl;
    private $apiUsername;
    private $apiKey;

    public function __construct(ParameterBagInterface $params)
    {
        $resourceSpaceApi = $params->get('resourcespace_api');
        $this->apiUrl = $resourceSpaceApi['url'];
        $this->apiUsername = $resourceSpaceApi['username'];
        $this->apiKey = $resourceSpaceApi['key'];
    }

    public function findResourceWithId($id, $pendingReview)
    {
        $data = $this->doApiCall('do_search&param1=' . $id . '&param4=' . $pendingReview);
        return json_decode($data, true);
    }

    public function searchGetPreviews($inventoryNumber, $previews)
    {
        $data = $this->doApiCall('do_search&param1=inventorynumber:' . $inventoryNumber . '&param8=' . $previews);
        return json_decode($data, true);
    }

    public function getResourcePath($id, $type)
    {
        $data = $this->doApiCall('get_resource_path&param1=' . $id . '&param2=0&param3=' . $type);
        return json_decode($data, true);
    }

    private function doApiCall($query)
    {
        $query = 'user=' . $this->apiUsername . '&function=' . $query;
        $url = $this->apiUrl . '?' . $query . '&sign=' . $this->getSign($query);
        $data = file_get_contents($url);
        return $data;
    }

    private function getSign($query)
    {
        return hash('sha256', $this->apiKey . $query);
    }
}
