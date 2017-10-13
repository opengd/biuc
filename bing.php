<?php

namespace Bing;

require 'HTTP/Request2.php';

use \HTTP\Request2;

class ImageSearch {

    private $azureUrl = 'https://api.cognitive.microsoft.com/bing/v5.0/images/search';
    private $key = 'YOUR_SERVICE_BING_KEY';

    function search($searchParameters) 
    {
        if(!$searchParameters instanceof ImageSearchParameters)
            die('$searchParameters is not a instance of Bing\ImageSearchParameters');

        $request = new \Http_Request2($this->azureUrl);

        $url = $request->getUrl();

        $request->setHeader(['Ocp-Apim-Subscription-Key' => $this->key]);

        $url->setQueryVariables($searchParameters->toArray());

        $request->setMethod(\HTTP_Request2::METHOD_GET);
        // Request body
        $request->setBody("{body}");

        try
        {
            $response = $request->send();
            $json_response = json_decode($response->getBody());
            //echo serialize($json_response->value[0]->contentUrl);
        }
        catch (HttpException $ex)
        {
            //echo $ex;
        }

        return $json_response ?: false;
    }
}

class ImageSearchParameters {

    public $q;
    public $count;
    public $offset;
    public $mkt;
    public $safeSearch;

    function __construct(array $config = [])
    {
        $this->q = isset($config['q']) ? $config['q'] : '';
        $this->count = isset($config['count']) ? $config['count'] : null;
        $this->offset = isset($config['offset']) ? $config['offset'] : null;
        $this->mkt = isset($config['mkt']) ? $config['mkt'] : null;
        $this->safeSearch = isset($config['safeSearch']) ? $config['safeSearch'] : null;
    }

    function toArray()
    {
        $retArray = ['q' => $this->q];

        if(!is_null($this->count))
            $retArray['count'] = $this->count;
        if(!is_null($this->offset))
            $retArray['offset'] = $this->offset;
        if(!is_null($this->mkt))
            $retArray['mkt'] = $this->mkt;
        if(!is_null($this->safeSearch))
            $retArray['safeSearch'] = $this->safeSearch;

        return $retArray;
    }
}