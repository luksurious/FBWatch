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
            'feed' => $this->fetchFeed()
//          , 'statuses' => $this->fetchStatus() // it seems that every status is also in the feed
        );
    }
    
    
    private function fetchStatus() 
    {
        $status = array();
        $callHistory = array();
        $fbGraphCall = '/' . $this->username . '/statuses';
        
        while (true) {
            // TODO possibly make more robust
            // if same query was sent before break
            if (in_array($fbGraphCall, $callHistory)) {
                break;
            }
            
            $result = $this->facebook->api($fbGraphCall);
            $callHistory[] = $fbGraphCall;
            
            if ($this->statusResultIsEmpty($result)) {
                break;
            }

            $status[] = $result;
            
            $fbGraphCall = $this->createNextStatusQuery($result['paging']['next']);
        }

        $this->saveStatuses($status);
        
        return array(
            'callHistory' => $callHistory,
            'statuses' => $status
        );
    }
    
    private function saveStatuses($status) 
    {
    
        if (count($status) == 0) {
            return;
        }
        
        for ($i = 0; $i < sizeof($status); $i = $i + 1) {
            $handle = fopen($this->savePath . "status$i.json", 'w+');
            fwrite($handle, json_encode($status[$i]));
            fclose($handle);
        }
    }
    
    private function statusResultIsEmpty($result) 
    {
        // if no paging array is present the return object is 
        // presumably empty
        return !array_key_exists('paging', $result);
    }
    
    private function createNextStatusQuery($next) 
    {
        $params = array('limit' => '', 'until' => '');
        
        $nextQuery = substr($next, strpos($next, '?') + 1);
        parse_str($nextQuery, $params);
        
        return '/' . $this->username . '/statuses?' 
                . (empty($params['limit']) ? '' : 'limit=' . $params['limit'] . '&') 
                . (empty($params['until']) ? '' : 'until=' . $params['until']);
        
    }
    
    private function fetchFeed()
    {
        $feed = array();
        $callHistory = array();
        $fbGraphCall = '/' . $this->username . '/feed';
        
        while (true) {
            // TODO possibly make more robust
            // if same query was sent before break
            if (in_array($fbGraphCall, $callHistory)) {
                break;
            }
            
            $result = $this->facebook->api($fbGraphCall);
            $callHistory[] = $fbGraphCall;
            
            if ($this->feedResultIsEmpty($result)) {
                break;
            }

            $feed[] = $result;
            
            $fbGraphCall = $this->createNextFeedQuery($result['paging']['next']);
        }

        $this->saveFeed($feed);
        
        return array(
            'feed' => $feed,
            'callHistory' => $callHistory
        );
    }
    
    private function saveFeed($feed) 
    {    
        if (count($feed) == 0) {
            return;
        }
        
        for ($i = 0; $i < sizeof($feed); $i = $i + 1) {
            $handle = fopen($this->savePath . "feed$i.json", 'w+');
            fwrite($handle, json_encode($feed[$i]));
            fclose($handle);
        }
    }
    
    private function feedResultIsEmpty($result) 
    {
        // if no paging array is present the return object is 
        // presumably empty
        return false === array_key_exists('paging', $result);
    }
    
    private function createNextFeedQuery($next) 
    {
        $params = array('limit' => '', 'until' => '');
        
        $nextQuery = substr($next, strpos($next, '?') + 1);
        parse_str($nextQuery, $params);
        
        return '/' . $this->username . '/feed?' 
                . (empty($params['limit']) ? '' : 'limit=' . $params['limit'] . '&') 
                . (empty($params['until']) ? '' : 'until=' . $params['until']);
    }
    
    private function saveBasicData($basic_data) 
    {
        $handle = fopen($this->savePath . "basicdata.json", 'w+');
        fwrite($handle, json_encode($basic_data));
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