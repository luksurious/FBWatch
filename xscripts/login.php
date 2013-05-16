<?php
    
	$appId = '558538167531777';
	$facebook = new Facebook(array(
		'appId'  => $appId,
		'secret' => '9383f8e11829a80f740d43a995172ef1',
		'cookie' => true
    ));
    
    $user = $facebook->getUser();  
    $my_url = "http://fbwatch.lukas-brueckner.de/";
    print_r($user);
	if (0 == $user) {
        $code = $_REQUEST["code"];

        if(empty($code)) {
            $_SESSION['state'] = md5(uniqid(rand(), TRUE)); // CSRF protection 
            $dialog_url = "https://www.facebook.com/dialog/oauth?client_id=" 
            . $appId . "&redirect_uri=" . urlencode($my_url) . "&state="
            . $_SESSION['state'] . "&scope=user_location";

            echo("<script> window.location.href='" . $dialog_url . "'</script>");
        }   
    } else { 
        
        //try {
            $authenticated = $facebook->api("/me?fields=installed");
            echo json_encode($authenticated);
			print_r($authenticated);

            if (false == $authenticated['installed']) {
                $code = $_REQUEST["code"];

                if(empty($code)) {
                    $_SESSION['state'] = md5(uniqid(rand(), TRUE)); // CSRF protection
                    $dialog_url = "https://www.facebook.com/dialog/oauth?client_id=" 
                    . $app_id . "&redirect_uri=" . urlencode($my_url) . "&state="
                    . $_SESSION['state'] . "&scope=user_location";

                    echo("<script> window.location.href='" . $dialog_url . "'</script>");
                }  
            }
    }