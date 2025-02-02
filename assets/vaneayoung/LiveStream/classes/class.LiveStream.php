<?php

/*

Plugin Live Stream
email: movileanuion@gmail.com 
Copyright 2022 by Vanea Young 

*/

require_once "class.core.php";

class LIVE_STREAM extends VY_LIVESTREAM_CORE
{
    public $userid = 0;
    public $now;
    public $id;
    public $action;
    public $categ;
    public $page_id;
    public $group_id;

    public function __construct()
    {
 

        //the old building from parent class
        parent::__construct();
        $this->userid = $this->USER["id"];
        $this->now = time();

        $this->id = isset($_POST["id"])
            ? $this->test_input($_POST["id"])
            : (isset($_GET["id"])
                ? $this->test_input($_GET["id"])
                : 0);
        $this->page_id = isset($_POST["page_id"])
            ? $this->test_input($_POST["page_id"])
            : (isset($_GET["page_id"])
                ? $this->test_input($_GET["page_id"])
                : 0);
        $this->group_id = isset($_POST["group_id"])
            ? $this->test_input($_POST["group_id"])
            : (isset($_GET["group_id"])
                ? $this->test_input($_GET["group_id"])
                : 0);
        $this->action = isset($_POST["action"])
            ? $this->test_input($_POST["action"])
            : (isset($_GET["action"])
                ? $this->test_input($_GET["action"])
                : "");
        $this->categ = isset($_POST["categ"])
            ? $this->test_input($_POST["categ"])
            : (isset($_GET["categ"])
                ? $this->test_input($_GET["categ"])
                : "");
    }
    public function deleteShortVideos()
    {
        $resp = 0;
        $post_id = $this->id;
        $broadcast_id = $this->post_vars("broadcast_id");
        $filename = $this->post_vars("filename");
        $file_type = $this->post_vars("file_type");
        $cover = $post_id . ".png";

        $q = $this->db->query(
            "select `user_id` from " .
                VY_LV_TBL["BROADCASTS"] .
                " where `id`='{$broadcast_id}' limit 1"
        );
        $r = $q->fetch_array(MYSQLI_ASSOC);

        if (isset($r["user_id"]) && $r["user_id"] == $this->userid) {
            // delete post
            $delete_post = $this->deletePost($post_id);
            // delete broadcast
            $delete_broadcast = $this->deleteBroadCast($broadcast_id);
            // delete comments
            $delete_comments = $this->deleteComments($post_id);

            // delete files
            //sleep(20); // wait 20 seconds
            $this->deleteFiles($filename, $cover);

            $resp = 1;
        }

        echo $resp;
    }
    public function delete_crashed(){
 
        $resp = 0;
 
        $q = $this->db->query(
            "select `id`,`stream_name` from " .
                VY_LV_TBL["BROADCASTS"] .
                " where `post_id`='{$this->id}' limit 1"
        );
        $r = $q->fetch_array(MYSQLI_ASSOC);

            // delete post
            $delete_post = $this->deletePost($this->id);
            // delete broadcast
            $delete_broadcast = $this->deleteBroadCast($r['id']);
            // delete comments
            $delete_comments = $this->deleteComments($this->id);

            // delete files
            $this->removeFiles($r['stream_name']);
 
            $resp = 1;

        echo $resp; 
           }
    public function removeFiles($filename = '')
    {
        $post_id = $this->id;
        $filename = !empty($filename) ? $filename : $this->post_vars("filename");
        $cover = $post_id . ".png";

        $this->deleteFiles($filename, $cover);
        return true;
    }
    public function deleteFiles($stream_filename, $cover)
    {
        $stream =
            sprintf($this->upload_path_blobs, $this->USER["id"]) .
            $stream_filename;
        $cover = sprintf($this->upload_path_covers, $this->USER["id"]) . $cover;

        // delete stream
        if (!empty($stream_filename) && file_exists($stream . ".webm")) {
            unlink($stream . ".webm");
        } elseif (!empty($stream_filename) && file_exists($stream . ".mp4")) {
            unlink($stream . ".mp4");
        }

        // delete cover
        if (file_exists($cover)) {
            unlink($cover);
        }

        return true;
    }
    public function delete_stream($id = 0){

        $this->deleteBroadCast($id);
        $this->deletePost($id);
        $this->deleteComments($id);

    }
    public function deleteBroadCast($id = 0)
    {
        if (
            $this->query_delete(
                "delete from " . VY_LV_TBL["BROADCASTS"] . " where `id`='{$id}'"
            )
        ) {
            return true;
        } else {
            return false;
        }
    }
    public function deletePost($id = 0)
    {
        return PT_DeleteVideo($id);
    }
    public function deleteComments($post_id = 0)
    {
        if (
            $this->query_delete(
                "delete from " .
                    VY_LV_TBL["COMMENTS"] .
                    " where `post_id`='{$post_id}'"
            )
        ) {
            return true;
        } else {
            return false;
        }
    }
    public function generateUniqueStreamKey()
    {
        $r = $this->lang["error_generating_stream_key"];
        $user_id = $this->USER["id"];
        $timestamp = ((time() / 1000) | 0) + 10;
        $key = md5($timestamp) . "--" . md5($this->USER["id"]);

        if (
            $this->query_update(
                "update " .
                    VY_LV_TBL["USERS"] .
                    " set `vy-live-streamkey`='{$key}' where `user_id`='{$user_id}'"
            )
        ) {
            $r = $key;
        }

        return $r;
    }

    public function getUserSstreamKey()
    {
        $user_id = $this->USER["id"];
        $q = $this->db->query(
            "select `vy-live-streamkey` from " .
                VY_LV_TBL["USERS"] .
                " where `user_id`='{$user_id}' limit 1"
        );
        $r = $q->fetch_array(MYSQLI_ASSOC);

        return $r["vy-live-streamkey"];
    }
    public function constructPage()
    {
        global $__svgI;

        if (!$this->checking()) {
            header("location: /livestream");
            exit();
        }

        $this->template->assign([
            "this" => $this,
            "wo" => $GLOBALS['V_Y'],
            "i" => $this->userid,
        ]);
        $content = $this->template->fetch($this->theme_dir . "/index.html");

        echo $this->getPage($content);
    }
    public function getViewers()
    {
 
        $users = isset($_POST["users"])
            ? json_decode($_POST["users"], true)
            : [];

        $this->template->assign([
            "this" => $this,
            "wo" => $GLOBALS['V_Y'],
            "live_id" => $this->id,
            "i" => $this->userid,
            "users" => $users,
        ]);
        $content = $this->template->fetch($this->theme_dir . "/viewers.html");

        echo $this->getPage($content);
    }
    public function availableViewersForModerator()
    {
 
        $users = isset($_POST["users"])
            ? json_decode($_POST["users"], true)
            : [];

        $this->template->assign([
            "this" => $this,
            "wo" => $GLOBALS['V_Y'],
            "live_id" => $this->id,
            "i" => $this->userid,
            "users" => $users,
        ]);
        $content = $this->template->fetch(
            $this->theme_dir . "/add-moderators.html"
        );

        echo $this->getPage($content);
    }
    public function removeModerators()
    {
 
        $users = isset($_POST["users"])
            ? json_decode($_POST["users"], true)
            : [];

        $this->template->assign([
            "this" => $this,
            "wo" => $GLOBALS['V_Y'],
            "live_id" => $this->id,
            "i" => $this->userid,
            "users" => $users,
        ]);
        $content = $this->template->fetch(
            $this->theme_dir . "/remove-moderators.html"
        );

        echo $this->getPage($content);
    }

