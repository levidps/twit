
<?php

@include_once('config.php');

/******************* START IP LOCATIION ACTIONS *************************/

    // Get user IP address
    $ip = $_SERVER['REMOTE_ADDR'];

    // Initia cURL for getting location JSON (uses another website)
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => "ipinfo.io/$ip",
        CURLOPT_USERAGENT => 'IP address cURL Request'
    ));
    $resp = curl_exec($curl);
    curl_close($curl);

    // Decode location details
    $details = json_decode($resp);

    // Get location lat / long
    $longLat = $details->loc;

    // Location for testing locally
    $testLocation = '-38.1953,146.5415';

    // break lat / long into seperate stings for use in WOEID
    $location = explode(",", $testLocation);

    echo '<h1>Twittr Test</h1>';

    // Twitter API third party BS
    require_once 'TwitterAPIExchange.php';



/******************* END IP LOCATIION ACTIONS ***************************/

/******************* START TWITTER ACTIONS ******************************/

    // Twitter Geo Call
    $url = 'https://api.twitter.com/1.1/trends/closest.json';
    $requestMethod = 'GET';
    $getwoid = '?lat='. $location[0] .'&long='. $location[1] .'';

    $twitter = new TwitterAPIExchange($settings);
    $string = json_decode($twitter->setGetfield($getwoid)
    ->buildOauth($url, $requestMethod)
    ->performRequest(),$assoc = TRUE);

    $url2 = 'https://api.twitter.com/1.1/trends/place.json';
    $gettrends = '?id='. $string[0]['woeid'] .'&count=5'; 

    $twitterTrend = new TwitterAPIExchange($settings);
    $trends = json_decode($twitterTrend->setGetfield($gettrends)
    ->buildOauth($url2, $requestMethod)
    ->performRequest(),$assoc = TRUE);

    // Console log twitter errors
    if( $string["errors"][0]["message"] != "" ) {
        echo "<script> console.log('TWITTER ERROR: Sorry, there was a problem. Twitter returned the following error message: ".$string[errors][0]["message"]."'); </script>";
        exit();}
/*
    echo "<pre>";

        print_r($trends);

    echo "</pre>";

*/

/******************* END TWITTER ACTIONS ********************************/

$params = array(
    'api_key'   => $flikr_key,
    'method'    => 'flickr.photos.getRecent',
    'ispublic'  => '0',
    'page'      => '1',
    'perpage'   => '1',
    'format'    => 'php_serial',
);

$encoded_params = array();
foreach ($params as $k => $v){
    $encoded_params[] = urlencode($k).'='.urlencode($v);
}

#
# call the API and decode the response
#
$url = "https://api.flickr.com/services/rest/?".implode('&', $encoded_params);
$rsp = file_get_contents($url);
$rsp_obj = unserialize($rsp);

$img = $rsp_obj['photos']['photo'][3];

$img_url = 'https://farm'. $img['farm'] .'.staticflickr.com/'. $img['server'] .'/'. $img['id'] .'_'. $img['secret'] .'_b.jpg';

#
# display the photo title (or an error if it failed)
#
if ($rsp_obj['stat'] == 'ok'){
    $bg = $img_url;    
}else{
    $bg = 'default';
    echo "<script>console.log( 'Shit\'s fucked! Image has\'t loaded from Flickr :/' );</script>";
}


/******************* START GUARDIAN ACTIONS *****************************/
    
    // Trend array number used to iterate through trends if no articles can be found
    $i = 0;
    $resultingName = $trends[0]['trends'][$i]['name'];
    $resultingTrend = $trends[0]['trends'][$i]['query'];

    // Remove double quotes from twitter query
    if ( strpos($resultingTrend, '%22') !== false ) {
        $query = str_replace($resultingTrend, '%22', '');
    } else {
        $query = $resultingTrend;
    }

    $guardian = curl_init();
    curl_setopt_array($guardian, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => "https://content.guardianapis.com/search?q=%22". $query ."%22&api-key=" . $guardian_key.'',
        CURLOPT_USERAGENT => 'Guardian cURL Request'
    ));
    $articles = curl_exec($guardian);
    curl_close($guardian);

    $articleResults = json_decode($articles);

    $max = count($articleResults->response->total);

    $number = rand(0,$max);

    $article_title = $articleResults->response->results[$number]->webTitle;
    $article_url = $articleResults->response->results[$number]->webUrl;

    if ( $articleResults->response->total == 0 ) {
        echo 'no results';
    } else if ( $article_url == null ) {
        echo '<script type="text/javascript"> console.log("It would appear no article was found :\'( ")); </script>';
    }
    
    // print_r($articleResults->response->results);
    // print_r($articleResults->response->total);

?>



<!DOCTYPE html>
<html>
<head>

<style type="text/css">
body {
    background-color: #f1e3a0;
}

body:after {
    content: '';
    display: block;
    background: linear-gradient(180deg, #f430a9, #f2e782);
    mix-blend-mode: lighten;
    height: 100%;
    width: 100%;
    position: absolute;
    top: 0;
    left: 0;
    z-index: 10;
}

img {
    mix-blend-mode: darken;
    -webkit-filter: grayscale(100%) contrast(2);
    filter: grayscale(100%) contrast(2);
    opacity: .5;
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 2;
}
</style>

</head>
<body>

<img src="<?php echo $bg; ?>">

<?php


    echo "<h1>Hey there</h1>";
    echo "Currently trending in your area is " . $resultingName . ", if you want to learn more check out <a href=\"" . $article_url . "\" target=\" _blank\">" . $article_title . "</a>";


?>

</body>
</html>