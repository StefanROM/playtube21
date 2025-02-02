//
//  author: Vanea Young
//  date:  11/8/21.
//  email: movileanuion[a]gmail.com
//  Copyright (c) 2022 Vanea Young. All rights reserved.
//
var nms,b2;
const offline_timeout = 300000;// 5 minutes
const {
    DB_CREDENTIALS
} = require("./lib/db_credentials.js");
let {
    VY_CONFIG
} = require("./lib/config.js");
const port = VY_CONFIG.app_port;
const express = require("express");
const app = express();
const minimist = require('minimist');
const mysql = require("mysql");
const axios = require('axios')
const querystring = require("querystring");
const util = require('util');
const md5 = require("md5");
const path = require('path');
const url = require('url');
const ws = require('ws');
const kurento = require('kurento-client');
const B2_cloud = require('backblaze-b2');
const AWS = require('aws-sdk');
const fs = require("fs");
const spawn = require('child_process').spawn;
const {
    promises: fss
} = require("fs");
const {
    Timer
} = require('timer-node');
const shell = require('shelljs');
const webmToMp4 = require("webm-to-mp4");
const bodyParser = require('body-parser');
const favicon = require('serve-favicon');
const pkey = fs.readFileSync(VY_CONFIG.ssl_path.key);
const pcert = fs.readFileSync(VY_CONFIG.ssl_path.crt);
const ssl_options = {
    key: pkey,
    cert: pcert
};
const https = require("https");
const server = https.createServer(ssl_options, app);
const io = require("socket.io")(server, {
    transports: ["websocket"],
    resource:'/socket.io',
    upgrade: false,
    allowUpgrades: false,
    'pingTimeout': 20000,
    'pingInterval': 25000
});
const wss = new ws.Server({
    //server : server,
    path : '/vybroadcasting',
    noServer: true
}); 

const ioHandleUpgrade = server._events.upgrade;
const NodeMediaServer = require('node-media-server');
const rtmp = {};
const timeouts = {};
const stream_health = {}; 
const concurents = {};
const concurentsmd5_id = {};
const this_live = {};
const blocked_users = {};
const muted_users = {};
const moderators = {};
const broadcaster = {};
const host_userid = {};
const concurents_obs_session = {};
const concurents_reconnected_obs_session = {};
const m_connection = mysql.createPool({
    host: DB_CREDENTIALS.DBHOST,
    user: DB_CREDENTIALS.DBUSER,
    password: DB_CREDENTIALS.DBPASS,
    database: DB_CREDENTIALS.DBNAME
});
const query = async function(q) {
m_connection.getConnection = util.promisify(m_connection.getConnection);
let conn = await m_connection.getConnection(), r = util.promisify(m_connection.query).bind(m_connection)(q);
conn.release();
return r;
};
// delete upgrade, to allow socket.io & ws connected togheter 
delete server._events.upgrade;
server.on('upgrade', function (req, socket, head) {
    if (req.url.indexOf('socket.io') > -1) {
        ioHandleUpgrade(req, socket, head);

    } else {
        wss.handleUpgrade(req, socket, head, (webSocket) => {
            wss.emit('connection', webSocket, req);
        });
    }
});

// get configuration
(async () => {

    const rows = await query('SELECT `settings` from `vy_live_conf` limit 1');
    VY_CONFIG = Object.assign({}, VY_CONFIG, JSON.parse(rows[0]['settings']));
    VY_CONFIG = Object.assign({}, VY_CONFIG, VY_CONFIG['tables']);
    delete VY_CONFIG.tables;
    b2 = new B2_cloud({
                  applicationKeyId: VY_CONFIG.b2_key_id, // or accountId: 'accountId'
                  applicationKey: VY_CONFIG.b2_app_key // or masterApplicationKey
                });
    AWS.config.update({
    "accessKeyId":     VY_CONFIG['s3_key_id'],
    "secretAccessKey": VY_CONFIG['s3_secret_key'], 
    "region":          VY_CONFIG['s3_region']
    
    });
 
    console.log(VY_CONFIG);
    startNMS(); 
    //hlsServerInit();
    //Cleaner(); 
    console.log('Configuration loaded...');
 
    console.log(AWS.config);
 
})()
 
const argv = minimist(process.argv.slice(2), {
    default: {
        as_uri: `https://localhost:${VY_CONFIG.app_port}`,
        ws_uri: `ws://localhost:${VY_CONFIG.kurento_ws_uri_port}/kurento`
        //ws_uri: `ws://localhost:29999/kurento`
    }
});
const asUrl = url.parse(argv.as_uri);


// include global functions
//eval(fs.readFileSync('lib/global.js') + '');
 /*
 * Definition of global variables.
 */
global.streams_timer = global.streams_timer || {};
global.ice_servers = {iceServers:[]};
global.is_recording = {};
global.record_filename = {};//md5(Math.random() * 1000) + VY_CONFIG['record_type'];
global.reconncting_broadcasts = new Array();
global.candidatesQueue = {};
global.kurentoClient = null;
global.presenter = {};
global.viewers = {};
global.sdps = {};

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
            s.to(id).emit("wait_broadcast",id, 'ping_timeout');
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
        const rows = await query(`select user_id from ${VY_CONFIG.VY_LV_TBL_USERS} where id<>${user_id} order by rand() limit 1`);
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
function tomp4(mod_file,file){
 
 return new Promise((resolve, reject) => {
        let response = false;
        
        const args = VY_CONFIG.mp4_high_quality ? ["-i", mod_file, "-y", "-crf", "1", "-c:v", "libx264", file] : ["-i", mod_file, "-c", "copy", file];
 
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
            response = true;
            resolve(true);
            return true;
        });
  
  });

    
}

