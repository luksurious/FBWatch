<?php

namespace FBWatch\Model;

class Resource {
    public $id;
    public $resourceName;
    public $facebookId;
    public $lastSynced;
    public $active;
    
    public function exchangeArray($data)
    {
        $this->id           = (!empty($data['id'])) ? (int) $data['id'] : null;
        $this->resourceName = (!empty($data['resourceName'])) ? $data['resourceName'] : null;
        $this->facebookId   = (!empty($data['facebookId'])) ? $data['facebookId'] : null;
        $this->lastSynced   = (!empty($data['lastSynced'])) ? $data['lastSynced'] : null;
        $this->active       = (!empty($data['active'])) ? (bool) $data['active'] : null;
    }
}