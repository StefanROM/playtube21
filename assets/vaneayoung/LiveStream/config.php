<?php 

/*
Live stream plugin.

email: me@vaneayoung.com

Copyright 2022 by Vanea Young 
*/

$GLOBALS['V_Y'] = (array)$pt;
$c = glob(dirname(__FILE__). DIRECTORY_SEPARATOR ."ini". DIRECTORY_SEPARATOR ."*.ini");
$CONFIG = parse_ini_file($c[0]); 
define('LV_GLOBAL_CONFIG',$CONFIG);
 
date_default_timezone_set($CONFIG['timezone']);

  
// website url
$GLOBALS['V_Y']['site_url'] = $GLOBALS['V_Y']['extra_config']->site_url;
$GLOBALS['V_Y']['u_scheme'] = $_SERVER['SERVER_PORT'] != 443 ? 'http://' :  'https://';

// version
$GLOBALS['V_Y']['version'] = mt_rand();//"1.2.27";
$GLOBALS['V_Y']['license_key'] = $CONFIG['st__PURCHASE_KEY'];
$GLOBALS['V_Y']['blank'] = $CONFIG['st__DEFAULT_BLANK'];
$GLOBALS['V_Y']['original_theme_name'] = $CONFIG['st__ORIGINAL_THEME_NAME'];

// ports
$GLOBALS['V_Y']['port']['media_server'] = $CONFIG['st__MEDIA_SERVER'];
$GLOBALS['V_Y']['port']['media_server_tls'] = $CONFIG['st__MEDIA_SERVER_TLS'];
$GLOBALS['V_Y']['port']['rtmp_http'] = $CONFIG['st__RTMP_HTTP'];
$GLOBALS['V_Y']['port']['rtmp_https'] = $CONFIG['st__RTMP_HTTPS'];
$GLOBALS['V_Y']['port']['rtmp'] = $CONFIG['st__RTMP_PORT'];
$GLOBALS['V_Y']['port']['rtmp_tls'] = $CONFIG['st__RTMP_PORT_TLS'];
$GLOBALS['V_Y']['port']['rtmp_nginx'] = $CONFIG['st__NGINX_RTMP'];
$GLOBALS['V_Y']['port']['rtmp_nginx_tls'] = $CONFIG['st__NGINX_RTMP_TLS'];



$GLOBALS['V_Y']['nodejs_server']['port'] = $CONFIG['st__SERVER_PORT']; 
$GLOBALS['V_Y']['nodejs_server']['url'] = $CONFIG['st__APP_SERVER_URL'];
$GLOBALS['V_Y']['nodejs_server']['full_url'] = "{$CONFIG['st__APP_SERVER_URL']}:{$CONFIG['st__SERVER_PORT']}";
$GLOBALS['V_Y']['nodejs_server']['RTMP_URL'] = "{$GLOBALS['V_Y']['u_scheme']}{$CONFIG['st__APP_SERVER_URL']}:{$CONFIG['st__RTMP_HTTPS']}"; 



// sql tables
define('VY_LV_TBL', array(	'COMMENTS' => $CONFIG['tbl_comments'],
							'USERS' => $CONFIG['tbl_users'],
							'BROADCASTS'=> $CONFIG['tbl_vy_lv_broadcasts'],
							'CONF'=> $CONFIG['tbl_vy_lv_config'],
							'NOTIF'=> $CONFIG['tbl_notif'],
							'PAGES' => $CONFIG['tbl_pages'],
							'GROUPS' => $CONFIG['tbl_groups'], 
							'WO_PRODUCTS' => $CONFIG['tbl_products'],
							'WO_PRODUCTS_MEDIA' => $CONFIG['tbl_products_files'],
							'PRODUCTS' => $CONFIG['tbl_vy_lv_products'],
							'PRODUCTS_CART' => $CONFIG['tbl_products_cart'], 
							'POSTS'=> $CONFIG['tbl_posts']));
 
// reactions
define('VY_LV_REACTIONS',array(			array("id" => 1, "class" => "__like", "name" => "Like"),
										array("id" => 2, "class" => "__heart", "name" => "Love"),
										array("id" => 3, "class" => "__haha", "name" => "HaHa"),
										array("id" => 4, "class" => "__wow", "name" => "WoW"),
										array("id" => 5, "class" => "__cry", "name" => "Sad"),
										array("id" => 6, "class" => "__angry", "name" => "Angry"),
										));

