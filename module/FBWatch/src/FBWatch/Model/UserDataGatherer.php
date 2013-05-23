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
        try {
            $basic_data = $this->facebook->api('/' . $this->username);
        } catch (\Exception $e) {
            print $e . "1<br>";
        }
        
        if (empty($basic_data)) {
            // TODO
            return;
        }

        $this->initDataFolderFor($basic_data['id']);

        $this->saveBasicData($basic_data);

        $this->fetchFeed();

        $this->fetchStatus();
    }
    
    
    private function fetchStatus() {
        try {
            $str = $this->facebook->api('/' . $this->username . '?fields=statuses');
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
        } catch (\Exception $e) {
            print $e . ' in ' . __METHOD__;
        }
    }
    
    private function fetchFeed() {
        $feed = array();
        $paging = array('limit' => '', 'until' => '');
        $callHistory = array();
        
        try {
            while (true) {
                $fbGraphCall = '/' . $this->username . '/feed?' 
                        . (empty($paging['limit']) ? '' : 'limit=' . $paging['limit'] . '&') 
                        . (empty($paging['until']) ? '' : 'until=' . $paging['until']);
                $result = $this->facebook->api($fbGraphCall);
                
                if (!array_key_exists('paging', $result) 
                        || in_array($fbGraphCall, $callHistory)) {
                    break;
                }
                
                // TODO possibly make more robust
                $callHistory[] = $fbGraphCall;
                
                $feed[] = $result;
                
                $paging = array('limit' => '', 'until' => '');
                $nextQuery = substr($result['paging']['next'], strpos($result['paging']['next'], '?') + 1);
                parse_str($nextQuery, $paging);
            }
        } catch (\FacebookApiException $e) {
            print $e . "3<br>";
        }
            
        var_dump($callHistory);

        if (count($feed) > 0) {
            for ($i = 0; $i < sizeof($feed); $i = $i + 1) {
                $handle = fopen($this->savePath . "feed$i.json", 'w+');
                fwrite($handle, json_encode($feed[$i]));
                fclose($handle);
            }
        }
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