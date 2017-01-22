<html>
<head>
<style>
#below {
   background-color: #FFFFCC
}
</style>
<title>W3.CSS</title>
</head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="http://www.w3schools.com/lib/w3.css">
<link href="http://www.flockathon.16mb.com/weather-icon-animated.css" rel="stylesheet">
<link rel="stylesheet" href="http://www.w3schools.com/lib/w3-theme-indigo.css">
<?php
$weather_key = "b12e906e36343cad18c4d715c532fe7b";
$weather_data = json_decode(file_get_contents("http://api.openweathermap.org/data/2.5/forecast/daily?q=" .  $_GET["q"] . "&appid=" . $weather_key . "&units=metric") , true);
$country = $weather_data["city"]["country"];
$iconCode = $weather_data["list"][0]["weather"][0]["icon"]; 
$idCode = $weather_data["list"][0]["weather"][0]["id"];
$epoch =  $weather_data["list"][0]["dt"];
$dt = new DateTime("@$epoch");
$weekday = date('l', $epoch);
$iconCode_1 = $weather_data["list"][1]["weather"][0]["icon"]; 
$idCode_1 = $weather_data["list"][1]["weather"][0]["id"];
$epoch_1 =  $weather_data["list"][1]["dt"];
$weekday_1 = date('l', $epoch_1);
$dt_1 = new DateTime("@$epoch_1"); 
$iconCode_2 = $weather_data["list"][2]["weather"][0]["icon"]; 
$idCode_2 = $weather_data["list"][2]["weather"][0]["id"];
$epoch_2 =  $weather_data["list"][2]["dt"];
$weekday_2 = date('l', $epoch_2);
$dt_2 = new DateTime("@$epoch_2");
$iconCode_3 = $weather_data["list"][3]["weather"][0]["icon"]; 
$idCode_3 = $weather_data["list"][3]["weather"][0]["id"];
$epoch_3 =  $weather_data["list"][3]["dt"];
$weekday_3 = date('l', $epoch_3);
$dt_3 = new DateTime("@$epoch_3");  
?>

<body>

<div class="w3-card-12 w3-margin" style="width:90%">
  <div class="w3-display-container w3-text-white w3-light-blue w3-bold">
           <img src="http://www.flockathon.16mb.com/images.jpg" alt="Lights" style="width:100%">
    <div class="w3-display-bottomright w3-padding"><?php echo strtoupper($_GET["q"]);?> , <?php echo $country; ?> </div>
    <div class="w3-display-topleft w3-padding"><?php echo $weather_data["list"][0]["temp"]["day"];?>&degC
      <div><?php echo ucwords($weather_data["list"][0]["weather"][0]["description"]);?> <?php echo $weather_data["list"][0]["temp"]["max"];?>&deg / <?php echo $weather_data["list"][0]["temp"]["min"];?>&degC</div>
    </div>
  </div>
<div class="w3-row-padding w3-text-black" id="below">
    </br>
    <div class="w3-col s4 m4 l4 w3-center">
    <div> <font size="1"><?php echo $dt_1->format('M/d')?></br><?php echo substr($weekday_1, 0, 3);?></br><?php echo $weather_data["list"][1]["temp"]["day"];?>&degC</font></div>
      <img src="http://openweathermap.org/img/w/<?php echo $iconCode_1; ?>.png" alt="sun" style="width:40px"></br></br>
    </div>
    <div class="w3-col s4 m4 l4 w3-center">
    <div> <font size="1"><?php echo $dt_2->format('M/d')?></br><?php echo substr($weekday_2, 0, 3); ?></br><?php echo $weather_data["list"][2]["temp"]["day"];?>&degC</font> </div>
      <img src="http://openweathermap.org/img/w/<?php echo $iconCode_2; ?>.png" alt="cloud" style="width:40px"></br></br>
    </div>
    <div class="w3-col s4 m4 l4 w3-center">
    <div> <font size="1"><?php echo $dt_3->format('M/d')?></br><?php echo substr($weekday_3, 0, 3); ?></br><?php echo $weather_data["list"][3]["temp"]["day"];?>&degC</font> </div>
       <img src="http://openweathermap.org/img/w/<?php echo $iconCode_3; ?>.png" alt="clouds" style="width:40px"></br></br>
    </div>
  </div>
</div>
</body>
</html> 