<?php

namespace Biuc;

require 'bing.php';
require 'db.php';
require 'settings.php';
use Bing;
use Db;

class Biuc {

    private $settings;    
    private $con;

    function __construct(array $settings) {
        $this->settings = $settings;
    }

    function run($query)
    {
        $this->con = $this->openBiucDb();

        $clean_query = $this->con->escapeString($query);
        $query_b64 = base64_encode($clean_query);

        $ret = $this->getUrlFromCacheByName($query_b64);

        if($ret && $ret['local']) {            
            $url = "{$this->settings['server']['host']}{$this->settings['server']['root']}/{$this->settings['storage']['path']}{$ret['name']}{$this->settings['storage']['ext']}";

            $ret_json = json_encode(['url' => $url, 'source' => 'local']);
        } elseif($ret['url'] != "" && !$this->is_404($ret['url'])) {
            $ret_json = json_encode(['url' => $ret['url'], 'source' => 'cache']);
        } else { 
            $json_response = (new Bing\ImageSearch)->search(new Bing\ImageSearchParameters(['q' => $query, 'count' => '10']));
            if($json_response && isset($json_response->value) && count($json_response->value) > 0) {
                
                $response_counter = 0;
                while(!$ret_json && $response_counter < count($json_response->value)) {

                    $res_url = $this->parseBingResponseUrl($json_response->value[$response_counter]->contentUrl);

                    if($res_url != null && !$this->is_404($res_url)) {
                        if($ret)
                            $this->updateUrlInCache($clean_query, $res_url, $query_b64);
                        else
                            $this->addUrlToCache($clean_query, $res_url, $query_b64); 

                        $ret_json = json_encode(['url' => $res_url, 'source' => 'bing']);
                    }

                    $response_counter++;
                }

            }
        }

        $this->con->close();
        unset($this->con);

        return $ret_json ?: json_encode(['error' => 'No image could be found for query ' . $clean_query]);
    }

    function getUrlFromCacheByName($name)
    {
        if(!$this->con)
            $this->con = $this->openBiucDb();

        $sql = "SELECT * FROM {$this->settings['db']['table']} where name = '$name' LIMIT 1";

        $result = $this->con->runQuery($sql);

        if($result) {
            $ret = $result->fetch_assoc();
            $result->free();
        }

        return $ret ?: false;
    }

    function addUrlToCache($query, $url, $name)
    {
        if(!$this->con)
            $this->con = $this->openBiucDb();

        $sql = "INSERT INTO {$this->settings['db']['table']} set query = '$query', url = '$url', name = '$name'";

        $result = $this->con->runQuery($sql);
    }

    function updateUrlInCache($query, $url, $name)
    {
        if(!$this->con)
            $this->con = $this->openBiucDb();

        $sql = "UPDATE {$this->settings['db']['table']} set url = '$url' WHERE name = '$name'";

        $result = $this->con->runQuery($sql);
    }

    function openBiucDb()
    {
        $con = new Db\Connection ($this->settings['db']);

        $con->open();

        return $con;
    }

    function is_404($url) {
        $handle = curl_init($url);
        curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);

        /* Get the HTML or whatever is linked in $url. */
        $response = curl_exec($handle);

        /* Check for 404 (file not found). */
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        /* If the document has loaded successfully without any redirection or error */
        if ($httpCode >= 200 && $httpCode < 300) {
            return false;
        } else {
            return true;
        }
    }

    function parseBingResponseUrl($bingresponseurl) {
        
        $p_url = parse_url($bingresponseurl);
        
        if($p_url && isset($p_url['query'])) {
            parse_str($p_url['query'], $query_url);

            if($query_url && isset($query_url['r']))
                return $query_url['r'];
        }

        return null;
    }
}

if(!isset($_GET['q']) || !isset($_GET['h']))
    die(json_encode(['error' => 'Query is wrong']));
elseif ($_GET['h'] != md5($_GET['q'] . '8Z0dV%%xCy6531o^AmcbS6*2kpDyctAV'))
    die(json_encode(['error' => 'h Query is wrong']));

echo (new Biuc($settings))->run(strtolower($_GET['q']));