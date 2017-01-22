<?php
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);
$servername = "mysql.hostinger.in";
$username = "u294078145_aa112";
$password = "ame123pun";
$dbname = "u294078145_flock";
$bot_token = "5aa6fb82-1cdf-4fe9-b85d-70f44e00e868";//"2bc21613-1d14-4679-b64f-32fe69cbbce7";
$weather_key = "b12e906e36343cad18c4d715c532fe7b";
$news_key = "d57e794562c849debe8ca8981726cf6e";
$cricket_key = "YIjoubVlUIc3gUE9mPmg1fs0h4p1";

		

$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection

if (mysqli_connect_errno())
	{
	printf("Connect failed: %s\n", mysqli_connect_error());
	exit();
	}

$data = json_decode(file_get_contents('php://input') , true);
$name = $data["name"];

if ($name === "app.install") add_user($data["userId"], $data["token"]);
  else
if ($name === "app.uninstall") remove_user($data["userId"]);
  else
if ($name === "chat.receiveMessage") handle_message($data["userId"], $data["message"]);
  else
if ($name === "client.flockmlAction") handle_flockml_action($data["userId"], $data["actionId"]);
  else
if ($name === "client.slashCommand") slash_message($data["userId"],$data["chat"], $data["text"]);


function send_news($token, $to,  $search){
	$num = 3;
	$rss2json_url = "https://api.rss2json.com/v1/api.json";

	$rssfeed_url = "https://news.google.com/news?q=".urlencode($search)."&output=rss&num=".$num;

	$fileContents  = file_get_contents($rssfeed_url);
	$fileContents = str_replace(array("\n", "\r", "\t"), '', $fileContents);
	$fileContents = trim(str_replace('"', "'", $fileContents));
	$simpleXml = simplexml_load_string($fileContents);
	$json = json_encode($simpleXml);
	$data = json_decode($json, true);
	$data = $data["channel"];
	
	//$data = json_decode(file_get_contents($rss2json_url."?rss_url=".urlencode($rssfeed_url)),true);
	//echo json_encode($data);
	//print_r($data["channel"]);
	
	for($i = 0; $i < $num; $i++){
		//To remove link of the news
		$link = array();
		parse_str($data["item"][$i]["link"],$link);
		
		//To remove description of the news
		$desc = strip_tags($data["item"][$i]["description"]);
		$desc = htmlspecialchars_decode($desc, ENT_QUOTES | ENT_HTML401);
		$desc = html_entity_decode($desc);

		$str1 = str_replace(" - ","",$data["item"][$i]["title"]);
		$desc = substr($desc,strpos($desc,$str1)+ strlen($str1));
		$desc = substr($desc,0,strpos($desc, "...") + 3);
		$desc = $desc."\nGoogle News";

		//To remove image url of the news
		$img = $data["item"][$i]["description"];
		$img_s = strpos($img, "<img src=") + 10;
		//echo substr($img, $img_s);
		$img_e = strpos($img, "\"", $img_s);
		$img = "http:".substr($img, $img_s, $img_e - $img_s);
		//echo $img;

		send_news_attachment($to, $token, $data["item"][$i]["title"], $desc, $img ,$link["url"]);
	}
}
function send_news_attachment( $to, $token, $title, $desc, $image_url, $link )
{
	$url = "https://api.flock.co/v1/chat.sendMessage";
	$data = array(
		 'to' => $to,
		'token' => $token,
		'attachments' => array(
			 array(
				'title' => $title,
				'description' => $desc,
				'views' => array(
					/* 'widget' => array(
						 'src' => $widget_url,
						'width' => "400",
						'height' => "400" 
					),
					'html' => array(
						'inline' => $desc,
						'width' => "400",
						'height' => "400" 
					),
					'flockml' => $desc ,
					*/
					'image' => array(
						'original' => array(
							'src' => $image_url,
							'width' => "100",
							'height' => "100" 
						)
					)
				),
				'forward' => "true",
				'url' => $link,
				'buttons' => array(
					 array(
						 'name' => "Read More",
						'id' => "button_id",
						'action' => array(
							'type' => "openBrowser",
							'url' => $link 
						) 
					) 
				) 
				
			) 
		) 
	);
	
	$query = json_encode( $data );
	
	// $query = http_build_query($data);
	
	//echo $query;
	$ch = curl_init( $url );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_HEADER, 0 );
	curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
		 'Content-Type: application/json' 
	) );
	curl_setopt( $ch, CURLOPT_POST, 1 );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $query );
	
	// execute!
	
	$response = curl_exec( $ch );
	
	// close the connection, release resources used
	
	curl_close( $ch );
	
	// do anything you want with your response
	
	//var_dump( $response );
	
}

