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

        $data = array(
            'basicData' => array(
                'query' => $basicDataQuery,
                'data' => $basicData
            ),
            'feed' => $this->fetchData('feed')
        );
        
        $this->saveData($data);
        
        return $data;
    }
    
    private function fetchData($connection)
    {
        $data = array();
        $callHistory = array();
        $previousLink = '';
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
            
            // save this link so that we can get only updates next time
            if (empty($previousLink)) {
                $previousLink = $result['paging']['previous'];
            }

            $data[] = $result;
            
            $fbGraphCall = $this->createNextQuery(
                    $result['paging']['next'], 
                    $connection
            );
        }
        
        return array(
            'data' => $data,
            'callHistory' => $callHistory,
            'previousLink' => $previousLink
        );
    }
    
    private function saveData($data) 
    {
        $handle = fopen("{$this->savePath}combined.json", 'w+');
        fwrite($handle, json_encode($data));
        fclose($handle);
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