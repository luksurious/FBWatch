<?php
namespace FBWatch\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use FBWatch\Model\UserDataGatherer;
use FBWatch\Model\Resource;
use \Facebook;

class FBWatchController extends AbstractActionController
{
    public $facebook;
    
    protected $resourceTable;
    
    public function __construct() 
    {    
        $appId = '558538167531777';
	$this->facebook = new Facebook(array(
		'appId'  => $appId,
		'secret' => '9383f8e11829a80f740d43a995172ef1',
		'cookie' => true
        ));
        Facebook::$CURL_OPTS[CURLOPT_CONNECTTIMEOUT] = 30;
    }
    
    private function assertLoggedIn()
    {
        $container = new Container('fbwatch');
        $code = $this->params()->fromQuery('code');
        
        if (empty($container->authCode) && empty($code)) {
            $user = $this->facebook->getUser();

            if (0 == $user) {
                return $this->redirect()->toRoute('fbwatch', array('action' => 'login'));
            }
        }
        
        if (!empty($code)) {
            $container->authCode = $code;
            $this->facebook->setAccessToken($container->authCode);
        }
    }
    
    public function indexAction()
    {
        $this->assertLoggedIn();
        
        return new ViewModel(array(
            'resources' => $this->getResourceTable()->fetchAll()
        ));
    }
    
    /**
     * 
     * @return \FBWatch\Model\ResourceTable
     */
    public function getResourceTable()
    {
        if (!$this->resourceTable) {
            $sm = $this->getServiceLocator();
            $this->resourceTable = $sm->get('FBWatch\Model\ResourceTable');
        }
        return $this->resourceTable;
    }
    
    public function addAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = $request->getPost();
            
            $resource = new Resource();
            $resource->resourceName = $this->parseFacebookUrl($data['resource-url']);
            $resource->active = true;
            $this->getResourceTable()->saveResource($resource);
        }
        // TODO add some message / user feedback
        
        return $this->redirect()->toRoute('fbwatch');
    }
    
    private function parseFacebookUrl($url)
    {
        $parts = parse_url($url);
        
        // the path of the facebook url holds either the unique name or the facebook id
        $path = substr($parts['path'], 1);
        $path = explode('/', $path);
        
        // if it's a page or group the id is in the last "folder"
        // otherwise this will just return the unique name
        return $path[ count($path) - 1];
    }

    public function loginAction()
    {
        return new ViewModel(array(
            'login_url' => $this->facebook->getLoginUrl(array(
                'redirect_uri' => 'http://fbwatch.lukas-brueckner.de',
                'scope' => 'email, user_about_me, user_groups, user_interests, user_subscriptions, user_status, read_stream'
            ))
        ));
    }

    public function syncAction()
    {
        $this->assertLoggedIn();
        
        $id = (int) $this->params()->fromRoute('id', 0);
        if (!$id) {
            return $this->redirect()->toRoute('fbwatch');
        }
        
        $resource = $this->getResourceTable()->getResource($id);
        
        if ($resource) {
            $this->syncResource($resource);
            
            return new ViewModel(array(
                'result' => $result,
                'username' => $resource->resourceName
            ));
        }
        
        return $this->redirect()->toRoute('fbwatch');
    }
    
    public function syncAllAction()
    {
        $this->assertLoggedIn();
        
        $resources = $this->getResourceTable()->fetchAll();
        foreach ($resources as $resource) {
            $this->syncResource($resource);
        }
        
        return $this->redirect()->toRoute('fbwatch');
    }
    
    private function syncResource(Resource $resource)
    {
        $result = $this->runQuery($resource->resourceName);
        $resource->lastSynced = (new \DateTime())->format('Y-m-d G:i:s+T');
        if (empty($resource->facebookId)) {
            $resource->facebookId = $result['basicData']['data']['id'];
        }
        $this->getResourceTable()->saveResource($resource);
        
        return $result;
    }
    
    private function runQuery($searchFor)
    {
        $dataGatherer = new UserDataGatherer($searchFor, $this->facebook);
            
        try {
            $result = $dataGatherer->startFetch();
        } catch (\FacebookApiException $e) {
            $errorResult = $e->getResult();
            if (array_key_exists('code', $errorResult) 
                    && 102 == $errorResult['error']['code']) {
                return $this->redirect()->toRoute('fbwatch', array('action' => 'login'));
            }
            throw $e;
        }
        
        return $result;
    }
    
    public function apitestAction() 
    {
        $this->assertLoggedIn();
        
        $query = $this->params()->fromQuery('query');
        $result = '';
        
        if (!empty($query)) {
            try {
                $result = $this->facebook->api($query);
            } catch (\FacebookApiException $e) {
                $result = $e->getMessage() . json_encode($e->getResult());
            }
        }
        
        return new ViewModel(array(
            'result' => $result,
            'query' => $query,
            'facebook' => $this->facebook
        ));
    }
}