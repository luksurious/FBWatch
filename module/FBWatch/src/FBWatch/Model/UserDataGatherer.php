<?php

namespace FBWatch\Model;

class UserDataGatherer {
    private $username;
    private $facebook;
    private $savePath;
    
    public function __construct($username, $facebook) {
        $this->username = $username;
        $this->facebook = $facebook;
    }
    
    public function startFetch() 
    {
        $basicDataQuery = '/' . $this->username;
        $basicData = $this->facebook->api($basicDataQuery);
        
        if (empty($basicData)) {
            // TODO
            return;
        }

        $this->initDataFolderFor($basicData['id']);

        $this->saveBasicData($basicData);

        return array(
            'basicData' => array(
                'query' => $basicDataQuery,
                'data' => $basicData
            ),
            'feed' => $this->fetchData('feed')
//          , 'statuses' => $this->fetchStatus() // it seems that every status is also in the feed
        );
    }
    
    private function fetchData($connection)
    {
        $data = array();
        $callHistory = array();
        $fbGraphCall = "/{$this->username}/{$connection}";
        
        while (true) {
            // TODO possibly make more robust
            // if same query was sent before break
            if (in_array($fbGraphCall, $callHistory)) {
                break;
            }
            
            $result = $this->facebook->api($fbGraphCall);
            $callHistory[] = $fbGraphCall;
            
            if ($this->resultIsEmpty($result)) {
                break;
            }

            $data[] = $result;
            
            $fbGraphCall = $this->createNextQuery(
                    $result['paging']['next'], 
                    $connection
            );
        }

        $this->saveData($data, $connection);
        
        return array(
            'data' => $data,
            'callHistory' => $callHistory
        );
    }
    
    private function saveData($data, $connection) 
    {    
        if (count($data) == 0) {
            return;
        }
        
        for ($i = 0; $i < sizeof($data); $i = $i + 1) {
            $handle = fopen("{$this->savePath}{$connection}{$i}.json", 'w+');
            fwrite($handle, json_encode($data[$i]));
            fclose($handle);
        }
    }
    
    private function resultIsEmpty($result) 
    {
        // if no paging array is present the return object is 
        // presumably empty
        return false === array_key_exists('paging', $result);
    }
    
    private function createNextQuery($next, $connection) 
    {
        $params = array('limit' => '', 'until' => '');
        
        $nextQuery = substr($next, strpos($next, '?') + 1);
        parse_str($nextQuery, $params);
        
        return "/{$this->username}/{$connection}?" 
                . (empty($params['limit']) ? '' : 'limit=' . $params['limit'] . '&') 
                . (empty($params['until']) ? '' : 'until=' . $params['until']);
    }
    
    private function saveBasicData($basicData)
    {
        $handle = fopen($this->savePath . "basicdata.json", 'w+');
        fwrite($handle, json_encode($basicData));
        fclose($handle);
    }
    
    private function initDataFolderFor($userId) 
    {
        $user = $this->facebook->getUser();
        $savePath = 'data/fbwatch/' . $user . '/';

        if (!file_exists($savePath)) {
            mkdir($savePath);
        }

        $savePath = $savePath . "$userId/";

        if (!file_exists($savePath)) {
            mkdir($savePath);
        }
        
        $this->savePath = $savePath;
    }
}