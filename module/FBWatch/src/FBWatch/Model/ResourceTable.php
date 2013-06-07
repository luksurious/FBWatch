<?php
namespace FBWatch\Model;

use Zend\Db\TableGateway\TableGateway;

class ResourceTable
{
    protected $tableGateway;

    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function fetchAll()
    {
        $resultSet = $this->tableGateway->select();
        return $resultSet;
    }
    
    public function getResource($id)
    {
        $id = (int) $id;
        $rowset = $this->tableGateway->select(array('id' => $id));
        $row = $rowset->current();
        if (!$row) {
            throw new \Exception("Could not find row {$id}");
        }
        return $row;
    }
    
    public function saveResource(Resource $resource)
    {
        $data = array(
            'resourceName' => $resource->resourceName,
            'facebookId' => $resource->facebookId,
            'active' => $resource->active
        );
        
        $id = (int) $resource->id;
        if (0 == $id) {
            $this->tableGateway->insert($data);
        } else {
            if ($this->getResource($id)) {
                $this->tableGateway->update($data, array('id' => $id));
            } else {
                throw new \Exception('Resource with specified id does not exist');
            }
        }
    }
    
    public function deleteResource($id)
    {
        $this->tableGateway->delete(array('id' => $id));
    }
}