function handle_flockml_action($uid, $action_id)
	{
	global $bot_token;
	if (is_numeric($action_id))
		{
		global $cricket_key;
		$score = json_decode(file_get_contents("http://cricapi.com/api/cricketScore?apikey=" . $cricket_key . "&unique_id=" . $action_id) , true);
		if (strstr($score["score"], "Match over")) $response = "<flockml>" . $score["score"] . "<br/>" . $score["innings-requirement"] . "<br/><action type=\"sendEvent\" id=\"" . $action_id . "\">" . "Refresh </action> <br/></flockml>";
		  else $response = "<flockml>" . $score["score"] . "<br/><action type=\"sendEvent\" id=\"" . $action_id . "\">" . "Refresh </action> <br/></flockml>";
		send_message($uid, $bot_token, $response, 1);
		}
	}

function send_weather_attachment($to, $token, $title, $flockml_text, $image_url, $widget_url)
	{
	$url = "https://api.flock.co/v1/chat.sendMessage";
	$data = array(
		'to' => $to,
		'token' => $token,
		'attachments' => array(
			array(
				'title' => $title,
				'description' => $flockml_text,
				'views' => array(
					/*'widget' => array(
						'src' => $widget_url,
						'width' => "400",
						'height' => "400"
					) ,
					//'flockml' => $flockml_text,
					*/'image' => array(
						'original' => array(
							'src' => $image_url,
							'width' => "100",
							'height' => "100" 
						)
					)
				) ,
				'forward' => "true",
				'buttons' => array(
					array(
						'name' => "Forecast",
						'id' => "button_id",
						'action' => array(
							'type' => "openWidget",
							'desktopType' => "sidebar",
							'mobileType' => "modal",
							'url' => $widget_url
						)
					)
				)
			)
		)
	);
	$query = json_encode($data);

	// $query = http_build_query($data);
	// //echo $query;

	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json'
	));
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $query);

	// execute!

	$response = curl_exec($ch);

	// close the connection, release resources used

	curl_close($ch);

	// do anything you want with your response
	// //var_dump( $response );

	}
function send_meaning_attachment($to, $token, $title, $flockml_text, $widget_url)
	{
	$url = "https://api.flock.co/v1/chat.sendMessage";
	$data = array(
		'to' => $to,
		'token' => $token,
		'attachments' => array(
			array(
				'title' => $title,
				'description' => "Courtesy : Pearson",
				'views' => array(
					'flockml' => $flockml_text
				) ,
				'forward' => "true",
				'buttons' => array(
					array(
						'name' => "Pronunciations",
						'id' => "button_id",
						'action' => array(
							'type' => "openWidget",
							'desktopType' => "modal",
							'mobileType' => "modal",
							'url' => $widget_url
						)
					)
				)
			)
		)
	);
	$query = json_encode($data);

	// $query = http_build_query($data);
	// //echo $query;

	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json'
	));
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $query);

	// execute!

	$response = curl_exec($ch);

	// close the connection, release resources used

	curl_close($ch);

	// do anything you want with your response
	// //var_dump( $response );

	}

function send_message($to, $token, $text, $is_flockml)
	{
	$url = "https://api.flock.co/v1/chat.sendMessage";
	if ($is_flockml == 1) $data = array(
		'to' => $to,
		'token' => $token,
		'flockml' => $text,
		'text' => $text
	);
	  else $data = array(
		'to' => $to,
		'token' => $token,
		'text' => $text
	);

	// $query = json_encode( $data );

	$query = http_build_query($data);

	// //echo $query;

	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $query);

	// execute!

	$response = curl_exec($ch);

	// close the connection, release resources used

	curl_close($ch);

	// do anything you want with your response
	// //var_dump( $response );

	}

function handle_message($uid, $message){
	global $bot_token, $weather_key;
	$from = $message["from"];
	$to = $message["to"];
    $text = $message["text"];
	perform_action($uid, $text, $uid, $bot_token);
}

function slash_message($uid, $to, $text){
	global $bot_token, $weather_key;
	
	$abc = array(
		'text' => "Responded to slash message"
	);
	echo json_encode($abc);
	perform_action($uid, $text, $to, get_token($uid));
	
}

function get_token($uid)
{
	global $conn;
	$sql = "SELECT auth_token FROM Users WHERE userid='$uid'";
	$result = mysqli_query($conn, $sql);
	if ($result == TRUE) {
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		return $row["auth_token"];
	}
	else return "";
}

function remove_user($uid)
{
	global $conn;
	$sql = "DELETE FROM Users WHERE userid='$uid'";
	if (mysqli_query($conn, $sql) === TRUE) {
		//echo "Deleted successfully";
	}
	else {
		//echo "Error: " . $sql . "<br />" . $conn->error;
	}
}