    public function getPopup()
    {
        $value = $this->post_vars("value");

        $p = "/popups/not-found.html";
        $title = "404 Error";
        $arr = [];
        switch ($this->categ) {
            case "select-privacy":
                $title = $this->lang["post_privacy"];
                $p = "/popups/select-privacy.html";
                $arr = $this->getPrivacyOpts();
                break;
            case "live-settings":
                $title = $this->lang["live_settings"];
                $p = "/popups/live-settings.html";
                break;

            case "":
            default:
                $p = $p;
        }

        $this->template->assign([
            "this" => $this,
            "arr" => $arr,
            "value" => $value,
            "title" => $title,
            "i" => $this->userid,
        ]);
        $content = $this->template->fetch($this->theme_dir . $p);

        echo $this->getPage($content);
    }
    public function checking()
    {
        if (
            !isset($this->USER["id"]) ||
            !$this->USER["id"] ||
            $this->USER["id"] <= 0
        ) {
            header("location: /");
            exit();
        }
        
        if ( ($this->group_id > 0 && !$this->doesGroupExists($this->group_id)) || ($this->page_id > 0 && !$this->doesPageExists($this->page_id)) )
            return false;


        return true;
    }
    public function productShippingContinents($data = array()){

        $all = json_decode($this->continents_countries(),true);
        $generate_continents = $continents = array();
        $template = $last_continent = "";
        if($this->isJson($data)){
            $data = json_decode($data,true);

            for($x = 0; $x < count($data); $x++){
                for($i = 0; $i < count($all); $i++){
                    $countries = $all[$i]['countries'];
                        for($k=0;$k<count($countries);$k++){
                            if(strtolower($data[$x]) == strtolower($countries[$k]['code'])){
                                $generate_continents[$all[$i]['name']][] = $countries[$k];
                            }

                        }

 
            }



            }

        }

 
        foreach(array_keys($generate_continents) as $continent) {

            if($last_continent != $continent){
                $continents[] = '<a href="javascript:void(0);" rel="#'.md5($continent).'" onclick="showCountriesOfContinent(event,this);">'.$continent.'&nbsp;<i class="fa fa-chevron-down GSSIm2 isdown" aria-hidden="true"></i></a>';
                $template .= '<div class="vylvproducontin2fFhP2__D1" id="'.md5($continent).'">';
            }

            foreach($generate_continents[$continent] as $country_data){
                $template .= '<a target="_blank" href="//google.com/search?q='.urlencode($country_data['name']).'" class="vylv_prod32ld11">'.$country_data['name'].' <i class="flag-icon flag-icon-'.strtolower($country_data['code']).' mr-1" style="margin-left:1px;"></i></a>';
            }

            if($last_continent != $continent)
                $template .= "</div>";
 
                $last_continent = $continent;
        }

        $template = implode("&nbsp;&bull;&nbsp;",$continents). $template;
        
        return $template;

    }
    public function goLive()
    {
        $broadcast_filename = "";
        $descr = $this->post_vars("descr");
        $title = $this->post_vars("title");
        $privacy = $this->post_vars("privacy");
        $obs = $this->post_vars("obs");
        $obs_stream_name = $this->post_vars("stream_name");
        $post_to_timeline = $this->post_vars("post_to_timeline");
        $contain_product = false;
        $product_template = null;
        $is_vid = false;
        $product_data = isset($_POST['product']) && $_POST['product'] != null && $this->isJson($_POST['product']) ? json_decode($_POST['product'],true) : false;
 
        if($product_data && $this->isarray($product_data) && count($product_data) > 0 && $product_data['id'] > 0){

            $contain_product = true;

            $p_name = $this->test_input($product_data['name']);
            $p_descr = $this->db->real_escape_string($product_data['descr']);
            $p_contact_nr = $this->test_input($product_data['contact_number']);
            $p_price = $this->test_input($product_data['price']);
            $p_discount_price = $this->test_input($product_data['discount_price']);
            $p_units = $this->test_input($product_data['units']);
            $p_categ = $this->isarray($product_data['categ']) && count($product_data['categ']) ? $product_data['categ'] : [];
            $p_location = $this->test_input($product_data['location']);
            $p_condition = $this->test_input($product_data['condition']);
            $p_countries = $this->isarray($product_data['countries']) && count($product_data['countries']) ? $product_data['countries'] : [];
            $p_files = $this->isarray($product_data['files']) && count($product_data['files']) ? $product_data['files'] : [];
            $wo_p_discount_price = $p_discount_price > 0 ? $p_discount_price : $p_price;

            if(count($p_files)){

                $p_files_positioning = $p_files_db = array();
                for($i=0;$i<count($p_files);$i++) {

                    $is_vid = explode('.',$p_files[$i]);
                    $is_vid = end($is_vid);

                    if($is_vid == 'mp4'){

                        $video = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . $this->getProductFilesPath($this->userid,0,1) . $p_files[$i];
                        $thumbnail_folder = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . $this->getProductFilesPath($this->userid,1,1);
                        $thumbnail = $thumbnail_folder . basename($p_files[$i], 'mp4') . 'jpg';

                        # create thumbnail dir
                        if (!file_exists($thumbnail_folder)) 
                            mkdir($thumbnail_folder,0755,true);
        
                        @exec("ffmpeg -i {$video} -an -ss 1 -t 00:00:05 -r 1 -y -vcodec mjpeg -f mjpeg {$thumbnail} 2>&1", $exec_output, $exec_return);
                        sleep(3);
                        if(!file_exists($thumbnail)){ 
                            $blank_img_data = $this->rs_slider_video_blank();

                            list($type, $blank_img_data) = explode(';', $blank_img_data);
                            list(, $blank_img_data)      = explode(',', $blank_img_data);
                            $blank_img_data = base64_decode($blank_img_data);

                            file_put_contents($thumbnail, $blank_img_data);
                        }

                    } 

                    $fname = $p_files[$i];  
                    $fpos = $i+1;
                    $p_files_positioning[] = ["filename" => $fname,"thumb" => $this->getProductFilesPath($this->userid,1) . DIRECTORY_SEPARATOR . ($is_vid == 'mp4' ? basename($p_files[$i], 'mp4') . 'jpg' : $p_files[$i]), "position" => $fpos];

                    $p_files_db[] = ["filename" => $fname, "position" => $fpos];
                }


                $p_files = $p_files_db;
                $product_data['files'] = $p_files_positioning;

            }

        } 



        $now = time();
        $date = date("n/Y");
        $time_date = date("n/Y");

        switch ($privacy) {
            case "1":
                $privacy = "0";
                break;

            case "2":
                $privacy = "2";
                break;

            case "3":
                $privacy = "1";
                break;

            case "4":
                $privacy = "3";
                break;

            case "5":
                $privacy = "4";
                break;
        }

        if (trim($title) && strlen($title) > 0) {
            $descr = "<strong>" . $title . "</strong><br/>" . $descr;
        }
        $video_id = PT_GenerateKey(15, 15);
   
        // create live post
        $insert = $this->query_insert(
            "insert into " .
                VY_LV_TBL["POSTS"] .
                " set `video_id`='{$video_id}', `video_location`='{$obs_stream_name}',`title`='{$title}',`time_date`=NOW(),`time`='{$now}',`user_id`='{$this->userid}',`registered`='{$time_date}',`type`='live',`vy-live`='yes'"
        );

        if ($insert) {

            // add live to broadcasts table
            $add_broadcast = $this->query_insert(
                "insert into " .
                    VY_LV_TBL["BROADCASTS"] .
                    " set `obs`='{$obs}',`islivenow`='yes',`stream_name`='{$obs_stream_name}',`post_id`='{$insert}',`added`='{$now}',`user_id`='{$this->userid}'"
            );

            $broadcast_filename = md5($insert);
            $update_filename = $this->query_update("update " . VY_LV_TBL["BROADCASTS"] . " set `stream_name`='{$broadcast_filename}' where `id`='{$insert}'");

            // send notification
            pt_push_channel_notifiations($insert,'started_live_video');


            // attach product 
            if($contain_product){


                $add_product = $this->query_insert(
                "INSERT INTO " .
                    VY_LV_TBL["PRODUCTS"] ." SET 
                    `user_id`='{$this->userid}',
                    `post_id`='{$insert}',
                    `broadcast_id`='{$add_broadcast}',
                    `name`='{$p_name}',
                    `added`='{$now}',
                    `descr`='{$p_descr}',
                    `contact`='{$p_contact_nr}',
                    `price`='{$p_price}',
                    `units`='{$p_units}',
                    `location`='{$p_location}',
                    `condition`='{$p_condition}',
                    `discount_price`='{$p_discount_price}',
                    `categ`='{$this->jencode($p_categ)}',
                    `shipping_countries`='{$this->jencode($p_countries)}',
                    `files`='{$this->jencode($p_files)}' ");

                    if($add_product) {
                        $product_data['id'] = $add_product;
                   
                        $basic_category = 1;
                        // insert into wowonder table
                        $add_product_wo = $this->query_insert(
                                                    "INSERT INTO " .
                                                        VY_LV_TBL["WO_PRODUCTS"] ." SET 
                                                        `user_id`='{$this->userid}',
                                                        `name`='{$p_name}',
                                                        `time`='{$now}',
                                                        `description`='{$p_descr}',
                                                        `category`='{$basic_category}',
                                                        `price`='{$wo_p_discount_price}',
                                                        `units`='{$p_units}',
                                                        `location`='{$p_location}',
                                                        `type`='{$p_condition}',
                                                        `active`='1',
                                                        `vy_product_id`='{$add_product}'");

                        if($add_product_wo){
                            for($i=0;$i<count($p_files);$i++):

                                $file = $p_files[$i]['filename'];

                                $is_vid = explode('.',$file);
                                $is_vid = end($is_vid);

                                if($is_vid == 'mp4'){
                                    $file = $this->getProductFilesPath($this->userid,1,1)  .basename($file, 'mp4') . 'jpg';
                                } else {
                                    $file = $this->getProductFilesPath($this->userid,0,1)  . $file;
                                }

                                if($is_vid != 'mp4')
                                    $this->query_insert("INSERT INTO ".VY_LV_TBL['WO_PRODUCTS_MEDIA']." SET `product_id`='{$add_product_wo}',`image`='{$file}'");
                            
                            endfor;

                            // add post
                            $post_reg = date('m/Y');
                            $post_for_prod_id = $this->query_insert("insert into ".VY_LV_TBL['POSTS']." set `user_id`='{$this->userid}',`postType`='post',`time`='{$now}',`product_id`='{$add_product_wo}',`registered`='{$post_reg}'");

                            if($post_for_prod_id > 0)
                                $this->query_update("update ".VY_LV_TBL['POSTS']." set `post_id`='{$post_for_prod_id}' where `id`='{$post_for_prod_id}'");


                        }


                        $this->template->assign([
                            "this" => $this,
                            "id" => $add_product,
                            "name" => $p_name,
                            "views" => 0,
                            "price" => !empty($p_discount_price) && $p_discount_price > 0 ? $p_discount_price : $p_price,
                            "image" => $product_data['files'][0]['thumb'],
                            "seller_url" => $this->get_full_url()."/livestream/u/".$this->userid,
                            "seller_name" => $this->USER['fullname']
                        ]);
                        $product_template = $this->template->fetch($this->theme_dir . "/product_template.html");

              }


            }

            $this->generateBlankCover($insert);

        }

        echo $this->jencode([
            "post_id" => $insert,
            "broadcast_id" => $add_broadcast,
            "filename" => $broadcast_filename,
            "product" => $product_data,
            "product_template" => $product_template 
        ]);
    }
    public function getProductsSugg(){

        $r = $rl = [];
         $q = $this->query_select(
                "Select `name` from " .
                    VY_LV_TBL["PRODUCTS"] .
                    " where `user_id`='{$this->userid}' order by added desc limit 300"
            );
        
        foreach($q as $rr){

            $r[] = $rr['name'];
            $rl[] = strtolower($rr['name']);
        }


        echo $this->jencode(['lower' => $rl,'original' => $r]);

    }
    public function generateBlankCover($id = 0){

        $thumbnail_dir = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . sprintf($this->upload_path_covers, $this->USER["id"]);
        $thumbnail = $thumbnail_dir . $id . ".png";

        if(!file_exists($thumbnail)){ 
            $blank_img_data = $this->rs_slider_video_blank();

            list($type, $blank_img_data) = explode(';', $blank_img_data);
            list(, $blank_img_data)      = explode(',', $blank_img_data);
            $blank_img_data = base64_decode($blank_img_data);

            file_put_contents($thumbnail, $blank_img_data);
                            
      
        $thumbnail = str_replace($_SERVER['DOCUMENT_ROOT']. DIRECTORY_SEPARATOR,'',$thumbnail);
            // add live to broadcasts table
         $update_broadcast = $this->query_update(
                "update " .VY_LV_TBL["BROADCASTS"] ." set `live-cover`='{$thumbnail}' where `post_id`='{$id}'"
         );
         /*
        $update_videos = $this->query_update(
                "update " .VY_LV_TBL["POSTS"] ." set `thumbnail`='{$thumbnail}' where `id`='{$id}'"
        );*/
         }
    }
    public function generateCover(){
 
        
            $cover = $_POST["cover"];
            // generate cover
            $cover = str_replace("data:image/png;base64,", "", $cover);
            $cover = str_replace(" ", "+", $cover);
            $data = base64_decode($cover);
            $cover_file = $this->id . ".png";
            $success = file_put_contents(
                sprintf($this->upload_path_covers, $this->USER["id"]) .
                    $cover_file,
                $data
            );

            $thumbnail_path = DIRECTORY_SEPARATOR . sprintf($this->upload_path_covers, $this->USER["id"]) . $cover_file;
            
            // add live to broadcasts table
            $update_broadcast = $this->query_update(
                "update " .VY_LV_TBL["BROADCASTS"] ." set `live-cover`='{$cover_file}' where `post_id`='{$this->id}'"
            );
            $update_videos = $this->query_update(
                "update " .VY_LV_TBL["POSTS"] ." set `thumbnail`='{$thumbnail_path}' where `id`='{$this->id}'"
            );
            if($update_broadcast)
                echo 1;
            else echo 0;
        
        
    }
    public function renameObsFile()
    {
        $path = $this->post_vars("path");
        $filename = $this->post_vars("filename");

        $response = ["success" => 0, "filename" => $filename];

        if (rename($path, str_replace("writing", $filename, $path))) {
            $response["success"] = 1;
        }

        echo $this->jencode($response);
    }

    public function stopLive()
    {
        $post_id = $this->post_vars("post_id");
        $broadcast_id = $this->post_vars("broadcast_id");
        $time = $this->post_vars("time");
        $post_to_timeline = $this->post_vars("post_to_timeline");
        $file_type = $this->post_vars("file_type");
        $filename = $this->post_vars("filename") . "." . $file_type;
        $video_location = $this->generateFullFilePath(array("storage" => "", "stream_name" => $filename, "user_id" => $this->USER['id']));
        $update = $update2 = true;

        if (!$this->recording || $post_to_timeline == "no") {
            // delete post
            $delete_post = $this->deletePost($post_id);
            // delete broadcast
            $delete_broadcast = $this->deleteBroadCast($broadcast_id);
            // delete comments
            $delete_comments = $this->deleteComments($post_id);
        } else {
            $broadcast_time = $this->mediaTimeDeFormater($time);

            $update2 = $this->query_update(
                "update " .
                    VY_LV_TBL["POSTS"] .
                    " SET `video_location`='{$video_location}',`duration`='{$broadcast_time}',`live_time`='{$time}',`live_ended`='1' where `id`='{$post_id}'"
            );
            $update3 = $this->query_update(
                "update " .
                    VY_LV_TBL["BROADCASTS"] .
                    " set `islivenow`='no',`ended`='yes',`stream_name`='{$filename}',`time`='{$time}' where `id`='{$broadcast_id}'"
            );
         }



        // re-generate the stream keys for OBS
        $this->generateUniqueStreamKey();

        if ($update && $update2) {
            echo 1;
        } else {
            echo 0;
        }
    }
    public function getRtmpHLS_Path(){
        return $GLOBALS['V_Y']['host'] . DIRECTORY_SEPARATOR .sprintf($this->upload_path_blobs, $this->id) . "index.m3u8";
    }
    public function getBroadcastData($post_id = 0)
    {global $pt;

        $data = ["full_cover_path" => null, "full_file_path" => null];

        if ($post_id > 0) {
            $q = $this->db->query(
                "Select * from " .
                    VY_LV_TBL["BROADCASTS"] .
                    " where `post_id`='{$post_id}' limit 1"
            );
            $data = $q->fetch_array(MYSQLI_ASSOC);

            if (isset($data["user_id"]) && isset($data["stream_name"])) {
                $data["full_file_path"] =
                    sprintf($this->upload_path_blobs, $data["user_id"]) .
                    $data["stream_name"];
            }
                        
            if (isset($data["user_id"]) && isset($data["live-cover"])) {
                
                if(empty($data["live-cover"])){
                $data["full_cover_path"] = $pt->vy['site_url'] . $pt->vy['vy-livestream']['blank'];
                } else {
                
                $data["full_cover_path"] = $data['full_cover_url'] = 
                    sprintf($this->upload_path_covers, $data["user_id"]) .
                    $data["live-cover"];
                    
                }
            }
        }

        return $data;
    }


















    public function generateFullFilePath($data){
 
        $p = '';

        switch($data['storage']){


            case 'b2':
            $p = sprintf(LV_GLOBAL_CONFIG['st__B2_URL_EXAMPLE'],LV_GLOBAL_CONFIG['st__B2_BUCKET_NAME'],$data["user_id"],$data["stream_name"]);
            break;
            case 's3':
            $p = sprintf(LV_GLOBAL_CONFIG['st__S3_URL_EXAMPLE'],$this->settings['s3_bucket_name'],$this->settings['s3_region'],$data['user_id'],$data["stream_name"]);
            break;
            case 'default':
            default:
            $p = DIRECTORY_SEPARATOR . sprintf($this->upload_path_blobs, $data["user_id"]) .
                    $data["stream_name"];


        }

        return $p;


    }
    public function AddComment()
    {
        $post_id = $this->post_vars("post_id");
        $text = $this->post_vars("text");

        if (
            !$this->isLogged() ||
            !is_numeric($post_id) ||
            $post_id <= 0 ||
            !trim($text) ||
            empty($text)
        ) {
            die();
        }

        $query = $this->query_insert(
            "insert into " .
                VY_LV_TBL["COMMENTS"] .
                " set `text`='{$text}',`post_id`='{$post_id}',`time`='{$this->now}',`user_id`='{$this->userid}'"
        );

        if ($query) {
            return true;
        } else {
            return false;
        }
    }
    public function isLiveExists($id)
    { 
        $q = $this->db->query(
            "select COUNT(*) from " . VY_LV_TBL["POSTS"] . " where `id`='{$id}' limit 1"
        );
        $r = $q->fetch_row();

        return $r[0];
    }
    public function isLiveNow($post_id)
    {
        $q = $this->db->query(
            "select COUNT(*) from " . VY_LV_TBL["BROADCASTS"] . " where `post_id`='{$post_id}' && `islivenow`='yes' limit 1"
        );
        $r = $q->fetch_row();

        return $r[0];
    }
    public function getTopProduct($broadcast_id = 0) {

        $data = array("template" => "", "row" => []);

        // check for product and select product data
        if ($broadcast_id > 0){
            $query_product = $this->query_select("select * from ".VY_LV_TBL["PRODUCTS"]." where `broadcast_id`='{$broadcast_id}' limit 1");
            $is_vid = false;
            if(count($query_product)){
                
                
                foreach($query_product as $row):

                 $user_details = $this->lv_getUserDetails($row["user_id"]);
                 $data['product'] = array("query" => $query_product, "files_path" => $this->getProductFilesPath($row["user_id"]));
                 $data['product_id'] = $row['id'];
                 $thumbnail = $this->blankimage(); 

                 $files = json_decode($row['files'],true);
                 for($i=0;$i<count($files);$i++){
                    $file = $files[$i];

                    $is_vid = explode('.',$file['filename']);
                    $is_vid = end($is_vid);

                    
                    if($file['position'] <= 1)
                        $thumbnail = $this->getProductFilesPath($row["user_id"],1). ($is_vid == 'mp4' ? basename($file['filename'], 'mp4') . 'jpg' : $file['filename']);

                 }   
                 $this->template->assign([
                            "this" => $this,
                            "id" => $row['id'],
                            "name" => $row['name'],
                            "views" => $row['views'] > 0 ? $row['views'] : 0,
                            "price" => !empty($row['discount_price']) && $row['discount_price'] > 0 ? $row['discount_price'] : $row['price'],
                            "image" => $thumbnail,
                            "seller_url" => $this->get_full_url()."/livestream/u/".$row["user_id"],
                            "seller_name" => $user_details['fullname']
                        ]);
                $data['template'] =  $this->template->fetch($this->theme_dir . "/product_template.html");
                $data['row'] = $row;

                    
                    

                endforeach;

            }

        }

        return $data;


    }
    public function joinLive()
    {
        $data = ["error" => 0, "error_code" => 0, "product" => null, "product_id" => 0];
        $post_id = $this->post_vars("id");
        $mobile = $this->post_vars("mobile");
        $cover_path = $stream_path = '';
        if (!$this->isLiveExists($post_id)) {
            echo $this->jencode(["error" => 1, "error_code" => 404]);
            exit();
        }

        $query = $this->db->query(
            "select * from " . VY_LV_TBL["POSTS"] . " where `id`='{$post_id}' limit 1"
        );
        $post_data = $query->fetch_array(MYSQLI_ASSOC);

        // select data from live broadcast table
        $query2 = $this->db->query(
            "select * from " .
                VY_LV_TBL["BROADCASTS"] .
                " where `post_id`='{$post_id}' limit 1"
        );
        $rows = $query2->fetch_array(MYSQLI_ASSOC);
        $user_details = $this->lv_getUserDetails($rows["user_id"]);
        // get last 15 comments
        $comments = $this->query_select(
            "
    
                select * from (
                select * from " .
                VY_LV_TBL["COMMENTS"] .
                " where `post_id`='{$post_id}' order by id desc limit 15
            ) tmp order by tmp.id asc ");

        $product_dt = $this->getTopProduct($rows['id']);

        if(count($product_dt['row']) && isset($product_dt['row']['id']) && $product_dt['row']['id'] > 0){
            $data['product_template'] = $product_dt['template'];
            $data['product_id'] = $product_dt['row']['id'];
            $data['product'] = $product_dt['row'];
        }
        if (isset($rows["user_id"]) && isset($rows["live-cover"])) {
            $cover_path =
                sprintf($this->upload_path_covers, $rows["user_id"]) .
                $rows["live-cover"];
        }

        if (isset($rows["user_id"]) && isset($rows["stream_name"])) {
            $stream_path =
                sprintf($this->upload_path_blobs, $rows["user_id"]) .
                $rows["stream_name"];
        }

        $this->template->assign([
            "this" => $this,
            "live_id" => $post_id,
            "stream_path" => $stream_path,
            "cover_path" => $cover_path,
            "rows" => $rows,
            "post" => $post_data,
            "author" => $user_details,
            "comments" => $comments,
            "i" => $this->userid,
            "id" => $post_id 
        ]);
        $content = $this->template->fetch($this->theme_dir . ($mobile == "yes" ? "/live-mob.html" : "/live-desktop.html"));
        
        $data["html"] = $content;
        $data["post"] = $rows;
        $data["post"]["stream_name"] = $rows["stream_name"];
        $data["comments"] = $comments;

        echo $this->jencode($data);
    }
    public function getProductFilesPath($uid = 0, $thumb = false, $nofullurl = false){

        return ($nofullurl ? '' : $this->get_full_url() . DIRECTORY_SEPARATOR) . sprintf($this->upload_path_products, $uid) . ($thumb ? "thumbnail" . DIRECTORY_SEPARATOR : '');

     }
    public function getNiceDuration($durationInSeconds)
    {
        $duration = "";
        $days = floor($durationInSeconds / 86400);
        $durationInSeconds -= $days * 86400;
        $hours = floor($durationInSeconds / 3600);
        $durationInSeconds -= $hours * 3600;
        $minutes = floor($durationInSeconds / 60);
        $seconds = $durationInSeconds - $minutes * 60;

        if ($days > 0) {
            $duration .= $days . "d";
        }
        if ($hours > 0) {
            $duration .=
                " " .
                $hours .
                ' <span class="vy_lv_small">' .
                $this->lang["hours"] .
                "</span>";
        }
        if ($minutes > 0) {
            $duration .=
                " " .
                $minutes .
                ' <span class="vy_lv_small">' .
                $this->lang["minutes"] .
                "</span>";
        }
        /*
  if($seconds > 0) {
    $duration .= ' ' . $seconds . 's';
  }*/
        return $duration;
    }
    public function lv_getUserDetails($uid)
    {global $pt;
        $rs = [];

        $q = $this->db->query(
            "Select * from " .
                VY_LV_TBL["USERS"] .
                " where `id`='{$uid}' limit 1"
        );
        $r = $q->fetch_array(MYSQLI_ASSOC);

        if (!isset($r["id"])) {
            return [];
        }

        $rs["fullname"] = !empty($r["first_name"])
            ? $r["first_name"] . " " . $r["last_name"]
            : $r["username"];
        $rs["id"] = $r["id"];
        $rs["avatar"] = $this->lv_get_avatar($r["avatar"]);
        $rs["name"] = empty($r["first_name"])
            ? $r["username"]
            : $r["first_name"];
        $rs["follwing_btn"] = PT_GetSubscribeButton($r["id"]);
        $rs["following_me"] = PT_GetSubscribeButton($r["id"]);

        return $rs;
    }
    public static function getSvgIcons()
    {
        global $__svgI;
        return $__svgI;
    }
    public static function getSounds()
    {

        return $GLOBALS['V_Y']["sounds"];
    }
    public function showdashboard()
    {
        $this->template->assign([
            "this" => $this,
            "id" => $this->id,
            "i" => $this->userid,
        ]);
        $content = $this->template->fetch($this->theme_dir . "/dashboard.html");
        return $content;
    }
    public function getPreLiveSt()
    {
        $this->template->assign([
            "this" => $this,
            "id" => $this->id,
            "i" => $this->userid,
        ]);
        $content = $this->template->fetch(
            $this->theme_dir . "/pre-live-settings.html"
        );
        return $content;
    }
    public static function getReactionsBtns()
    {
        return VY_LV_REACTIONS;
    }
    public static function isRecording()
    {
 
        return $GLOBALS['V_Y']["record"]["recording"] ? 1 : 0;
    }
    public static function awayDesktop()
    {
 
        return $GLOBALS['V_Y']["record"]["away_desktop"] ? true : false;
    }
    public static function productsCategs()
    {
        return file_get_contents(dirname(__DIR__, 1) . DIRECTORY_SEPARATOR ."pcategs/product-categories.json");

    }
    public static function continents_countries()
    {
        return file_get_contents(dirname(__DIR__, 1) . DIRECTORY_SEPARATOR ."json_countries/continent_countries.json");
    }
    public static function recType()
    {
        return str_replace(
            ".",
            "",
            $GLOBALS['V_Y']["record"]["record_type"]
        );
    }
    public static function recordingBits($x = "video")
    {
        return $x == "audio"
            ? $GLOBALS['V_Y']["record"]["audioBitsPerSecond"]
            : $GLOBALS['V_Y']["record"]["videoBitsPerSecond"];
    }
    public static function fr_miliseconds()
    {
        return $GLOBALS['V_Y']["record"]["fr_miliseconds"];
    }
    public static function getPrivacyOpts()
    { 
        $core = new VY_LIVESTREAM_CORE();
        return [
            "0" => [
                "id" => 1,
                "title" => $core->lang["everyone"],
                "descr" => $core->lang["everyone_info"],
                "ic" => "__everyone",
            ],
            "1" => [
                "id" => 2,
                "title" => $core->lang["people_i_follow"],
                "descr" => $core->lang["people_i_follow_info"],
                "ic" => "__p_i_follow",
            ],
            "2" => [
                "id" => 3,
                "title" => $core->lang["people_following_me"],
                "descr" => $core->lang["people_following_me_info"],
                "ic" => "__p_follow_me",
            ],
            "3" => [
                "id" => 4,
                "title" => $core->lang["only_me"],
                "descr" => "",
                "ic" => "__only_me",
            ],
            "4" => [
                "id" => 5,
                "title" => $core->lang["anonymous"],
                "descr" => "",
                "ic" => "__anonymous",
            ],
        ];
    }
    public function recording()
    {
   
        $post_id = $this->post_vars("live_id");

        // make the post invisible till the video file is merged
        $this->query_update(
            "update " .
                VY_LV_TBL["POSTS"] .
                " set `post_id`='0' where `id`='{$post_id}'"
        );

        // send user notification
        $notification_data = [
            "recipient_id" => $this->USER["id"],
            "notifier_id" => $this->getAnyNotifierId(),
            "type" => "admin_notification",
            "text" => $this->lang["we_process_your_stream"],
            "admin" => 1,
            "url" => "index.php",
        ];
        Wo_RegisterNotification($notification_data);

        // because we've different ffmpeg commands for windows & linux
        // that's why following script is used to fetch target OS
        $OSList = [
            "Windows 3.11" => "Win16",
            "Windows 95" => "(Windows 95)|(Win95)|(Windows_95)",
            "Windows 98" => "(Windows 98)|(Win98)",
            "Windows 2000" => "(Windows NT 5.0)|(Windows 2000)",
            "Windows XP" => "(Windows NT 5.1)|(Windows XP)",
            "Windows Server 2003" => "(Windows NT 5.2)",
            "Windows Vista" => "(Windows NT 6.0)",
            "Windows 7" => "(Windows NT 7.0)",
            "Windows NT 4.0" =>
                "(Windows NT 4.0)|(WinNT4.0)|(WinNT)|(Windows NT)",
            "Windows ME" => "Windows ME",
            "Open BSD" => "OpenBSD",
            "Sun OS" => "SunOS",
            "Linux" => "(Linux)|(X11)",
            "Mac OS" => "(Mac_PowerPC)|(Macintosh)",
            "QNX" => "QNX",
            "BeOS" => "BeOS",
            "OS/2" => "OS/2",
            "Search Bot" =>
                "(nuhk)|(Googlebot)|(Yammybot)|(Openbot)|(Slurp)|(MSNBot)|(Ask Jeeves/Teoma)|(ia_archiver)",
        ];
        // Loop through the array of user agents and matching operating systems
        foreach ($OSList as $CurrOS => $Match) {
            // Find a match
            if (preg_match("/" . $Match . "/i", $_SERVER["HTTP_USER_AGENT"])) {
                // We found the correct match
                break;
            }
        }

        $dir = sprintf($this->upload_path_blobs, $this->USER["id"]);

        if (!file_exists($dir)) {
            mkdir(
                $_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . $dir,
                0777,
                true
            );
        }

        // if it is audio-blob
        if (isset($_FILES["audio-blob"])) {
            $uploadDirectory = $dir . $_POST["filename"] . ".wav";
            if (
                !move_uploaded_file(
                    $_FILES["audio-blob"]["tmp_name"],
                    $uploadDirectory
                )
            ) {
                echo "Problem writing audio file to disk!";
            } else {
                // if it is video-blob
                if (isset($_FILES["video-blob"])) {
                    $uploadDirectory =
                        $dir .
                        $_POST["filename"] .
                        $GLOBALS['V_Y']["record"]["recor_type"];
                    if (
                        !move_uploaded_file(
                            $_FILES["video-blob"]["tmp_name"],
                            $uploadDirectory
                        )
                    ) {
                        echo "Problem writing video file to disk!";
                    } else {
                        $audioFile = $dir . $_POST["filename"] . ".wav";
                        $videoFile =
                            $dir .
                            $_POST["filename"] .
                            $GLOBALS['V_Y']["record"]["recor_type"];

                        $mergedFile =
                            $dir .
                            $_POST["filename"] .
                            "-merged" .
                            $GLOBALS['V_Y']["record"]["recor_type"];

                        // ffmpeg depends on yasm
                        // libvpx depends on libvorbis
                        // libvorbis depends on libogg
                        // make sure that you're using newest ffmpeg version!

                        if (!strrpos($CurrOS, "Windows")) {
                            $cmd =
                                "-i " .
                                $audioFile .
                                " -i " .
                                $videoFile .
                                " -map 0:0 -map 1:0 " .
                                $mergedFile;
                        } else {
                            $cmd =
                                " -i " .
                                $audioFile .
                                " -i " .
                                $videoFile .
                                " -c:v mpeg4 -c:a vorbis -b:v 64k -b:a 12k -strict experimental " .
                                $mergedFile;
                        }

                        exec("ffmpeg " . $cmd . " 2>&1", $out, $ret);
                        if ($ret) {
                            // the record can not be saved remove post,comments and broadcast
                            $this->query_delete(
                                "delete from " .
                                    VY_LV_TBL["POSTS"] .
                                    " where `id`='{$post_id}'"
                            );
                            $this->query_delete(
                                "delete from " .
                                    VY_LV_TBL["BROADCASTS"] .
                                    " where `post_id`='{$post_id}'"
                            );
                            $this->query_delete(
                                "delete from " .
                                    VY_LV_TBL["COMMENTS"] .
                                    " where `post_id`='{$post_id}'"
                            );

                            // send user notification
                            $notification_data = [
                                "recipient_id" => $this->USER["id"],
                                "notifier_id" => $this->getAnyNotifierId(),
                                "type" => "admin_notification",
                                "text" =>
                                    "Your previous live stream was not saved, and the post has been removed permanently.",
                                "admin" => 1,
                                "url" => "index.php",
                            ];
                            Wo_RegisterNotification($notification_data);

                            echo "There was a problem!\n";
                            print_r($cmd . '\n');
                            print_r($out);
                        } else {
                            echo "Ffmpeg successfully merged audi/video files into single WebM container!\n";

                            // make the post ready
                            $this->query_update(
                                "update " .
                                    VY_LV_TBL["POSTS"] .
                                    " set `post_id`='{$post_id}' where `id`='{$post_id}'"
                            );

                            // send user notification
                            $notification_data = [
                                "recipient_id" => $this->USER["id"],
                                "notifier_id" => $this->getAnyNotifierId(),
                                "type" => "admin_notification",
                                "text" =>
                                    "Your live stream its ready! Now you can see it on your timeline.",
                                "post_id" => $post_id,
                                "admin" => 1,
                                "url" => "index.php?link1=post&id=" . $post_id,
                            ];
                            Wo_RegisterNotification($notification_data);
                            unlink($audioFile);
                            unlink($videoFile);
                        }
                    }
                }
            }
        }
    }
    public function getContent()
    {
        $file = $this->post_vars("type");
        $available_files = ["desktop", "mobile"];

        $this->template->assign([
            "this" => $this,
            "wo" => $GLOBALS['V_Y'],
            "i" => $this->userid,
        ]);

        if (!in_array($file, $available_files)) {
            $content = $this->template->fetch($this->theme_dir . "/404.html");
        } else {
            $content = $this->template->fetch(
                $this->theme_dir . "/{$file}-stream-author.html"
            );
        }

        echo $this->getPage($content);
    }
    public function mob_popup()
    {
 
        $title = $this->post_vars("title");
        $kind = $this->post_vars("kind");
        $mob_pop_dir = "/popups/mob/";
        $users = isset($_POST["users"])
            ? json_decode($_POST["users"], true)
            : [];

        $popup_content = "404.html";
        switch ($kind) {
            case "mob-pre-live-settings":
                $popup_content = "pre-live-settings.html";
                break;
            case "mob-streaming-settings":
                $popup_content = "mob-streaming-settings.html";
                break;
            case "get-viewers":
                $popup_content = "get-viewers.html";
                break;
            case "get-available-for-moder":
                $popup_content = "add-moderators.html";
                break;
            case "remove-moderators":
                $popup_content = "remove-moderators.html";
                break;
        }

        $this->template->assign([
            "this" => $this,
            "users" => $users,
            "live_id" => $this->id,
            "dir" => $this->theme_dir,
            "wo" => $GLOBALS['V_Y'],
            "id" => $this->id,
            "file_content" => $popup_content,
            "i" => $this->userid,
            "title" => $title,
        ]);

        $content = $this->template->fetch(
            $this->theme_dir . $mob_pop_dir . "content.html"
        );
        echo $this->getPage($content);
    }
    public function getPageDetails($page_id = 0)
    {
        $arr = ["name" => "unknown-page", "avatar" => "", "owner" => 0];

        if (!$page_id || !is_numeric($page_id)) {
            return $arr;
        }

        $q = $this->db->query(
            "select `user_id`,`page_name`,`avatar` from " .
                VY_LV_TBL["PAGES"] .
                " where `page_id`='{$page_id}' limit 1"
        );
        $r = $q->fetch_array(MYSQLI_ASSOC);

        if (isset($r["page_name"])) {
            $arr["name"] = $r["page_name"];
        }

        if (isset($r["user_id"])) {
            $arr["owner"] = $r["user_id"];
        }

        if (isset($r["avatar"])) {
            $arr["avatar"] = $this->lv_get_avatar($r["avatar"]);
        }

        return $arr;
    }
    public function doesGroupExists($group_id = 0){
        
        return count($this->query_select("select id from ".VY_LV_TBL["GROUPS"] ." where `id`='{$group_id}' limit 1"));
        
    }
    public function doesPageExists($page_id = 0){
        
        return count($this->query_select("select page_id from ".VY_LV_TBL["PAGES"] ." where `page_id`='{$page_id}' limit 1"));
        
    }
    public function getGroupDetails($group_id = 0)
    {
        $arr = ["name" => "unknown-page", "avatar" => "", "owner" => 0];

        if (!$group_id || !is_numeric($group_id)) {
            return $arr;
        }

        $q = $this->db->query(
            "select `user_id`,`group_name`,`avatar` from " .
                VY_LV_TBL["GROUPS"] .
                " where `id`='{$group_id}' limit 1"
        );
        $r = $q->fetch_array(MYSQLI_ASSOC);

        if (isset($r["group_name"])) {
            $arr["name"] = $r["group_name"];
        }

        if (isset($r["user_id"])) {
            $arr["owner"] = $r["user_id"];
        }

        if (isset($r["avatar"])) {
            $arr["avatar"] = $this->lv_get_avatar($r["avatar"]);
        }

        return $arr;
    }
    public function getTurnCredentials()
    {
        $json_path = glob(getcwd() . DIRECTORY_SEPARATOR ."cr_turnserver". DIRECTORY_SEPARATOR ."*.json");
        $json = file_get_contents($json_path[0]); 
 
        return $json;
/*
        $data = [];

        $data["stun"] = $GLOBALS['V_Y']["ice_servers"]["stun_url"];
        $data["turn"] = $GLOBALS['V_Y']["ice_servers"]["turn_url"];
        $data["turn_username"] = $GLOBALS['V_Y']["ice_servers"]["turn_un"];
        $data["turn_password"] = $GLOBALS['V_Y']["ice_servers"]["turn_cr"];

        return $this->jencode($data);
        */
    }
    public static function getLLang()
    {
        $core = new VY_LIVESTREAM_CORE();
        return $core->lang;
    }
    public function getProductDetailsModal($id = 0){

        $data = array("error" => 0, "template" => "");

        if(!is_numeric($id) || $id <= 0){
            $data['error'] = "Invalid product id {$id}";

        } else {


            $q = $this->db->query("select p.*, ob.id as pid, ob.units as maxunits, pcart.units as curunits from ".VY_LV_TBL['PRODUCTS']." as p 
                left join ".VY_LV_TBL['WO_PRODUCTS']." as ob ON ob.vy_product_id = p.id 
                left join ".VY_LV_TBL['PRODUCTS_CART']." as pcart ON pcart.product_id = ob.id && pcart.user_id = '{$this->userid}' 
                where p.id='{$id}' limit 1");
            $r = $q->fetch_array(MYSQLI_ASSOC);


            if(isset($r['id'])){

                $categs = $this->productsCategs();
                $categ_full_text = '';
                $files = [];

                if($this->isJson($r['categ'])){

                    $categs = json_decode($categs,true);
                    $product_categ = json_decode($r['categ'],true);
                    $categ_arr = array();
                    for($j=0;$j<count($categs);$j++){

                        $categ = $categs[$j];

                        if($product_categ['id'] > 0 && $categ['id'] == $product_categ['id']){

                            $categ_arr['base_name'] = $categ['category_name'];

                            if($product_categ['sub_id'] > -1 && count($categ['sub']) && isset($categ['sub'][$product_categ['sub_id']]['categ_name']))
                                $categ_arr['sub_name'] = $categ['sub'][$product_categ['sub_id']]['categ_name'];

                            if($product_categ['sub2_id'] > -1 && count($categ['sub'][$product_categ['sub_id']]) && isset($categ['sub'][$product_categ['sub_id']]['sub2'][$product_categ['sub2_id']]))
                                $categ_arr['sub2_name'] = $categ['sub'][$product_categ['sub_id']]['sub2'][$product_categ['sub2_id']];

                            
 
                            break;
                        }


                    }

                    $categ_full_text = implode(" > ",$categ_arr);

                }
                
                if($this->isJson($r['files'])){
                    $r['files'] = json_decode($r['files'],true);
                    usort($r['files'], fn($x, $y) => strcmp($x['position'], $y['position']));
                    for($i=0;$i<count($r['files']);$i++)
                        $files[] = $r['files'][$i]['filename'];

                }

                if(count($files)){
                $is_vid = explode('.',$files[0]);
                $is_vid = end($is_vid);
                $def_image = $this->getProductFilesPath($r['user_id'],1) . ($is_vid == 'mp4' ? basename($files[0], 'mp4') . 'jpg' : $files[0]);
                }

                if(!isset($_SESSION['product_view_'.$r['id']])){
                    $this->query_update("update ".VY_LV_TBL['PRODUCTS']." set `views`=views+1 where `id`=".$r['id']);
                    $_SESSION['product_view_'.$r['id']] = 1;
                }
               

                $this->template->assign([
                            "this" => $this,
                            "id" => $id,
                            "r" => $r,
                            "categs" => $categs,
                            "categ_full_text" => $categ_full_text,
                            "files" => $files,
                            "image" => $def_image,
                            "seller_url" => $this->get_full_url()."/livestream/u/".$r['user_id'],
                            "seller" => $this->lv_getUserDetails($r['user_id'])
                        ]);
                        $data['template'] = $this->template->fetch($this->theme_dir . "/product-template-full.html");


            } else {

                $q = $this->db->query("select `id` as post_id from ".VY_LV_TBL['POSTS']." where product_id='{$id}' limit 1");
                $r = $q->fetch_array(MYSQLI_ASSOC);

                if(isset($r['post_id']) && $r['post_id'] > 0){
                    $data['error'] = 3;
                    $data['post_id'] = $r['post_id'];
                } else $data['error'] = "Product {$id} not found.";

            }

        }

        echo $this->jencode($data);

    }
    public function getProductLocation($loc = ''){

        $loc = strtolower($loc);
        $all = json_decode($this->continents_countries(),true);
        $res = ['empty' => 1, 'data' => []];


        if(!empty($loc)){



            for($i = 0; $i < count($all); $i++){

                $countries = $all[$i]['countries'];
                for($k=0;$k<count($countries);$k++){
                    if($loc == strtolower($countries[$k]['code'])){
                        $res = array('empty' => 0,'data' => array("country_name"=>$countries[$k]['name'],"country_code"=>$countries[$k]['code'],"continent_name"=>$all[$i]['name']));
                    }

                }

            }
        }

      return $res;


    }
    public function getFullSizeModal(){



        $this->template->assign([
            "this" => $this
        ]);
        $content = $this->template->fetch($this->theme_dir . "/popups/fullsizemodal/index.html");

        echo $this->getPage($content);  



    }
    public function changeProductDefCover(){

        $img = urldecode($this->post_vars('imagename'));
        $id = $this->post_vars('id');
        $files_pos = array();
        $response = false;
        if($id > 0){

            $q = $this->db->query("select `id`,`files` from ".VY_LV_TBL['PRODUCTS']." where `id`='{$id}' && `user_id`='{$this->userid}' limit 1");
            $r = $q->fetch_array(MYSQLI_ASSOC);

            if(isset($r['id']) && $r['id'] > 0){

                $files = json_decode($r['files'],true);

                $k = 2;
                for($i=0; $i < count($files); $i++){

                    $file = $files[$i];

                    if($file['filename'] == $img){
                        $file['position'] = 1;
                        $k--; 
                    }
                    else
                        $file['position'] = $k;

                        $files_pos[] = $file;

                    $k++;
                }
                $files_pos = $this->jencode($files_pos);
                if($this->query_update("update ".VY_LV_TBL['PRODUCTS']." set `files`='{$files_pos}' where `id`=".$r['id'])) $response = true;
            }

        }

        echo $response;

    }
    public static function getProductDefaultImage($id = 0, $native_wo = false){

        $live = new LIVE_STREAM();
        $def_image = $live->rs_slider_video_blank();
        $is_vid = false;

        if($id > 0 && !$native_wo){

            $q = $live->db->query("select `files`,`user_id` from ".VY_LV_TBL['PRODUCTS']." where `id`='{$id}' limit 1");
            $r = $q->fetch_array(MYSQLI_ASSOC);

            if(isset($r['files'])){
                $files = json_decode($r['files'],true);
                usort($files, fn($x, $y) => strcmp($x['position'], $y['position']));

                $is_vid = explode('.',$files[0]['filename']);
                $is_vid = end($is_vid);

 
                $def_image = $live->getProductFilesPath($r['user_id'],1) . ($is_vid == 'mp4' ? basename($files[0]['filename'], 'mp4') . 'jpg' : $files[0]['filename']);

            }

            


        } else {

            $q = $live->db->query("select `id`,`image` from ".VY_LV_TBL['WO_PRODUCTS_MEDIA']." where `product_id`='{$id}' order by id desc limit 1");
            $r = $q->fetch_array(MYSQLI_ASSOC);

            if(isset($r['id']) && $r['id'] > 0)
                $def_image = $live->get_full_url() . DIRECTORY_SEPARATOR .  $r['image'];

        }

        return $def_image;

    }

    public function insertProductToCart($product_id = 0){

        $now = time();
        $pid = 0;
        $custom_value = $this->post_vars("val");
        if($product_id > 0 && is_numeric($product_id)){

            $check = $this->db->query("select `id`,`units` from ".VY_LV_TBL['PRODUCTS_CART']." where `product_id`='{$product_id}' && `user_id`='{$this->userid}' limit 1");
            $rcheck = $check->fetch_array(MYSQLI_ASSOC);
            $pid = isset($rcheck['id']) && $rcheck['id'] > 0 ? $rcheck['id'] : 0;
            if($pid){
                $custom_value = $custom_value > 0 ? $custom_value : $rcheck['units'] + 1;
                $update = $this->query_update("update ".VY_LV_TBL['PRODUCTS_CART']." set `units`='{$custom_value}' where `id`='{$pid}' && `user_id`='{$this->userid}'");
            } else {


                $insert = $this->query_insert("insert into ".VY_LV_TBL['PRODUCTS_CART']." set `user_id`='{$this->userid}',`product_id`='{$product_id}',`units`=units+1");
                $pid = $product_id;
 
            }


        }



        echo $pid;

    }
    public static function ifBroadcastHaveProduct($id = 0){
        $live = new LIVE_STREAM();
        $q = $live->db->query(
            "select * from ".VY_LV_TBL['PRODUCTS']." where `broadcast_id`='{$id}' limit 1"
        );
        $r = $q->fetch_array(MYSQLI_ASSOC);

        return isset($r['id']) ? $r : [];
    }
    public function deleteProductFromCart($id = 0){
        $delete = false;
        if($id > 0 && is_numeric($id))
            $delete = $this->query_delete("delete from ".VY_LV_TBL['PRODUCTS_CART']." where `id`='{$id}' && `user_id`='{$this->userid}'");

        return $delete ? true : false;
    }

    public static function cartCount(){
        $live = new LIVE_STREAM();
        $q = $live->db->query(
            "select `units` from ".VY_LV_TBL['PRODUCTS_CART']." where `user_id`='{$live->userid}' limit 1"
        );
        $r = $q->fetch_array(MYSQLI_ASSOC);

        return isset($r['units']) ? $r['units'] : 0;

    }
    public static function cartItems(){
        $live = new LIVE_STREAM();
        $q = $live->query_select("select p.*,c.id as cart_id, c.units as myunits, p.units as uunits, c.product_id as pid, vyp.price as original_price from ".VY_LV_TBL['PRODUCTS_CART']." as c 
                                
                                left join ".VY_LV_TBL['WO_PRODUCTS']." p ON p.id = c.product_id
                                left join ".VY_LV_TBL['PRODUCTS']." vyp ON vyp.id = p.vy_product_id
                                where c.user_id='{$live->userid}'
            ");

        return $q;

    }
    public static function getProductDiscountPrice($id = 0){
        $live = new LIVE_STREAM();
        $q = $live->db->query("select discount_price from ".VY_LV_TBL['PRODUCTS']." where id='{$id}' limit 1");
 
        return ($q->fetch_array(MYSQLI_ASSOC))['discount_price'];

    }
    public function productIsInCart($product_id = 0){

        return count($this->query_select("select `id` from ".VY_LV_TBL['PRODUCTS_CART']." where `product_id`='{$product_id}' && `user_id`='{$this->userid}' limit 1"));

    }

    public function createRecordPostPlayer($data = array()){

        $content = $this->template->fetch($this->theme_dir . "/404.html");

        if($this->isarray($data) && count($data)){

            if(isset($data['id']) && $data['id'] > 0){

                $this->template->assign([
                    "this" => $this,
                    "r" => $data
                ]);
                $content = $this->template->fetch($this->theme_dir . "/player.html");

            }  



        }

        return $content;

    }
    public function createRecordPostPlayerWithProduct($data = array()){

        $content = $this->template->fetch($this->theme_dir . "/404.html");

        if($this->isarray($data) && count($data)){

            if(isset($data['id']) && $data['id'] > 0){

                $this->template->assign([
                    "this" => $this,
                    "r" => $data
                ]);
                $content = $this->template->fetch($this->theme_dir . "/post_contain_product.html");

            }  



        }

        return $content;

    }
    public function getComments($post_id = 0,$limit = 100){
        return $this->query_select("select * from " . VY_LV_TBL["COMMENTS"] .  " where `post_id`='{$post_id}' order by id desc limit ".$limit);

    }

}
