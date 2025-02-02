global.streams_timer = global.streams_timer || {};

// GLOBAL FUNCTIONS
// ==============================================================

function sendStreamLagReason(s,id,reason){
	
		switch(reason){
			
			case 'server namespace disconnect':
			s.to(id).emit("broadcast_end",id, 'forcefully_disconnected');
			break;
			case 'client namespace disconnect':
			s.to(id).emit("broadcast_end",id, 'manually_disconnected');
			break;
			case 'server shutting down':
			s.to(id).emit("broadcast_end",id, 'server_down');
			break;
			case 'ping timeout':
			s.to(id).emit("wait_broadcast",id, 'ping_timeout', 'load_only');
			 console.log('sending ping timeout to '+id);
			break;
			case 'transport close':
			s.to(id).emit("wait_broadcast",id, 'network_error');
			 console.log('sending ping timeout to '+id);
			break;
			case 'transport error':
			s.to(id).emit("broadcast_end",id, 'socket_error');
			break;
		}
	
}
function rimraf(dir_path) {  
    if (fs.existsSync(dir_path)) {
        fs.readdirSync(dir_path).forEach(function(entry) {
            var entry_path = path.join(dir_path, entry);
            if (fs.lstatSync(entry_path).isDirectory()) {
                rimraf(entry_path);
            } else {
                fs.unlinkSync(entry_path);
            }
        });
        fs.rmdirSync(dir_path);
    }
}

function delete_obs_video(path,f){
	
 
		if (fs.existsSync(path)) 
			fs.unlinkSync(path);
		
		console.log('obs stream deleted');
		
		if(f)
			console.log('OBS DELETED FIRST');
		
		return true;
}

async function DEPRECATED_sendNotification(c,user_id,host_name){
		let notifier_id = 2, time = Math.floor(new Date().getTime() / 1000), t = '';
		const rows = await query(`select user_id from ${VY_CONFIG.VY_LV_TBL_USERS} where user_id<>${user_id} order by rand() limit 1`);
		if(rows[0]['user_id'] > 0) {
			notifier_id = rows[0]['user_id'];
		}
	switch(c){
		
		case 'processing_stream':
			t = 'Your live stream its generating now by our system. You will be notified when it is ready.';
			await query(`insert into ${VY_CONFIG.VY_LV_TBL_NOTIF} set 
																	time='${time}',
																	notifier_id='${notifier_id}',
																	recipient_id='${user_id}',
																	type='admin_notification',
																	url='index.php',
																	text='${t}'`);
		break;
		case 'stream_processed':
			t = 'Your live stream its ready! Now you can see it on your timeline.';
			await query(`insert into ${VY_CONFIG.VY_LV_TBL_NOTIF} set 
																	time='${time}',
																	notifier_id='${notifier_id}',
																	recipient_id='${user_id}',
																	type='admin_notification',
																	url='index.php',
																	text='${t}'`);
		break;
		
		
		
	}

	return true;	
	
}
function tomp4(file){
 
 return new Promise((resolve, reject) => {
  
		const spawn = require('child_process').spawn;
		
		const args = VY_CONFIG.mp4_high_quality ? ["-i", file+".webm", "-crf", "1", "-c:v", "libx264", file+".mp4"] : ["-i", file+".webm", "-vcodec", "libx264", "-crf", "20",  file+".mp4"];
 
		const proc = spawn(VY_CONFIG.ffmpeg_path, args);

		proc.stdout.on('data', function(data) {
			console.log(data);
		});

		proc.stderr.setEncoding("utf8")
		proc.stderr.on('data', function(data) {
			console.log(data);
		});

		proc.on('close', function() {
			console.log('finished');
		 
			resolve(true);
		});
  
  });

	
}