function add_user($uid, $token)
{
	global $conn;
	$sql = "INSERT INTO Users (userid,auth_token) VALUES ('$uid','$token')";
	if (mysqli_query($conn, $sql) === TRUE) {
		//echo "New record created successfully";
        $welcome_message = "<flockml>Welcome to the <strong>Weather Bot</strong>. <br/>Type <b>help</b> for more options <br/> Please enter a <strong>city name </strong> <br/>";
        send_message($uid,$bot_token,$welcome_message,true);
	}
	else {
		//echo "Error: " . $sql . "<br />" . $conn->error;
	}
}

function perform_action($uid, $str, $to, $token){
	global $bot_token;
	if(strpos($str," ") == FALSE){
		$feature = $str;
	}
	else{
		$feature = trim(substr($str,0, strpos($str," ")));
	}
	$keyword = trim(substr($str,strlen($feature)));
	
	if(strtolower($feature) === "news"){
		send_news($token, $to, $keyword);
	}
	else if(strtolower($feature) === "weather"){
		$weather_data = json_decode(file_get_contents("http://api.openweathermap.org/data/2.5/forecast/daily?q=".urlencode($keyword)."&appid=b12e906e36343cad18c4d715c532fe7b&units=metric") , true);	
		$response = ucfirst($weather_data["list"][0]["weather"][0]["description"]) . ",                      \nTemperature: " . $weather_data["list"][0]["temp"]["min"] ." /". $weather_data["list"][0]["temp"]["max"]." C\nHumidity: " . $weather_data["list"][0]["humidity"]."%" ;
		$image_url = "http://openweathermap.org/img/w/".$weather_data["list"][0]["weather"][0]["icon"].".png";
		send_weather_attachment($to, $token,$weather_data["city"]["name"].",". $weather_data["city"]["country"], $response, $image_url,"http://www.flockathon.16mb.com/widget.php?q=".$keyword);	
	}
	else if(strtolower($feature) === "cricket"){
		global $cricket_key;
		$matches = json_decode(file_get_contents("http://cricapi.com/api/matches?apikey=" . $cricket_key) , true) ["matches"];

		// send_message($uid,$bot_token,"Hello From Cricket",false);

		$response = "<flockml> Current Matches:<br/>";
		foreach($matches as $match)
			{
			if ($match["squad"] == true && $match["matchStarted"] == true) $response.= "<br/> <action type=\"sendEvent\" id=\"" . $match["unique_id"] . "\">" . $match["team-1"] . " vs " . $match["team-2"] . "</action> <br/>";
			}

		$response.= "</flockml>";
		send_message($to, $token, $response, 1);
		
	}
	else if(strtolower($feature) === "wiki"){
		$wiki_image =json_decode(file_get_contents("https://en.wikipedia.org/w/api.php?action=query&prop=pageimages&format=json&piprop=original&titles=".urlencode(strtolower($keyword))), true);
		$new_result_image = array_pop($wiki_image["query"]["pages"]);
		$image_url = $new_result_image["thumbnail"]["original"];
		//echo $image_url;
		$wiki_data =json_decode(file_get_contents("https://en.wikipedia.org/w/api.php?action=query&prop=extracts&format=json&exchars=1000&titles=".urlencode(strtolower($keyword))), true);
		$new_result = array_pop($wiki_data["query"]["pages"]);
		$data = $new_result["extract"];
		//echo $data;
		$page_id = $new_result["pageid"];
		$wiki_url = json_decode(file_get_contents("https://en.wikipedia.org/w/api.php?action=query&prop=info&pageids=".urlencode($page_id)."&inprop=url&format=json"), true);
		$url = $wiki_url["query"]["pages"][$page_id]["fullurl"];
		//echo $url;
		send_news_attachment($to, $token, $new_result["title"]." - Wikipedia", substr(html_entity_decode(strip_tags($data),ENT_QUOTES | ENT_HTML401),0,350)."...", $image_url,$url);
	}
	else if (strtolower($feature) === "meaning"){
		$res = json_decode(file_get_contents("http://api.pearson.com/v2/dictionaries/ldoce5/entries?headword=".urlencode($keyword)),true);
		//echo $word."\n".$res["results"][0]["part_of_speech"]. ": " .$res["results"][0]["senses"][0]["definition"][0];
		$flckml = "<flockml><strong>".$res["results"][0]["headword"]."</strong><br/>".$res["results"][0]["pronunciations"][0]["ipa"]."<br/><b>".$res["results"][0]["part_of_speech"]."</b><br/> ".$res["results"][0]["senses"][0]["definition"][0]."<br/><i>\"". $res["results"][0]["senses"][0]["examples"][0]["text"]."\"</i><br/></flockml>";
		send_meaning_attachment($to, $token, "Meaning", $flckml,"http://api.pearson.com".$res["results"][0]["pronunciations"][0]["audio"][0]["url"]);
	}
	else if (strtolower($feature) === "subscribe"){
		if(strpos($str," ") == FALSE){ // No keyword provided
			send_message($to, $bot_token,"Provide a keyword",0);
			return;
		}
		else{
			$type = trim(substr($keyword,0, strpos($keyword," ")));
		}
		$keyword = trim(substr($keyword,strlen($type)));
		$type = strtolower($type);
	
		if( strtolower($type) === "news" || strtolower($type) === "weather"){
			if($uid === $to){   //Normal message to bot
				add_subscription($uid, $type, $keyword);
			}
			else if(substr($to,0,1) === "g"){ //Slash command in a group
				//echo json_encode(array('text' => "Subscribed"));
				add_subscription($to, $type, $keyword);
			}
			else{ // Slash command anywhere else
				//echo json_encode(array('text' => "Subscribed"));
				add_subscription($uid, $type, $keyword);
			}
		}
	}
	else if (strtolower($feature) === "unsubscribe"){
		if( strtolower($keyword) === "news" || strtolower($keyword) === "weather"){
			if($uid === $to){   //Normal message to bot
				remove_subscription($uid,$keyword);
				//send_message($to, $token, "Unsuscribed from ".$keyword, 0);
			}
			else if(substr($to,0,1) === "g"){ //Slash command in a group
				//echo json_encode(array('text' => "Unsubscribed"));
				remove_subscription($to,$keyword);
			}
			else{ // Slash command anywhere else
				//echo json_encode(array('text' => "Unsubscribed"));
				remove_subscription($uid,$keyword);
			}
		}
		else{
			send_message($to, $bot_token, "Unsubscribe failed. Write unsubscribe news or unsubscribe weather", 0);
		}	
	}
	else{
		//$welcome_message = "<flockml>Welcome to the <strong>Info Bot</strong>. <br/>Type :<b>Feature</b> [keyword if needed] <br/> Examples:<br/>News COEP<br/>Weather Mumbai<br/>cricket<br/>Wiki India<br/>meaning endanger";
                $welcome_message = "<flockml>Welcome to the <strong>Info Bot</strong>.<br/>Type :<b>Feature</b> [keyword if needed] <br/> Examples:<br/><strong>News</strong> COEP<br/><strong>Weather</strong> Mumbai<br/><strong>cricket</strong><br/><strong>Wiki</strong> India<br/><strong>meaning</strong> endanger<br/> For <strong>Subscribing</strong> to Weather or News updates: <strong>subscribe weather/news keyword</strong></br> Examples: <strong>subscribe</strong> news Trump</br></br> To subscribe News and Weather in a group don't forget to add our InfoBot in the group and stay updated</br> To unsubscribe just type unsubscribe news/weather";
		send_message($to, $bot_token, $welcome_message, 1);
	}

}

