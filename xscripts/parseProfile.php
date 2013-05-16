<?php
require_once 'setup.php';
require_once 'login.php';

$searchFor = $_GET['username'];

if ($searchFor) {

    try {
        $basic_data = $facebook->api("/$searchFor");

        $filename = dirname(__FILE__) . "/data/$user/";
		
        if (!file_exists($filename)) {
            mkdir($filename);
        }

        $filename = $filename . "$basic_data[id]/";

        if (!file_exists($filename)) {
            mkdir($filename);
        }

        if (!empty($basic_data)) {
            $handle = fopen($filename . "basicdata.json", 'w+');
            fwrite($handle, json_encode($basic_data));
            fclose($handle);

            try {
                $str = $facebook->api("/$searchFor/feed");
                // echo json_encode($feed).'<br><br>';
                $feed[] = $str;

                $counter = 0;

                $nextpage = $str['paging']['next'];
                parse_str($nextpage);

                while ($until != null) {

                    $str = $facebook->api("/$searchFor/feed?limit=$limit&until=$until");
                    $feed[] = $str;
                    $until = null;

                    $nextpage = $str['paging']['next'];
                    parse_str($nextpage);
                }

                if (count($feed) > 0) {
                    for ($i = 0; $i < sizeof($feed); $i = $i + 1) {
                        $handle = fopen($filename . "feed$i.json", 'w+');
                        fwrite($handle, json_encode($feed[$i]));
                        fclose($handle);
                    }
                }
            } catch (Exception $e) {
                print $e . "3<br>";
            }

            try {
                $str = $facebook->api("/$searchFor?fields=statuses");
                // echo json_encode($feed).'<br><br>';
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

                    $str = $facebook->api("/$searchFor/statuses?limit=$limit&until=$until");
                    $status[] = $str;
                    $lastUntil = $until;
                    $until = null;

                    $nextpage = $str['paging']['next'];
                    print "<br>Next page: $nextpage<br>";
                    parse_str($nextpage);
                }

                if (count($status) > 0) {
                    for ($i = 0; $i < sizeof($status); $i = $i + 1) {
                        $handle = fopen($filename . "status$i.json", 'w+');
                        fwrite($handle, json_encode($status[$i]));
                        fclose($handle);
                    }
                }
            } catch (Exception $e) {
                print $e . "4<br>";
            }
        }

        print "Data Gathered";
    } catch (Exception $e) {
        print $e . "1<br>";
    }

//        try {
//            $friends = $facebook->api("/$searchFor?fields=friends,friendlists.fields(members,name,list_type,id)");    
//        } catch (Exception $e) {
//            print $e."2<br>";
//        }
//        $handle = fopen($filename."friends.json", 'x+');
//        fwrite($handle, json_encode($friends));
//        fclose($handle);  
} else {
    print "No username provided";
}