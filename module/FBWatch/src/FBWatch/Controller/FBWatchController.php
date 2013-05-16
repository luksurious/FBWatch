<?php
namespace FBWatch\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
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
    
    public function indexAction()
    {
        $user = $this->facebook->getUser();
        
        $code = $this->params()->fromQuery('code');
        if (0 == $user && empty($code)) {
            return $this->forward()->dispatch('FBWatch\Controller\FBWatch', array('action' => 'login'));
        }
        
        return new ViewModel(array(
            'user' => $user
        ));
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
    }
}