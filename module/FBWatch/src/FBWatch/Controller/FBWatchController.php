<?php
namespace FBWatch\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use FBWatch\Model\UserDataGatherer;
use \Facebook;

class FBWatchController extends AbstractActionController
{
    private $facebook;
    
    public function __construct() 
    {    
        $appId = '558538167531777';
	$this->facebook = new Facebook(array(
		'appId'  => $appId,
		'secret' => '9383f8e11829a80f740d43a995172ef1',
		'cookie' => true
        ));
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
            $container->authCode = $this->facebook->getAccessToken();
        }
        
        $this->facebook->setAccessToken($container->authCode);
        
        echo $this->facebook->getUser();
    }
    
    public function indexAction()
    {
        $this->assertLoggedIn();
    }

    public function loginAction()
    {
        if (0 != $this->facebook->getUser()) {
            $this->redirect()->toRoute('fbwatch');
        }
        
        return new ViewModel(array(
            'login_url' => $this->facebook->getLoginUrl()
        ));
    }

    public function parseProfileAction()
    {
        $this->assertLoggedIn();
        
        $searchFor = $this->params()->fromQuery('username');
        
        if ($searchFor) {
            $dataGatherer = new UserDataGatherer($searchFor, $this->facebook);
            $dataGatherer->startFetch();
        } else {
            print "No username provided";
        }
    }
    
    public function apitestAction() {
        $this->assertLoggedIn();
        
        $query = $this->params()->fromQuery('query');
        $result = '';
        
        if (!empty($query)) {
            try {
                $result = json_encode($this->facebook->api($query));
            } catch (\FacebookApiException $e) {
                $result = $e->getMessage() . json_encode($e->getResult());
            }
        }
        
        return new ViewModel(array(
            'result' => $result,
            'query' => $query
        ));
    }
}