async function updateStreamKey(user_id){
	
 
	let _time = Date.parse(new Date())/1000, key = VY_CONFIG.stream_key_prefix + '-' + md5(user_id.toString()) + '-' + user_id+ '_' +_time +md5(VY_CONFIG.stream_secret);
  
  
  console.log('error sql update steam key');
  console.log('_time',_time);
  console.log('key',key);
 
  console.log('user_id tostring',user_id.toString());
    console.log('user_id',user_id);
	    console.log('user_id md5 to string',md5(user_id.toString()));
			    console.log('sql query ',"UPDATE "+VY_CONFIG.VY_LV_TBL_USERS+" set `vy-live-streamkey`='"+key+"' where `user_id`='"+user_id+"'");
				
				
	await query("UPDATE "+VY_CONFIG.VY_LV_TBL_USERS+" set `vy-live-streamkey`='"+key+"' where `user_id`='"+user_id+"'");
	
	return key;
}
function sendNotification(c,user_id,post_id){
	
	let buffer = "";
 
	const qs = querystring.stringify({
											'cmd': 'send_notif',
											'notification':c,
											'id':user_id,
											'source':'api',
											'post_id':post_id
	   });
	const options = {
	  hostname: VY_CONFIG.host,
	  port: 443,
	  path: '/vy-livestream-cmd.php', 
	  method: 'POST',
		headers: {
       'Content-Type': 'application/x-www-form-urlencoded',
       'Content-Length': qs.length
     }
	};

 
	const req = https.request(options, (res) => {
	 
	  res.on('data', (d) => {
 
		  console.log('Notification '+c+' sent to user '+user_id);
		         buffer+=d;

 
	  });
    res.on('end', function() {
        console.log(buffer);
    });
	});

	req.on('error', (e) => {
	  console.error('Notification sending err: '+e);
	});
	req.write(qs);
	req.end();
	
	
	
	
	
	
}

