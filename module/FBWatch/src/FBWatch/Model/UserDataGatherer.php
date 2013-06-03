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
    
    public function startFetch() {
        $basicDataQuery = '/' . $this->username;
        try {
            $basic_data = $this->facebook->api($basicDataQuery);
        } catch (\Exception $e) {
            print $e . "1<br>";
        }
        
        if (empty($basic_data)) {
            // TODO
            return;
        }

        $this->initDataFolderFor($basic_data['id']);

        $this->saveBasicData($basic_data);

        return array(
            'basicData' => array(
                'query' => $basicDataQuery,
                'data' => $basic_data
            ),
            'feed' => $this->fetchFeed(),
            'statuses' => $this->fetchStatus()
        );
    }
    
    
    private function fetchStatus() {
        $statusQuery = '/' . $this->username . '?fields=statuses';
        $str = $this->facebook->api($statusQuery);
        
        if (!array_key_exists('statuses', $str)) {
            return array(
                'query' => $statusQuery,
                'statuses' => null
            );
        }
        
        $status[] = $str['statuses'];

        $counter = 0;

        $nextpage = $str['statuses']['paging']['next'];
        parse_str($nextpage);

        print "<br>Next page: $nextpage<br>";
        $lastUntil = null;

        while ($until != null && $lastUntil != $until) {

            if ($limit == null) {
                $limit = 25;
            }

            $str = $this->facebook->api('/' . $this->username . '/statuses?limit=' . $limit . '&until=' . $until);
            $status[] = $str;
            $lastUntil = $until;
            $until = null;

            $nextpage = $str['paging']['next'];
            print "<br>Next page: $nextpage<br>";
            parse_str($nextpage);
        }

        if (count($status) > 0) {
            for ($i = 0; $i < sizeof($status); $i = $i + 1) {
                $handle = fopen($this->savePath . "status$i.json", 'w+');
                fwrite($handle, json_encode($status[$i]));
                fclose($handle);
            }
        }
        
        return array(
            'query' => $statusQuery,
            'statuses' => $status
        );
    }
    
    private function fetchFeed() {
        $feed = array();
        $paging = array('limit' => '', 'until' => '');
        $callHistory = array();
        
        while (true) {
            $fbGraphCall = '/' . $this->username . '/feed?' 
                    . (empty($paging['limit']) ? '' : 'limit=' . $paging['limit'] . '&') 
                    . (empty($paging['until']) ? '' : 'until=' . $paging['until']);
            
            // TODO possibly make more robust
            if (in_array($fbGraphCall, $callHistory)) {
                break;
            }
            
            $result = $this->facebook->api($fbGraphCall);
            $callHistory[] = $fbGraphCall;
            
            if (!array_key_exists('paging', $result)) {
                break;
            }

            $feed[] = $result;

            $paging = array('limit' => '', 'until' => '');
            $nextQuery = substr($result['paging']['next'], strpos($result['paging']['next'], '?') + 1);
            parse_str($nextQuery, $paging);
        }

        if (count($feed) > 0) {
            for ($i = 0; $i < sizeof($feed); $i = $i + 1) {
                $handle = fopen($this->savePath . "feed$i.json", 'w+');
                fwrite($handle, json_encode($feed[$i]));
                fclose($handle);
            }
        }
        
        return array(
            'feed' => $feed,
            'callHistory' => $callHistory
        );
    }
    
    private function saveBasicData($basic_data) {
        $handle = fopen($this->savePath . "basicdata.json", 'w+');
        fwrite($handle, json_encode($basic_data));
        fclose($handle);
    }
    
    private function initDataFolderFor($userId) {
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