function remove_subscription($uid,$type)
{
	global $conn;
	global $bot_token;
	$sql = "DELETE FROM Subscription WHERE userid='$uid' AND type='$type'";
	if (mysqli_query($conn, $sql) === TRUE) {
		if(strtolower($type) === "news" ) {
				$subscription_message = "<flockml>You have been unsubscribed for <strong>News Updates</strong> from <strong>Info Bot</strong>.</flockml>";
		}
		else { 
				$subscription_message = "<flockml>You have been unsubscribed for <strong>Weather Updates</strong> from <strong>Info Bot</strong>.</flockml>";
		}
		send_message($uid,$bot_token,$subscription_message,true);
		//echo "Deleted successfully";
	}
	else {
		//echo "Error: " . $sql . "<br />" . $conn->error;
	}
}

function add_subscription($uid,$type,$keyword)
{
//type has to be string, either news or weather
	global $conn;
	global $bot_token;
	$sql = "INSERT INTO Subscription (userid,type,keyword) VALUES ('$uid','$type','$keyword')";
	
	if (mysqli_query($conn, $sql) === TRUE) {
		//echo "New record created successfully";
		if(strtolower($type) === "news" ) {
			$subscription_message = "<flockml>You have been subscribed for <strong>News Updates</strong> for <strong>".ucfirst($keyword)."</strong> from <strong>Info Bot</strong>.</flockml>";
        }
		else { 
			$subscription_message = "<flockml>You have been subscribed for <strong>Weather Updates</strong> for <strong>".ucfirst($keyword)."</strong> from <strong>Info Bot</strong>.</flockml>";
       }
		send_message($uid,$bot_token,$subscription_message,true);
	}
	else {
		//echo "Error: " . $sql . "<br />" . $conn->error;
	}
}
//file_put_contents('post.log', print_r($data,true) . PHP_EOL, FILE_APPEND);
?>			
	