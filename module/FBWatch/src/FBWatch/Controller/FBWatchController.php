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

    public function parseProfileAction()
    {
        $this->assertLoggedIn();
        
        $searchFor = $this->params()->fromQuery('username');
        
        if ($searchFor) {
            return $this->runQuery($searchFor);
        }
        
        return $this->redirect()->toRoute('fbwatch');
    }
    
    private function runQuery($searchFor)
    {
        $dataGatherer = new UserDataGatherer($searchFor, $this->facebook);
            
        try {
            $result = $dataGatherer->startFetch();
        } catch (\FacebookApiException $e) {
            $errorResult = $e->getResult();
            if (array_key_exists('code', $errorResult) 
                    && 102 == $errorResult["error"]["code"]) {
                return $this->redirect()->toRoute('fbwatch', array('action' => 'login'));
            }
            throw $e;
        }

        return new ViewModel(array(
            'result' => $result,
            'username' => $searchFor
        ));
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