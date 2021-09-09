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

    public function getApiUsername()
    {
        return $this->apiUsername;
    }

    public function setApiUsername($apiUsername)
    {
        $this->apiUsername = $apiUsername;
    }

    public function getApiKey()
    {
        return $this->apiKey;
    }

    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function findResource($searchTerm, $pendingReview)
    {
        $data = $this->doApiCall("do_search&search=%22" . urlencode($searchTerm) . "%22&archive=" . urlencode($pendingReview));
        return json_decode($data, true);
    }

    public function getResourceMetadata($ref)
    {
        $rawResourceMetadata = $this->getRawResourceFieldData($ref);
        return $this->getResourceFieldDataAsAssocArray($rawResourceMetadata);
    }

    public function getResourceFieldDataAsAssocArray($data)
    {
        $result = array();
        foreach ($data as $field) {
            $result[$field['name']] = $field['value'];
        }
        return $result;
    }

    public function getRawResourceFieldData($id)
    {
        $data = $this->doApiCall('get_resource_field_data&param1=' . $id);
        return json_decode($data, true);
    }

    public function getResourcePath($id, $type, $filePath, $extension = '')
    {
        $data = $this->doApiCall('get_resource_path&param1=' . $id . '&param2=' . $filePath . '&param3=' . $type . '&param5=' . $extension);
        return json_decode($data, true);
    }

    private function doApiCall($query)
    {
        $query = "user=" . $this->apiUsername . "&function=" . $query;
        $url = $this->apiUrl . "?" . $query . "&sign=" . $this->getSign($query);
        $data = file_get_contents($url);
        return $data;
    }

    private function getSign($query)
    {
        return hash('sha256', $this->apiKey . $query);
    }
}
