<?php 

 
function API_SendNotification(){
	global $pt,$sqlConnect;
	
	
    $notif_text = '';
	$type = 'admin_notification';
	$url = 'index.php';
	$time = time();
	$notif = isset($_POST['notification']) ? mysqli_real_escape_string($sqlConnect, $_POST['notification']) : 0;
	$recipient_id = isset($_POST['id']) ? mysqli_real_escape_string($sqlConnect, $_POST['id']) : 0;
	$post_id = isset($_POST['post_id']) ? mysqli_real_escape_string($sqlConnect, $_POST['post_id']) : 0;
	$notifier_id = API_GetAnyNotifierId($recipient_id);
	
	 
	if(is_numeric($post_id) && is_numeric($recipient_id) && $post_id > 0  && $recipient_id > 0){
 
		switch($notif){
			
			case 'processing_stream':
	 
							
				$notif_text = 'Your live stream its generating now by our system. You will be notified when it is ready.';
				mysqli_query($sqlConnect, "insert into `notifications` set `time`='{$time}',`notifier_id`='{$notifier_id}',`recipient_id`='{$recipient_id}',`type`='{$type}',`url`='{$url}',`text`='{$notif_text}'");
				
				// make the post invisible untill the stream its processed
				mysqli_query($sqlConnect, "update `videos` set `privacy`='2' where `id`='{$post_id}'");

			break;
			
			case 'stream_processed':
				$notif_text = 'Your live stream its ready! Now you can see it on your timeline.';
				mysqli_query($sqlConnect, "insert into `notifications` set `time`='{$time}',`notifier_id`='{$notifier_id}',`recipient_id`='{$recipient_id}',`type`='{$type}',`url`='{$url}',`text`='{$notif_text}'");
				
				// set the post available
				mysqli_query($sqlConnect, "update `videos` set `privacy`='0' where `id`='{$post_id}'");
				// update mp4
				mysqli_query($sqlConnect, "update `videos` set `video_location`=(SELECT REPLACE(video_location,'.webm','.mp4')) where `id`='{$post_id}'");
				mysqli_query($sqlConnect, "update `vy_live_broadcasts` set `stream_name`=(SELECT REPLACE(stream_name,'.webm','.mp4')) where `post_id`='{$post_id}'");
			break;
			
			
			
		}
	}
	return true;
	
}
 
function API_GetAnyNotifierId($id = 0){
	global $pt,$sqlConnect;
	
	$id = $id > 0 ? $id : 2;
    $q = mysqli_query($sqlConnect, "select `id` from `users` where `id`<>'{$id}' limit 1");
    $r = mysqli_fetch_assoc($q);
 	
	if(isset($r['user_id']))
		$id = $r['user_id'];
 
	
	return $id;
	
}