async function updateStreamKey(user_id){
    
    var key_exists = false;
    let _time = Date.parse(new Date())/1000, key = VY_CONFIG.stream_key_prefix + '-' + md5(user_id.toString()) + '-' + user_id+ '_' +_time +md5(VY_CONFIG.stream_secret);
    await query("UPDATE "+VY_CONFIG.VY_LV_TBL_USERS+" set `vy-live-streamkey`='"+key+"' where `id`="+user_id);
    return key;
}
function sendNotification(c,user_id,post_id,storage){
    
    let buffer = "";
 
    const qs = querystring.stringify({
                                            'cmd': 'send_notif',
                                            'notification':c,
                                            'id':user_id,
                                            'source':'api',
                                            'post_id':post_id,
                                            'storage': storage
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
function updatePostAnBroadcast(post_id,stream_val) {


var k1 = '', k2='';
if(stream_val != '') {
k = "&& `islivenow`='yes'";
k2 = "&& `vy-live`='yes'";
}

if(post_id > 0) {
    query("update "+VY_CONFIG.VY_LV_TBL_BROADCASTS+" set `islivenow`='no' where `post_id`='"+post_id+"' "+k1);
    query("update "+VY_CONFIG.VY_LV_TBL_POSTS+" set `vy-live`='no' where `post_id`='"+post_id+"' "+k2);
    console.log('crashed updated: ',post_id)
}
}
function deletePostAnBroadcast(post_id,stream_val){
var k1 = '', k2='';
if(stream_val != '') {
k = "&& `islivenow`='yes'";
k2 = "&& `vy-live`='yes'";
}


    if(post_id > 0) {
    query("delete from "+VY_CONFIG.VY_LV_TBL_BROADCASTS+" where `post_id`='"+post_id+"' "+k1);
    query("delete from "+VY_CONFIG.VY_LV_TBL_POSTS+" where `post_id`='"+post_id+"' "+k2);
    console.log('crashed deleted: ',post_id);
}

}
function cleanCrashed(post_id){
 
if(post_id in rtmp) return console.log('rtmp',rtmp);

if(VY_CONFIG.recording == '1')
updatePostAnBroadcast(post_id);
else 
deletePostAnBroadcast(post_id)

} 
async function Cleaner(){
 

const rows = await query("select `post_id`,`stream_name` from "+VY_CONFIG.VY_LV_TBL_BROADCASTS+" where `islivenow`='yes' || ISNULL(NULLIF(stream_name,''))");

    for(i = 0;i < rows.length;i++)
        if((!stream_health.hasOwnProperty(rows[i]['post_id']) || !presenter.hasOwnProperty(rows[i]['post_id']) && global.reconncting_broadcasts.indexOf(rows[i]['post_id']) <= -1))
            cleanCrashed(rows[i]['post_id'],rows[i]['stream_name']);

    
    setTimeout(Cleaner,229999);
    console.log('Cleaner is working...');
}

function startNMS() {




    let argv = minimist(process.argv.slice(2), {
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
        logType: 0,
        rtmp: {
            port: argv.rtmp_port,
            chunk_size: 6000,
            gop_cache: true,
            ping: 30,
            ping_timeout: 60,
            ssl: {
                port: VY_CONFIG.rtmp_ssl_port,
                key: VY_CONFIG.ssl_path.key,
                cert: VY_CONFIG.ssl_path.crt,
            }
        },
        http: {
            port: argv.http_port,
            mediaroot: `${VY_CONFIG.p_path}${VY_CONFIG.media_root}`,
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
            api_user: 'admin',
            api_pass: 'admin123',
            play: false,
            publish: false,
            secret: VY_CONFIG.stream_secret
        }

    };
 
    if (VY_CONFIG.recording) {
        nm_config['trans'] = {};
        nm_config['trans']['ffmpeg'] = VY_CONFIG.ffmpeg_path;
        nm_config['trans']['tasks'] = [{
            app: VY_CONFIG.app_name,
            hls: VY_CONFIG.hls == 0 ? false : true,
            hlsFlags: VY_CONFIG.hlsFlags,
            hlsKeep: false,
            dash: VY_CONFIG.dash == 0 ? false : true,
            dashFlags: VY_CONFIG.dashFlags,
            dashKeep: false,
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

        const rows = await query("SELECT COUNT(*) as count from " + VY_CONFIG.VY_LV_TBL_USERS + " where `vy-live-streamkey`='" + stream_key + "' and `id`='" + clean_userid + "'");
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
        io.to(concurentsmd5_id[userid_md5]).emit('obs_started', StreamPath); console.log('okay')
        concurents_obs_session[userid_md5] = id;
    });

    nms.on('donePublish', (id, StreamPath, args) => {
        console.log('[NodeEvent on donePublish]', `id=${id} StreamPath=${StreamPath} args=${JSON.stringify(args)}`);
        const stream_key = StreamPath.replace('/live/', '');
        const userid_md5 = stream_key.split('-')[1];
        const clean_userid = stream_key.split('_')[0].split('-')[2];
        const dirPath = VY_CONFIG.media_root + clean_userid + '/streams/writing.mp4';

 
       io.to(concurentsmd5_id[userid_md5]).emit('obs_stopped', JSON.stringify({'path': dirPath, 'storage': VY_CONFIG.storage, 'recording':VY_CONFIG.recording}));
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

 /* KURENTO
 * Definition of functions
 */
// Recover kurentoClient for the first time.
const noPresenterMessage = 'No active presenter. Try again later...';
function getKurentoClient(callback) {
    if (kurentoClient !== null) {
        return callback(null, kurentoClient);
    }

    kurento(argv.ws_uri, function(error, _kurentoClient) {
        if (error) {
            console.log("Could not find media server at address " + argv.ws_uri);
            return callback("Could not find media server at address" + argv.ws_uri
                    + ". Exiting with error " + error);
        }

        kurentoClient = _kurentoClient;
        callback(null, kurentoClient);

    });
  
}

function startPresenter(postId, userId, ws, sdpOffer, callback, reconn) {
 

    clearCandidatesQueue(postId);

    if (presenter.hasOwnProperty(postId) && !reconn) {
        stop(postId,1);
        return callback("Another user is currently acting as presenter. Try again later ...");
    }

    presenter[postId] = {
        id : postId,
        pipeline : null,
        webRtcEndpoint : null,
        streamer_uid: userId
    }
 
    stream_health[postId] = userId;
    viewers[postId] = {};
 
    getKurentoClient(function(error, kurentoClient) {
  
        if (error) {
            stop(postId,1); 
            return callback(error);
        }

        if (!presenter.hasOwnProperty(postId)) {
            stop(postId,1); 
            return callback(noPresenterMessage);
        }
 
        kurentoClient.create('MediaPipeline', async function(error, pipeline) {
            
            
            if (error) {
                stop(postId,1); 
                return callback(error);
            }

            if (!presenter.hasOwnProperty(postId)) {
                stop(postId,1); 
                return callback(noPresenterMessage);
            }

            presenter[postId].pipeline = pipeline;
 
            //let rtmp_key = await updateStreamKey(userId);
            //var rtmp_url = `rtmp://localhost:${VY_CONFIG.rtmp.rtmp_port}/${VY_CONFIG.app_name}/${rtmp_key}`;
            let rtmp_url = `rtmp://localhost:${VY_CONFIG.httpsendpoint_port}/broadcast/${md5(postId.toString())}`;
 
 console.warn('streaming url is ......  '+rtmp_url);
 console.error('POST ID ',postId);
  console.error('POST ID MD5',md5(postId.toString()));
            let recording_uri_param = {uri: getFullFilenameAndPath(userId,postId), 'mediaProfile':'WEBM'};
            let recording_uri_param_rtmp = {uri: rtmp_url, 'mediaProfile':'FLV'};
            console.log("PATH RECORDING.....---->>",getFullFilenameAndPath(userId,postId))
                          
/*  
               if(VY_CONFIG['record_type'] == '.webm')
                recording_uri_param['mediaProfile'] = 'webm';
*/
 

                const kms_elements =
                [
                  {type: 'RecorderEndpoint', params: recording_uri_param},
                  {type: 'RecorderEndpoint', params: recording_uri_param_rtmp},
                  {type: 'WebRtcEndpoint', params: {}}
                ];

                console.log('elements',kms_elements);
 
            
            pipeline.create(kms_elements, function(error, elements) {
                if (error) {
                    stop(postId,1);
                    return callback(error);
                }

                if (!presenter.hasOwnProperty(postId)) {
                    stop(postId,1); 
                    return callback(noPresenterMessage);
                }

 
                  var recorder = elements[0];
                  var recorder_rtmp = elements[1];
                  var webRtcEndpoint   = elements[2];
                presenter[postId].webRtcEndpoint = webRtcEndpoint;
                presenter[postId].recorder = recorder;
                createIces(webRtcEndpoint);
                if (candidatesQueue[postId]) {
                    while(candidatesQueue[postId].length) {
                        var candidate = candidatesQueue[postId].shift();
                        webRtcEndpoint.addIceCandidate(candidate);
                    }
                }
                webRtcEndpoint.setMaxVideoRecvBandwidth(200);
                webRtcEndpoint.setMinVideoRecvBandwidth(100);
                webRtcEndpoint.on('OnIceCandidate', function(event) {
                    var candidate = kurento.getComplexType('IceCandidate')(event.candidate);
                    ws.send(JSON.stringify({
                        id : 'iceCandidate',
                        candidate : candidate
                    }));
                });
                webRtcEndpoint.processOffer(sdpOffer, function(error, sdpAnswer) {
                    if (error) {
                        stop(postId,1);
                        return callback(error);
                    }

                    if (!presenter.hasOwnProperty(postId)) {
                        stop(postId,1);
                        return callback(noPresenterMessage);
                    }

                    callback(null, sdpAnswer);
                });

                webRtcEndpoint.gatherCandidates(function(error) {
                    if (error) {
                        stop(postId,1);
                        return callback(error);
                    }
                });

                //if(VY_CONFIG.recording && global.is_recording.hasOwnProperty(postId)){
                    // record the WEBM file
                    kurentoClient.connect(webRtcEndpoint, recorder, function(error) {
                     if (error) {
                         
                       throw new Error('Connecting Recording WEBM ERROR: '+error);
                    }


                      
                      recorder.record(function(error) {
                        if (error) throw new Error('RecorderEndPoint WEBM ERROR: '+error);
 
                        console.error("RecorderEndPoint WEBM started",recorder);
                        
                        presenter[postId].recorder_filepath = getFullFilenameAndPath(userId,postId);
                     
                      });


                    });

                    // send to rtmp
                    kurentoClient.connect(webRtcEndpoint, recorder_rtmp, function(error) {
                     if (error) {
                         
                       throw new Error('Connecting RecorderEndPoint RTMP FLV ERROR: '+error);
                    }
                      
                      recorder_rtmp.record(function(error) {
                        if (error) throw new Error('RecorderEndPoint RTMP FLV ERROR: '+error);

                        console.error("RecorderEndPoint RTMP FLV started",recorder_rtmp);
                        presenter[postId].recorder_rtmp = recorder_rtmp;                    

         
                      });


                    });
        

 

            });  
        });
    });
}

function startViewer(postId, userId, ws, sdpOffer, callback, reconn) {
    clearCandidatesQueue(postId);

    if (!presenter.hasOwnProperty(postId) && !reconn) {
        stop(postId);
        return callback(noPresenterMessage);
    }

    presenter[postId].pipeline.create('WebRtcEndpoint', function(error, webRtcEndpoint) {
        if (error) {
            stop(postId,userId);
            return callback(error);
        }

        if(!viewers.hasOwnProperty(postId)) {
                viewers[postId] = {};
        }



        viewers[postId][userId] = {
            "webRtcEndpoint" : webRtcEndpoint,
            "ws" : ws
        }

        createIces(webRtcEndpoint);

        if (!presenter.hasOwnProperty(postId)) {
            stop(postId,userId);
            return callback(noPresenterMessage);
        }

        if (candidatesQueue[postId]) {
            while(candidatesQueue[postId].length) {
                var candidate = candidatesQueue[postId].shift();
                webRtcEndpoint.addIceCandidate(candidate);
            }
        }

        webRtcEndpoint.on('OnIceCandidate', function(event) {
            var candidate = kurento.getComplexType('IceCandidate')(event.candidate);
            ws.send(JSON.stringify({
                id : 'iceCandidate',
                candidate : candidate
            }));
        });

        webRtcEndpoint.processOffer(sdpOffer, function(error, sdpAnswer) {
            if (error) {
                stop(postId);
                return callback(error);
            }
            if (!presenter.hasOwnProperty(postId)) {
                stop(postId);
                return callback(noPresenterMessage);
            }

            presenter[postId].webRtcEndpoint.connect(webRtcEndpoint, function(error) {
                if (error) {
                    stop(postId,userId);
                    return callback(error);
                }
                if (!presenter.hasOwnProperty(postId)) {
                    stop(postId,userId);
                    return callback(noPresenterMessage);
                }

                callback(null, sdpAnswer);
                webRtcEndpoint.gatherCandidates(function(error) {
                    if (error) {
                        stop(postId,userId);
                        return callback(error);
                    }
                });
            });
        });
    });
}

function clearCandidatesQueue(postId) {
    if (candidatesQueue.hasOwnProperty(postId)) {
        delete candidatesQueue[postId];
    }
}

function stop(postId,userId,streamer) {
 console.log('stop post id ---  '+postId);
    if((presenter.hasOwnProperty(postId) && presenter[postId].id == postId && parseInt(presenter[postId].streamer_uid) == userId) || streamer){

        //if (presenter.hasOwnProperty(postId) && presenter[postId].id == postId) {
        for (var i in viewers[postId]) {
            var viewer = viewers[i];
            if (viewer.ws) {
                viewer.ws.send(JSON.stringify({
                    id : 'stopCommunication'
                }));
            }
        }
 

        presenter[postId].recorder.stop();
        presenter[postId].recorder_rtmp.stop();
        presenter[postId].pipeline.release();
        delete presenter[postId];
        delete viewers[postId];


      /*  if(VY_CONFIG.mp4_high_quality == '1' && VY_CONFIG.recording == '1' && getcurrtime(postId))
            processRecordedVideo(postId,userId);
        else*/ 
        console.log('is recording', global.is_recording);
            if(VY_CONFIG.recording == '1' && getcurrtime(postId) && global.is_recording.hasOwnProperty(postId))
            {
                processRecordedVideo(postId,userId, function(){

                       if(postId in global.record_filename)
                            delete global.record_filename[postId];

                       if(postId in global.is_recording)
                            delete global.is_recording[postId];
 

                });

            } else {
                console.log('deleting file ->>',getFullFilenameAndPath(userId,postId));

                
                fs.unlinkSync(getFullFilenameAndPath(userId,postId));
            }
 

      if ('delete_post_'+postId in timeouts)
         clearTimeout(timeouts['delete_post_'+postId]);



       // }

    } else {

        if (viewers.hasOwnProperty(postId) && viewers[postId].hasOwnProperty(userId)) {
                viewers[postId][userId].webRtcEndpoint.release();
                delete viewers[postId][userId];
        }

    }

    clearCandidatesQueue(postId);

    if (viewers.length < 1 && !presenter.hasOwnProperty(postId)) {
        console.log('Closing kurento client');
        kurentoClient.close();
        kurentoClient = null;
    }
}
function getFullFilenameAndPath(userid,post_id){
    return getRecordingPath(userid,post_id) + global.record_filename[post_id].replace('.mp4','.webm');
}
function getRecordingPath(userid,post_id){
    return VY_CONFIG['p_path'] + VY_CONFIG['media_root'] + userid + '/streams/';
}
function getcurrtime(post_id){
    r = 0;
    const curr_timer = global.streams_timer.hasOwnProperty(post_id) && global.streams_timer[post_id].isStarted() ? global.streams_timer[post_id].time() : 0;
    if(curr_timer != 0 && curr_timer.hasOwnProperty('m') && curr_timer.m >= 1)
        r = 1;

    return r;
}
 
async function uploadToCloud(post_id,user_id,file){
 

    file = file ? file : getFullFilenameAndPath(user_id,post_id);
    file.replace('.webm','.mp4');
    switch(VY_CONFIG.storage){

        case 'b2':
            uploadToB2(file,post_id,user_id);
        break;
        case 's3':
            uploadToS3(file,post_id,user_id);
        break;


    }


}
async function uploadToS3(file,post_id,user_id){
console.log('file',file)
const keyName = file.replace(VY_CONFIG['media_root'],'');
const bucketName = VY_CONFIG['s3_bucket_name'];
const apiVersion = '2006-03-01';
 const checkBucketExists = async bucket => { 
  const s3 = new AWS.S3();
  const options = {
    Bucket: bucket,
  };
  try {
    await s3.headBucket(options).promise();
    return true;
  } catch (error) {
    if (error.statusCode === 404) {
      return false;
    }
    throw error;
  }
};
 const uploadFile = async file => { 
  try {
            // Create params for putObject call
            var objectParams = {Bucket: bucketName, Key: keyName, Body: fs.readFileSync(file)};
            // Create object upload promise
            var uploadPromise = new AWS.S3({apiVersion: apiVersion}).putObject(objectParams).promise();
            uploadPromise.then(
              function(data) {
                console.log("Successfully uploaded data to " + bucketName + "/" + keyName);
                sendNotification('stream_processed', user_id, post_id, 's3');
                fs.unlinkSync(file);

              });
    }catch (error) {
    console.log('err aws s3 upload, ERR -> ', error);
    throw new error;
  }
 };
  try {



        if(!checkBucketExists(bucketName)){
        // Create a promise on S3 service object
        const bucketPromise = new AWS.S3({apiVersion: apiVersion}).createBucket({Bucket: bucketName}).promise();
        // Handle promise fulfilled/rejected states
        bucketPromise.then(
          function(data) {
            uploadFile(file);
        }).catch(
          function(err) {
            console.error(err, err.stack);
        });
        }else{
            uploadFile(file);
        }

  } catch (err) {
    console.log('Error getting bucket:', err);
  }

}
async function uploadToB2(file,post_id,user_id){
 
  try {
    await b2.authorize(); // must authorize first (authorization lasts 24 hrs)

    let upload_data = await b2.getUploadUrl({
    bucketId: VY_CONFIG.b2_bucket_id
    });  // returns promise

 
 console.log('uploadoing to b2 ... ',file)
let results = await b2.uploadFile({
    uploadUrl: upload_data.data.uploadUrl,
    uploadAuthToken: upload_data.data.authorizationToken,
    fileName: file.replace(VY_CONFIG['media_root'],''),
    mime: 'video/mp4',
    data: fs.readFileSync(file),
    onUploadProgress: function(update){ console.log(`Progress: ${update.percent}% (${update.bytesDispatched}/${update.bytesTotal})`);}
});  // returns promise
 
            if(results.status == 200 && results.data.hasOwnProperty('fileName') && results.data.hasOwnProperty('fileId')){// && results.data.fileName.split('/')[1] == global.record_filename){

                await sendNotification('stream_processed', user_id, post_id, 'b2');
                fs.unlinkSync(file);
 
            } else {
                console.log('Error moving file to b2'); 
            }

  } catch (err) {
    console.log('Error getting bucket:', err);
  }

}
 
async function processRecordedVideo(post_id,user_id, callback){
console.log('processing video');
        const file = getFullFilenameAndPath(user_id,post_id);
        if (fs.existsSync(file)) {

            //const newfilename = file.split('.mp4')[0] + Math.random() + VY_CONFIG['record_type'];
          ///  fs.rename(file, newfilename, async () => {
            
                await sendNotification('processing_stream', user_id, post_id);
                const convert_done = tomp4(file,file.replace('.webm','.mp4')).then(async function(){
            
                    console.log('conver done .......');
                    if(VY_CONFIG.storage == 'default') await sendNotification('stream_processed', user_id, post_id);
                    else uploadToCloud(post_id,user_id,file);

                    if(typeof callback == 'function')
                        callback();
                    // delete file
                    console.log('delete file',file.replace('.mp4','.webm'));
                    fs.unlinkSync(file.replace('.mp4','.webm'));

                });
 
      
          //  });


        }
}
function onIceCandidate(postId, userId, _candidate) {
    var candidate = kurento.getComplexType('IceCandidate')(_candidate);

    if (presenter.hasOwnProperty(postId) && presenter[postId].id === postId && presenter[postId].streamer_uid === userId && presenter[postId].webRtcEndpoint) {
        console.info('Sending presenter candidate');
        presenter[postId].webRtcEndpoint.addIceCandidate(candidate);
    }
    else if (viewers.hasOwnProperty(postId) && viewers[postId].hasOwnProperty(userId) && viewers[postId][userId].webRtcEndpoint) {
        console.info('Sending viewer candidate');
        viewers[postId][userId].webRtcEndpoint.addIceCandidate(candidate);
    }
    else {
        console.info('Queueing candidate');
        if (!candidatesQueue[postId]) {
            candidatesQueue[postId] = [];
        }
        candidatesQueue[postId].push(candidate);
    }
}

app.use(bodyParser.json());
 

// CORS
app.use(function(req, res, next) {
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'GET, POST');
    res.setHeader('Access-Control-Allow-Headers', 'X-Requested-With,content-type, Authorization');
    next();
});
 
app.get('/', function(req, res, next){
    
    res.send('<title>Live Plugin Server</title><h2>Server online</h2><br/> <a href="https://codecanyon.net/user/vanea_young/portfolio">Get the live stream plugin</a>');
});
app.use(favicon(__dirname + '/favicon.webp')); 

app.post('/rm_broadcast_reconnecting_queue',(req, res) => {

    const index = global.reconncting_broadcasts.indexOf(req.body.id);
    global.reconncting_broadcasts.splice(index,1);

    res.writeHead(200, {
        'Content-Type': 'application/json'
    });

    res.write(JSON.stringify(global.reconncting_broadcasts));
    res.end();      
});
app.post('/getviewers', (req, res) => {

    const id = req.body.id;
    const only_moders = req.body.only_moders;
    const users = {
        'viewers': new Array(),
        'moderators': new Array(),
        'muted_viewers': new Array(),
        'blocked_viewers': new Array()
    };
    if(id in this_live){ 
    if (only_moders == 'no') {

        if (blocked_users.hasOwnProperty(id)) {
            users['blocked_viewers'] = blocked_users[id];
        }
        if (muted_users.hasOwnProperty(id)) {
            users['muted_viewers'] = muted_users[id];
        }
        if (moderators.hasOwnProperty(id)) {
            users['moderators'] = moderators[id];
        }

        for (var i = 0; i < this_live[id].length; i++) {

            if (muted_users[id].indexOf(parseInt(this_live[id][i])) <= -1 &&
                blocked_users[id].indexOf(parseInt(this_live[id][i])) <= -1 &&
                moderators[id].indexOf(parseInt(this_live[id][i])) <= -1)
                users['viewers'].push(this_live[id][i]);



        }
    }

    if (only_moders == 'yes') {
        for (var i = 0; i < moderators[id].length; i++)
            users['viewers'].push(moderators[id][i]);

    }
    
    }



    res.writeHead(200, {
        'Content-Type': 'application/json'
    });

    res.write(JSON.stringify(users));
    res.end();
});

app.post('/blockviewer', (req, res) => {

    const user_id = req.body.id;
    const Live_ID = req.body.Live_ID;
    const by_user_fullname = req.body.by_user_fullname;

    if (blocked_users.hasOwnProperty(Live_ID)) {

        if (blocked_users[Live_ID].indexOf(user_id) <= -1) {
            blocked_users[Live_ID].push(user_id);
            io.to(concurents[user_id]).emit('blocked', Live_ID, user_id);
            io.to(Live_ID).emit('notification', JSON.stringify({
                'notification': 'blocked',
                'user_id': user_id,
                'by': by_user_fullname
            }));

        }


    }


    res.writeHead(200, {
        'Content-Type': 'application/json'
    });

    res.write(JSON.stringify(new Array()));
    res.end();
});
app.post('/makeModerator', (req, res) => {

    const user_id = req.body.id;
    const Live_ID = req.body.Live_ID;
    const by_user_fullname = req.body.by_user_fullname;

    if (moderators.hasOwnProperty(Live_ID)) {

        if (moderators[Live_ID].indexOf(user_id) <= -1) {
            moderators[Live_ID].push(user_id);
            
            console.log('CONCURENTS',concurents);
            console.log('CONCURENTS USERID',concurents[user_id]);
            console.log('USERID',user_id);
            io.to(concurents[user_id]).emit('added-moderator', Live_ID, user_id);
            io.to(Live_ID).emit('notification', JSON.stringify({
                'notification': 'moderator-added',
                'user_id': user_id,
                'by':by_user_fullname
            }));

        }


    }


    res.writeHead(200, {
        'Content-Type': 'application/json'
    });

    res.write(JSON.stringify(new Array()));
    res.end();
});
app.post('/removeModerator', (req, res) => {

    const user_id = req.body.id;
    const Live_ID = req.body.Live_ID;
    const by_user_fullname = req.body.by_user_fullname;
    if (moderators.hasOwnProperty(Live_ID)) {
        const index = moderators[Live_ID].indexOf(user_id);


        moderators[Live_ID].splice(index, 1);
        io.to(concurents[user_id]).emit('removed-moderator', Live_ID, user_id);
        io.to(Live_ID).emit('notification', JSON.stringify({
            'notification': 'moderator-removed',
            'user_id': user_id,
            'by':by_user_fullname
        }));

    }



    res.writeHead(200, {
        'Content-Type': 'application/json'
    });

    res.write(JSON.stringify(new Array()));
    res.end();
});
app.post('/unblockviewer', (req, res) => {

    const user_id = req.body.id;
    const Live_ID = req.body.Live_ID;
    const by_user_fullname = req.body.by_user_fullname;
    if (blocked_users.hasOwnProperty(Live_ID)) {
        const index = blocked_users[Live_ID].indexOf(user_id);


        blocked_users[Live_ID].splice(index, 1);
        io.to(Live_ID).emit('notification', JSON.stringify({
            'notification': 'unblocked',
            'user_id': user_id,
            'by':by_user_fullname
        }));

    }


    res.writeHead(200, {
        'Content-Type': 'application/json'
    });

    res.write(JSON.stringify(new Array()));
    res.end();
});

app.post('/checkifimmoder', (req, res) => {

    const user_id = req.body.id;
    const live_id = req.body.Live_ID;
    const data = {
        'moderator':false,
        'is_host': false
       };
       
       
    if(moderators.hasOwnProperty(live_id)){
        for (var i = 0; i < moderators[live_id].length; i++)
            if (moderators[live_id][i] == user_id)
                data['moderator'] = true;
 
 
    }
    
    if(host_userid.hasOwnProperty(live_id) && host_userid[live_id] === user_id){
        
        data['is_host'] = true;
        
        
    }
    
    
    res.writeHead(200, {
        'Content-Type': 'application/json'
    });

    res.write(JSON.stringify(data));
    res.end();
});
 
app.post('/getliveoptions', (req, res) => {

    const user_id = req.body.id;
    const live_id = req.body.Live_ID;
    const is_rtmp = req.body.is_rtmp == 'yes' ? true : false;

    console.log('live-id-'+live_id);
    const data = {
        'blocked': 'no',
        'muted': 'no',
        'moderator':false,
        'terminated':false,
        'reconnecting': global.reconncting_broadcasts.indexOf(live_id),
        'time': global.streams_timer.hasOwnProperty(live_id) && global.streams_timer[live_id].isStarted() ? global.streams_timer[live_id].time() : 'Time get error.'
    };

    if(!(live_id in this_live) || (!(live_id in presenter) && !is_rtmp))
        data['terminated'] = 1;
  

    if(moderators.hasOwnProperty(live_id)){
        for (var i = 0; i < moderators[live_id].length; i++)
            if (moderators[live_id][i] == user_id)
                data['moderator'] = true;
 
 
    }
    
    if (blocked_users.hasOwnProperty(live_id)) {

        for (var i = 0; i < blocked_users[live_id].length; i++)
            if (blocked_users[live_id][i] == user_id)
                data['blocked'] = 'yes';


    }

    if (muted_users.hasOwnProperty(live_id)) {

        for (var i = 0; i < muted_users[live_id].length; i++)
            if (muted_users[live_id][i] == user_id)
                data['muted'] = 'yes';


    }



    res.writeHead(200, {
        'Content-Type': 'application/json'
    });

    res.write(JSON.stringify(data));
    res.end();
});
app.post('/muteViewer', (req, res) => {

    const user_id = req.body.id;
    const Live_ID = req.body.Live_ID;
    const by_user_fullname = req.body.by_user_fullname;
    if (muted_users.hasOwnProperty(Live_ID)) {

        if (muted_users[Live_ID].indexOf(user_id) <= -1) {
            muted_users[Live_ID].push(user_id);

            if (this_live[Live_ID].indexOf(user_id) > -1) {
                io.to(concurents[user_id]).emit('muted', Live_ID, user_id);
                io.to(Live_ID).emit('notification', JSON.stringify({
                    'notification': 'muted',
                    'user_id': user_id,
                    'by': by_user_fullname
                }));
            }

        }


    }


    res.writeHead(200, {
        'Content-Type': 'application/json'
    });

    res.write(JSON.stringify(new Array()));
    res.end();
});
app.post('/unmuteViewer', (req, res) => {

    const user_id = req.body.id;
    const Live_ID = req.body.Live_ID;
    const by_user_fullname = req.body.by_user_fullname;
    if (muted_users.hasOwnProperty(Live_ID)) {
        const index = muted_users[Live_ID].indexOf(user_id);


        muted_users[Live_ID].splice(index, 1);

        if (this_live[Live_ID].indexOf(user_id) > -1) {
            io.to(concurents[user_id]).emit('unmuted', Live_ID, user_id);
            io.to(Live_ID).emit('notification', JSON.stringify({
                'notification': 'unmuted',
                'user_id': user_id,
                'by':by_user_fullname
            }));
        }
    }


    res.writeHead(200, {
        'Content-Type': 'application/json'
    });

    res.write(JSON.stringify(new Array()));
    res.end();
});
app.post('/generateOBSstreamKey', async function(req, res) {

    const user_id = req.body.user_id;

    const key = await updateStreamKey(user_id);

    res.writeHead(200, {
        'Content-Type': 'application/json'
    });

    res.write(JSON.stringify({
        'error': null,
        'key': key
    }));
    res.end();
});

app.post('/isbroadcastalive', async function(req, res) {

    const post_id = req.body.post_id;

    res.writeHead(200, {
        'Content-Type': 'application/json'
    });

    res.write(JSON.stringify({
        'exists': presenter.hasOwnProperty(post_id)
    }));
    res.end();
});

app.post('/uploadtocloud', async function(req, res) {
 
    const user_id = req.body.user_id;
    const post_id = req.body.post_id;
    const old_filename = req.body.old_filename;
    const new_filename = old_filename.replace('writing', req.body.new_filename);

    console.log('old_filename', old_filename);
    console.log('new_filename', new_filename);

    let err = null;
    fs.rename(old_filename, new_filename, function(err) {
        if ( err ) {err = err; console.log('ERROR: ' + err);}
        else uploadToCloud(post_id,user_id,new_filename);
    });


    res.writeHead(200, {
        'Content-Type': 'application/json'
    });

    res.write(JSON.stringify({
        'error': err
    }));
    res.end();
});
app.post('/check_health', async function(req, res) {

    const user_id = req.body.user_id;

    res.writeHead(200, {
        'Content-Type': 'application/json'
    });

    res.write(JSON.stringify({
        'error': null
    }));
    res.end();
});
app.post('/ping-host', function(req, res) {
    const live_id = req.body.id;

    let response = {
        'exists': 0
    };

    if (live_id in stream_health)
        response.exists = 1;


    res.writeHead(200, {
        'Content-Type': 'application/json'
    });

    res.write(JSON.stringify(response));
    res.end();
});

io.use((socket, next) => { 
    socket.id = socket.handshake.query.token;
    socket.user_id = socket.handshake.query.user_id;
    next();
}).on("error", e => console.log(e)).on('connection', function(socket) {
 
    socket.on("broadcaster", async function(live_id,is_rtmp) {
        
        if(is_rtmp)
            rtmp[live_id] = live_id;

        stream_health[live_id] = socket.id;
        /*  if(!(live_id in broadcaster)){
                console.log('new broadcaster');
                broadcaster[live_id] = socket.id;
                global.streams_timer[live_id] = new Timer({ label: live_id }).start();
          }
             
            if(!(live_id in this_live)){
                this_live[live_id] = new Array();
            }
            if(!(live_id in blocked_users)){
                blocked_users[live_id] = new Array();
            }
            
            if(!(live_id in muted_users)){
                muted_users[live_id] = new Array();
            }
            if(!(live_id in moderators)){
                moderators[live_id] = new Array();
            }
            */

        console.log('NEW BROAD');
        socket.post_id = live_id;
        broadcaster[live_id] = socket.id;
        host_userid[live_id] = socket.user_id;

        if ('delete_post_'+live_id in timeouts)
            clearTimeout(timeouts['delete_post_'+live_id]);

        if (!(global.streams_timer.hasOwnProperty(live_id) && global.streams_timer[live_id].isStarted())){console.log('new timer');
            global.streams_timer[live_id] = new Timer({
                label: live_id
            }).start();}


        if (!(this_live[live_id] instanceof Array)) {
            console.log('NEW ARR');
            this_live[live_id] = new Array();
        }
        if (!(blocked_users[live_id] instanceof Array)) {
            blocked_users[live_id] = new Array();
        }

        if (!(muted_users[live_id] instanceof Array)) {
            muted_users[live_id] = new Array();
        }
        if (!(moderators[live_id] instanceof Array)) {
            moderators[live_id] = new Array();
        }
        await socket.join(live_id);
        
        
        
        // socket.broadcast.emit("broadcaster",live_id);
       // io.to(live_id).emit("broadcaster", live_id);
        console.log('sent broad');

        console.log('LIVE ID: ' + live_id);
    });

    socket.on('reconnect_join', async function(live_id, is_rtmp) {

        /*
        setTimeout(function(){
            socket.join(live_id);
        console.log('host joined to id '+live_id);
           io.to(live_id).emit("broadcaster",live_id);
        },500);
        */
        if ('disconnect_' + socket.id in timeouts)
            clearTimeout(timeouts['disconnect_' + socket.id]);

    });
    socket.on('end_broadcast', (id, reason) => {
        
        console.log(this_live);
        if (this_live.hasOwnProperty(id))
            for (var i = 0; i < this_live[id].length; i++)
                io.to(concurents[this_live[id][i]]).emit('broadcast_end', id, reason);


        delete this_live[id];
        delete blocked_users[id];
        delete muted_users[id];
        delete moderators[id];


    });


    socket.on('join_to_live', async function(data) {

        data = JSON.parse(data);


        if(data.live_id <= 0) return;

        if (this_live.hasOwnProperty(data.live_id)) {
            if (this_live[data.live_id].indexOf(parseInt(data.user_id)) <= -1) {
                this_live[data.live_id].push(parseInt(data.user_id));
                console.log('push user ' + data.user_id);
            }

            await socket.join(data.live_id);
            if (this_live[data.live_id].hasOwnProperty('away') && this_live[data.live_id]['away']) {
                console.log('awaaaaayy');
                io.to(socket.id).emit('host_away', data.live_id);
            }

        }
        console.log('join live', data.live_id);

        io.to(data.live_id).emit('count_viewers', JSON.stringify({
            'enter': 'yes',
            'count': (this_live.hasOwnProperty(data.live_id) ? this_live[data.live_id].length : 0),
            'user': data.user
        }));

 
        var sockets = io.in(data.live_id)
        Object.keys(sockets.sockets).forEach((item) => {
          console.log("SOCKETS:", sockets.sockets[item].id)            
        })
      /*  var clients = io.sockets.adapter.rooms[data.live_id].sockets;
        console.log('___Clients in room:' + JSON.stringify(clients));
*/
    });


    socket.on('vy_lv_away', (id) => {
        console.log('away');
        io.to(id).emit('host_away', id);
        this_live[id]['away'] = 1;

    });
    socket.on('vy_lv_rm_away', (id) => {
        console.log('remove away');
        io.to(id).emit('host_remove_away', id);
        this_live[id]['away'] = 0;

    });

    socket.on('exit_from_live', async function(data) {

        data = JSON.parse(data);

        await socket.leave(data.live_id);



        if (this_live.hasOwnProperty(data.live_id)) {

            const index = this_live[data.live_id].indexOf(parseInt(data.user_id));

            if (index > -1) {

                this_live[data.live_id].splice(index, 1);
                io.to(data.live_id).emit('count_viewers', JSON.stringify({
                    'enter': 'no',
                    'count': (this_live.hasOwnProperty(data.live_id) ? this_live[data.live_id].length : 0)
                }));


            }


        }

        console.log('exit live', this_live);


    });
    socket.on('update_product_views', (data) => {
         data = JSON.parse(data);
         io.to(data.live_id).emit('ss_update_product_views', data.product_id);
    });
    socket.on('add_comment', (data) => {

        data = JSON.parse(data);
        
 
        if(moderators[data.live_id].indexOf(data.user_id) <= -1)
            data['is_moderator'] = false;
        else
            data['is_moderator'] = true;
 
        io.to(data.live_id).emit('new_comment', data);

    });

    // recording 
    socket.on('recording', (data, user_id, filename) => {

        const path = VY_CONFIG.media_root + user_id + '/streams/';


        //if (!fs.existsSync(path)){
        //fs.mkdirSync(path, { recursive: true } );
        //shell.mkdir('-p', path);

        //}

        fs.createWriteStream(path + filename + '.webm', {
            flags: 'a'
        }).write(Buffer.from(new Uint8Array(data)));
    });
   

    socket.on('send_reaction', (data) => {
        data = JSON.parse(data);
        console.log('reacted ' + data.live_id);
        io.to(data.live_id).emit('reaction_floating', data);
    });
    socket.on('delete_obs_record', (streamPath, uid) => {

        const dirPath = VY_CONFIG.media_root + uid + '/streams/writing.mp4';
 
        delete_obs_video(dirPath, 1);

    });
    socket.on('stop_obs_manually', (md5_uid, uid, _remove) => {

        const dirPath = VY_CONFIG.media_root + uid + '/streams/writing.mp4';

        if (typeof nms.getSession(concurents_obs_session[md5_uid]) != 'undefined')
            nms.getSession(concurents_obs_session[md5_uid]).reject();

        console.log('obs stopped manually');
        updateStreamKey(uid);


        if (_remove) {
            // delete stream
            setTimeout(function() {
                delete_obs_video(dirPath);
                console.log('OBS DELETED FROM TIMEOUT', _remove);
            }, 1000);

        }


        setTimeout(function() {
            io.to(concurentsmd5_id[md5_uid]).emit('obs_stopped', dirPath);
            console.log('obs stopped manually');
        }, 1000);


    });
    socket.on('connect_user', (uid, md5_uid) => {

        console.log('user conected ' + uid);

        console.log('md5 ' + md5_uid);

        if (!concurents.hasOwnProperty(uid))
            concurents[uid] = md5_uid;

        if (!concurentsmd5_id.hasOwnProperty(md5_uid))
            concurentsmd5_id[md5_uid] = md5_uid;




        console.log(concurents);
    });
    socket.on('host_reconnected_successfully', async function(live_id) {

        
        if (broadcaster.hasOwnProperty(live_id)) {
 
        if ('delete_post_'+live_id in timeouts)
            clearTimeout(timeouts['delete_post_'+live_id]);

            setTimeout(async function() {

                await socket.join(live_id);
                console.log('host joined id ' + live_id);
                const _time = global.streams_timer.hasOwnProperty(live_id) && global.streams_timer[live_id].isStarted() ? global.streams_timer[live_id].time() : 'error';

                io.to(live_id).emit("broadcaster", live_id);
                // update timer for viewers
                io.to(live_id).emit("host_network_restored", live_id, _time);
                if (global.streams_timer.hasOwnProperty(live_id) && global.streams_timer[live_id].isStarted())
                    global.streams_timer[live_id].resume();

            }, 500);

        }


    });
    socket.on("host_restore", (live_id, reason) => {
        console.log(live_id + '---' + reason);
        sendStreamLagReason(io, live_id, reason);

    });
    socket.on('host_reconnected', function(live_id) {


        io.to(live_id).emit("host_network_restored", live_id, (global.streams_timer.hasOwnProperty(live_id) && global.streams_timer[live_id].isStarted() ? global.streams_timer[live_id].time() : 'error'));

        if (global.streams_timer.hasOwnProperty(live_id) && global.streams_timer[live_id].isStarted())
            global.streams_timer[live_id].resume();

    });
    socket.on("disconnecting", async function(reason) {
        console.log('first disconnect ' + reason + ' socket id ' + socket.id);

    

        const _stream_id = Object.keys(broadcaster).find(k => broadcaster[k] === socket.id);

        await socket.leave(_stream_id);
        if (broadcaster.hasOwnProperty(_stream_id) && VY_CONFIG.reconnecting) {
            if (global.streams_timer.hasOwnProperty(_stream_id) && global.streams_timer[_stream_id].isStarted())
                global.streams_timer[_stream_id].pause();



            global.reconncting_broadcasts.push(_stream_id);
            sendStreamLagReason(io, _stream_id, reason);


        }


   
    });

    socket.on("disconnect", (reason) => {
        console.log('disconnect ' + reason);

        const _stream_id = Object.keys(broadcaster).find(k => broadcaster[k] === socket.id);
        const stream_health_id = Object.keys(stream_health).find(k => stream_health[k] === socket.id);

        if (stream_health_id > 0)
            delete stream_health[stream_health_id];

        if(_stream_id && VY_CONFIG.reconnecting) {
            const post_id = _stream_id;
                        timeouts['delete_post_'+post_id] = setTimeout(function(){
                                console.log('broadcasted ended from nodejs application due to disconnecting more than 5 minutes.' + post_id)
                                    global.reconncting_broadcasts.splice(_stream_id,1);
                                    cleanCrashed();
                                io.to(post_id).emit('end_broadcast', post_id);

                            },offline_timeout);//2 minutes
             }

 
    });
});

function createIces(kur_endpoint){

    let ices = JSON.parse(global.ice_servers);
 
    for(var i=0;i<ices.length;i++){
        const ic = ices[i];
        console.log(ic)

        if(ic.urls.includes('turn:')){

            kur_endpoint.setTurnUrl(`${ic.username}:${ic.credential}@${ic.urls.replace('turn:','')}`);
 
        } else {
                const stun_i = ic.urls.split(':');

                kur_endpoint.setStunServerAddress(ic.urls.replace(':'+stun_i[stun_i.length - 1],''));
                kur_endpoint.setStunServerPort(stun_i[stun_i.length - 1]);

        }


    }

}
/*
 * Management of WebSocket messages
 */
wss.on('connection', function(ws,req) {
    const param = url.parse(req.url, true);

    const postId = param.query.p;
    const userId = param.query.u;
 

    console.log(global.ice_servers)
    console.log('Connection received with postId ' + postId);

    ws.on('error', function(error) {
        console.log('Connection ' + postId + ' error');
        stop(postId);
    });

    ws.on('close', function() {
        console.log('Connection ' + postId + ' closed');

        stop(postId,userId);
    });

    ws.on('message', function(_message) {
        var message = JSON.parse(_message);
        console.log('Connection ' + postId + ' received message ', message);

 
        switch (message.id) {
        case 'ices':
            global.ice_servers = message.ices;
            if(!global.record_filename.hasOwnProperty(message.post_id) && message.hasOwnProperty('filename'))
            global.record_filename[message.post_id] = decodeURIComponent(message.filename) + VY_CONFIG['record_type'];
 
            if(!global.is_recording.hasOwnProperty(message.post_id) && message.hasOwnProperty('record_enabled'))
                global.is_recording[message.post_id] = message.post_id;

            
        break;
        case 'presenter':
 
            startPresenter(message.post_id,userId, ws, message.sdpOffer, function(error, sdpAnswer) {
                if (error) {
                    return ws.send(JSON.stringify({
                        id : 'presenterResponse',
                        response : 'rejected',
                        message : error
                    }));
                }
                ws.send(JSON.stringify({
                    id : 'presenterResponse',
                    response : 'accepted',
                    sdpAnswer : sdpAnswer
                }));

                //global.presenter[message.post_id]['recording'] = message.recording == 'yes' ? true : false;

            },message.reconnect);
            break;

        case 'viewer':
            startViewer(message.post_id,userId, ws, message.sdpOffer, function(error, sdpAnswer) {
                if (error) {
                    return ws.send(JSON.stringify({
                        id : 'viewerResponse',
                        response : 'rejected',
                        message : error
                    }));
                }

                ws.send(JSON.stringify({
                    id : 'viewerResponse',
                    response : 'accepted',
                    sdpAnswer : sdpAnswer
                }));
            });
            break;
        case 'reconnect_viewer':
            startViewer(message.post_id,message.userId, ws, message.sdpOffer, function(error, sdpAnswer) {
                if (error) {
                    return ws.send(JSON.stringify({
                        id : 'viewerResponse',
                        response : 'rejected',
                        message : error
                    }));
                }

                ws.send(JSON.stringify({
                    id : 'viewerResponse',
                    response : 'accepted',
                    sdpAnswer : sdpAnswer
                }));
            },1);
            break;
        case 'stop':
            stop(message.post_id,userId);
            break;

        case 'onIceCandidate':
            onIceCandidate(message.post_id, userId, message.candidate);
            break;

        default:
            ws.send(JSON.stringify({
                id : 'error',
                user_id:userId,
                message : 'Invalid message ' + message
            }));
            break;
        }
    });
});
server.listen(port, function(){
console.log(`Server is running on port ${port}`);
});