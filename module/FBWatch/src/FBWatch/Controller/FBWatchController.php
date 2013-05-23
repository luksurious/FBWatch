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
        
        if (empty($container->authCode)) {
            $code = $this->params()->fromQuery('code');
            if (empty($code)) {
                $user = $this->facebook->getUser();
                
                if (0 == $user) {
                    return $this->redirect()->toRoute('fbwatch', array('action' => 'login'));
                }
            }
            $container->authCode = $code;
        }
    }
    
    public function indexAction()
    {
        $this->assertLoggedIn();
    }

    public function loginAction()
    {
        $_SESSION['state'] = md5(uniqid(rand(), TRUE)); // CSRF protection
        $my_url = "http://fbwatch.lukas-brueckner.de/";
        
        return new ViewModel(array(
            'login_url' => "https://www.facebook.com/dialog/oauth?client_id=" 
                . $this->facebook->getAppId() . "&redirect_uri=" . urlencode($my_url) 
                . "&state=" . $_SESSION['state'] . "&scope=user_location"
        ));
    }

    public function parseProfileAction()
    {
        $this->assertLoggedIn();
        
        $searchFor = $this->params()->fromQuery('username');
        
        if ($searchFor) {
            (new UserDataGatherer($searchFor, $this->facebook))
                    ->startFetch();
        } else {
            print "No username provided";
        }
    }
}