$GLOBALS['V_Y']['plugin_assets'] = $CONFIG['st__PLUGIN_ASSETS'];
$GLOBALS['V_Y']['host'] = $GLOBALS['V_Y']['site_url'];

$GLOBALS['V_Y']['sounds'] = array( "success" => $GLOBALS['V_Y']['plugin_assets']. DIRECTORY_SEPARATOR . $CONFIG['st__success'],
										"countdown2" => $GLOBALS['V_Y']['plugin_assets']. DIRECTORY_SEPARATOR . $CONFIG['st__countdown2'],
										"countdown" => $GLOBALS['V_Y']['plugin_assets']. DIRECTORY_SEPARATOR . $CONFIG['st__countdown'],
										"click" => $GLOBALS['V_Y']['plugin_assets']. DIRECTORY_SEPARATOR . $CONFIG['st__clickuibut'], 
										"openpopup" => $GLOBALS['V_Y']['plugin_assets']. DIRECTORY_SEPARATOR . $CONFIG['st__openpopup']);
 
 
// Record streams
$GLOBALS['V_Y']['record'] = array("recording" => $CONFIG['st__recording'], // record bool true|false
									   "record_type" => $CONFIG['st__record_type'], // allowed format > [.mp4 or .webm], record video format webm or mp4 [this is only for local device streaming]
									   "mp4_high_quality" => $CONFIG['st__mp4_high_quality'], // this only works if record_type option is set to .mp4 value,
																	// if you enable this to true the video size will increase x4, 
																	// for example a 1 minute video size will be almost 100MB
									   "record_path" => $CONFIG['st__STORAGE_DIR'],
									   "obs_enabled" => $CONFIG['st__rtmp_enabled'],
									   "away_desktop" => $CONFIG['st__away_desktop'],
									   "reconnecting" => $CONFIG['st__reconnecting_notif'],
									   "audioBitsPerSecond" => $CONFIG['st__audioBitsPerSecond'],
									   "videoBitsPerSecond" => $CONFIG['st__videoBitsPerSecond'],
									   "fr_miliseconds" => $CONFIG['st__fr_miliseconds'],// the number of milliseconds to record into each Blob
									   "p_path" => getcwd() . DIRECTORY_SEPARATOR,
									   "stream_secret" => $CONFIG['st__stream_secret'], // stream secret, any word
									   "stream_key_prefix" => $CONFIG['st__stream_prefix'], // stream key prefix
									   "ffmpeg_path"=> $CONFIG['st__ffmpeg_path'], // define ffmpeg location [ $ whereis ffmpeg ]
									   "app_name"=> $CONFIG['st__app_name'], // define app name 
									   "hls"=>$CONFIG['st__hls'], // hls bool true/false
									   "hlsFlags"=>$CONFIG['st__hlsFlags'], // hls options
									   "dash"=> $CONFIG['st__dash'], // dash bool true/false
									   "dashFlags"=>$CONFIG['st__dashFlags'], // dash options
									   "mp4Flags" => $CONFIG['st__mp4Flags'], // mp4 options 
									   "storage" => $CONFIG['st__MEDIA_STORAGE'],
									   "b2_key_id" => $CONFIG['st__B2_KEY_ID'],
									   "b2_app_key" => $CONFIG['st__B2_MASTER_APPLICATION_KEY'],
									   "b2_bucket_id" => $CONFIG['st__B2_BUCKET_ID'],
									   "b2_bucket_name" => $CONFIG['st__B2_BUCKET_NAME'],
									   "s3_key_id" => $CONFIG['st__S3_KEY_ID'],
									   "s3_secret_key" => $CONFIG['st__S3_SECRET_ACCESS_KEY'],
									   "s3_bucket_name" => $CONFIG['st__S3_BUCKET_NAME'],
									   "s3_region" => $CONFIG['st__S3_REGION']
									   
									   );
									   
$GLOBALS['V_Y']['record']['media_root'] = $GLOBALS['V_Y']['record']['record_path'] . DIRECTORY_SEPARATOR;
$GLOBALS['V_Y']['record']['host'] = $_SERVER["SERVER_NAME"];
$GLOBALS['V_Y']['record']['tables'] = call_user_func(function() {
													 $tbls = array();
													 foreach(VY_LV_TBL as $key => $value ){  $tbls['VY_LV_TBL_'.$key] = $value; }
													 return $tbls;
												  });

 			   
									   