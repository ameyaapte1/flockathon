<?php
include 'default.php';

$query = "SELECT * FROM Subscription";
$result = mysqli_query($conn,$query);
$rows=[];
while($row = mysqli_fetch_array($result,MYSQLI_ASSOC))

{
	$rows[] = $row;
}

foreach($rows as $row)
{
	$userid = $row['userid'];
	$keyword = $row['keyword'];
	if($row['type']=="news") {
		send_news($bot_token, $userid, $keyword);
	}
	else if($row['type']=="weather") {
		$weather_data = json_decode(file_get_contents("http://api.openweathermap.org/data/2.5/forecast/daily?q=".urlencode($keyword)."&appid=b12e906e36343cad18c4d715c532fe7b&units=metric") , true);	
		$response = ucfirst($weather_data["list"][0]["weather"][0]["description"]) . ",                      \nTemperature: " . $weather_data["list"][0]["temp"]["min"] ." /". $weather_data["list"][0]["temp"]["max"]." C\nHumidity: " . $weather_data["list"][0]["humidity"]."%" ;
		$image_url = "http://openweathermap.org/img/w/".$weather_data["list"][0]["weather"][0]["icon"].".png";
		send_weather_attachment($userid, $bot_token,$weather_data["city"]["name"].",". $weather_data["city"]["country"], $response, $image_url,"http://www.flockathon.16mb.com/widget.php?q=".$keyword);	
	}
}
?>