function startNMS() {

	


    let argv = require('minimist')(process.argv.slice(2), {
        string: ['rtmp_port', 'http_port', 'https_port'],
        alias: {
            'rtmp_port': 'r',
            'http_port': 'h',
            'https_port': 's',
        },
        default: {
            'rtmp_port': VY_CONFIG.rtmp.rtmp_port,
            'http_port': VY_CONFIG.rtmp.http_port,
            'https_port': VY_CONFIG.rtmp.https_port
        }
    });

    if (argv.help) {
        console.log('Usage:');
        console.log('  node-media-server --help // print help information');
        console.log('  node-media-server --rtmp_port '+VY_CONFIG.rtmp.rtmp_port+' or -r '+VY_CONFIG.rtmp.rtmp_port);
        console.log('  node-media-server --http_port '+VY_CONFIG.rtmp.http_port+' or -h '+VY_CONFIG.rtmp.http_port);
        console.log('  node-media-server --https_port '+VY_CONFIG.rtmp.https_port+' or -s '+VY_CONFIG.rtmp.https_port);
        process.exit(0);
    }


    let nm_config = {
        logType: 1,
        rtmp: {
            port: argv.rtmp_port,
            chunk_size: 60000,
            gop_cache: true,
            ping: 30,
            ping_timeout: 60,
            ssl: {
                port: VY_CONFIG.obs_ssl_port,
                key: VY_CONFIG.ssl_path.key,
                cert: VY_CONFIG.ssl_path.crt,
            }
        },
        http: {
            port: argv.http_port,
            mediaroot: VY_CONFIG.media_root,
            allow_origin: '*',
            api: true
        },
        https: {
            port: argv.https_port,
            key: VY_CONFIG.ssl_path.key,
            cert: VY_CONFIG.ssl_path.crt,
        },
        auth: {
            api: true,
            api_user: 'sasati',
            api_pass: 'sasati',
            play: false,
            publish: false,
            secret: VY_CONFIG.stream_secret
        }

    };
    console.log(VY_CONFIG);
    if (VY_CONFIG.recording) {
        nm_config['trans'] = {};
        nm_config['trans']['ffmpeg'] = VY_CONFIG.ffmpeg_path;
        nm_config['trans']['tasks'] = [{
            app: VY_CONFIG.app_name,
            hls: VY_CONFIG.hls,
            hlsFlags: VY_CONFIG.hlsFlags,
            dash: VY_CONFIG.dash,
            dashFlags: VY_CONFIG.dashFlags,
            mp4: true,
            mp4Flags: VY_CONFIG.mp4Flags,
            vc: "copy",
            vcParam: [
                '-tune',
                'zerolatency'
            ]

        }];

    }

    nms = new NodeMediaServer(nm_config);
    nms.run();

    nms.on('preConnect', (id, args) => {
        console.log('[NodeEvent on preConnect]', `id=${id} args=${JSON.stringify(args)}`);

    });

    nms.on('postConnect', (id, args) => {
        console.log('[NodeEvent on postConnect]', `id=${id} args=${JSON.stringify(args)}`);
    });

    nms.on('doneConnect', (id, args) => {
        console.log('[NodeEvent on doneConnect]', `id=${id} args=${JSON.stringify(args)}`);
    });

    nms.on('prePublish', async (id, StreamPath, args) => {
        console.log('[NodeEvent on prePublish]', `id=${id} StreamPath=${StreamPath} args=${JSON.stringify(args)}`);
 

        // verify stream key  
        let stream_key = StreamPath.replace('/live/', ''),
            uid_md5 = stream_key.split('-')[1],
            clean_userid = stream_key.split('_')[0].split('-')[2],
            session = nms.getSession(id);

        const rows = await query("SELECT COUNT(*) as count from " + VY_CONFIG.VY_LV_TBL_USERS + " where `vy-live-streamkey`='" + stream_key + "' and `user_id`='" + clean_userid + "'");
        if (rows[0]['count'] <= 0) {

            io.to(concurentsmd5_id[uid_md5]).emit('obs_invalid_key');
            session.reject();




            const dirPath = VY_CONFIG.media_root + clean_userid + '/streams/writing.mp4';

            if (VY_CONFIG.recording) {
                if (fs.existsSync(dirPath)) {
                    fs.unlinkSync(dirPath);

                }

            }
        }


    });

    nms.on('postPublish', (id, StreamPath, args) => {
        console.log('[NodeEvent on postPublish]', `id=${id} StreamPath=${StreamPath} args=${JSON.stringify(args)}`);
        const stream_key = StreamPath.replace('/live/', '');
        const userid_md5 = stream_key.split('-')[1];
        console.log('obs started ' + userid_md5);
        io.to(concurentsmd5_id[userid_md5]).emit('obs_started', StreamPath);
        concurents_obs_session[userid_md5] = id;
    });

    nms.on('donePublish', (id, StreamPath, args) => {
        console.log('[NodeEvent on donePublish]', `id=${id} StreamPath=${StreamPath} args=${JSON.stringify(args)}`);
        const stream_key = StreamPath.replace('/live/', '');
        const userid_md5 = stream_key.split('-')[1];
        const clean_userid = stream_key.split('_')[0].split('-')[2];

        const dirPath = VY_CONFIG.media_root + clean_userid + '/streams/writing.mp4';
        io.to(concurentsmd5_id[userid_md5]).emit('obs_stopped', dirPath);
        delete concurents_obs_session[userid_md5];
    });

    nms.on('prePlay', (id, StreamPath, args) => {
        console.log('[NodeEvent on prePlay]', `id=${id} StreamPath=${StreamPath} args=${JSON.stringify(args)}`);
        //let session = nms.getSession(id);
        //session.reject();
    });

    nms.on('postPlay', (id, StreamPath, args) => {
        console.log('[NodeEvent on postPlay]', `id=${id} StreamPath=${StreamPath} args=${JSON.stringify(args)}`);
    });

    nms.on('donePlay', (id, StreamPath, args) => {
        console.log('[NodeEvent on donePlay]', `id=${id} StreamPath=${StreamPath} args=${JSON.stringify(args)}`);


    });




}
