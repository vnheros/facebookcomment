<?php
if ($_GET['hub_verify_token'] === 'XXXXXXXXX-123456789-ABCdef') {
  echo $_GET['hub_challenge'];
}

$entry = file_get_contents('php://input');
file_put_contents('commentwebhook.log', "\n" . $entry, FILE_APPEND);

//get input message
$input = json_decode($entry, true);
$message = $input['entry'][0]['changes'][0]['value']['message'];
$fb_comment_id = $input['entry'][0]['changes'][0]['value']['id'];
$created_time = $input['entry'][0]['changes'][0]['value']['created_time'];
$from_name = $input['entry'][0]['changes'][0]['value']['from']['name'];
$from_id = $input['entry'][0]['changes'][0]['value']['from']['id'];

//get more details
$access_token = "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX";
$url = "https://graph.facebook.com/v2.6/$fb_comment_id?access_token=$access_token&fields=permalink_url";//fields=permalink_url,message,from,created_time
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$resp = curl_exec($ch);
curl_close($ch);
$output = json_decode($resp, true);
$permalink_url = $output['permalink_url'];

//send email
$bodyHtml = 
"<h1><a href='https://www.facebook.com/$from_id'>$from_name</a></h1>
<h2>$message</h2>
<h3>On: <a href='$permalink_url'>$permalink_url</a></h3>";
$bodyText = "$from_name:\n  $message\n  On: $permalink_url";
$url = 'https://api.elasticemail.com/v2/email/send';
try {
	$post = array('from' => 'youremail@yourdomain.com',
	'fromName' => 'Your Website Name',
	'apikey' => '00000000-0000-0000-0000-000000000000',
	'subject' => 'You have a comment',
	'to' => 'recipient1@gmail.com;recipient2@gmail.com',
	'bodyHtml' => $bodyHtml,
	'bodyText' => $bodyText,
	'isTransactional' => true);
	
	$ch = curl_init();
	curl_setopt_array($ch, array(
		CURLOPT_URL => $url,
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => $post,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HEADER => false,
		CURLOPT_SSL_VERIFYPEER => false
	));
	
	$result=curl_exec ($ch);
	curl_close ($ch);
	
	file_put_contents('commentwebhook.log', "\n" . $result, FILE_APPEND);	
}
catch(Exception $ex){
	file_put_contents('commentwebhook.log', "\n" . $ex->getMessage(), FILE_APPEND);
}
?>
