<?php
/* Smarty version 3.1.34-dev-7, created on 2025-02-01 14:26:53
  from 'C:\xampp\htdocs\vy-livestream\layout\desktop-stream-author.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.34-dev-7',
  'unifunc' => 'content_679e211dcf4f98_20150425',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '390c3e73e771a5aa6e8c4421a98821da9d319217' => 
    array (
      0 => 'C:\\xampp\\htdocs\\vy-livestream\\layout\\desktop-stream-author.html',
      1 => 1694532369,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_679e211dcf4f98_20150425 (Smarty_Internal_Template $_smarty_tpl) {
?><div class="vy_lv_a2" id="vy_lv_contains_product_desktopstreamauthor"><a href="/" class="vy_lv_gohomewbutton"><?php echo $_smarty_tpl->tpl_vars['this']->value->svg['gohome'];?>
</a><div class="vy_lv_a3vs"><div class="vy_lv_a5v" id="vy_lv_rtmpv"><div class="vy_lv_reactions_floating __dashboard" id="vy_lv_reactions_floating"></div><div id="vy_lv_productauthor_preview"></div><div class="vy_lv_dashboard_llv_vvw_tm __none js__au4x2"><div class="vy_lv_dsh_llv_vvw"><?php echo $_smarty_tpl->tpl_vars['this']->value->svg['live'];?>
&nbsp;<div class="vy_lv_eyes"><?php echo $_smarty_tpl->tpl_vars['this']->value->svg['eye'];?>
&nbsp;<span class="js__vylv_count2_viewers">0</span></div></div></div><canvas id="vy-lv-cover" style="display:none;"></canvas><video autoplay playsinline muted id="vy_lv_main_videoel"></video><div id="vy_lv_a17cam_wait_stream" class="__none"><div class="vy_lv_a18camsvg"><?php echo $_smarty_tpl->tpl_vars['this']->value->svg['camera'];?>
</div><div class="vy_lv_a19camt"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['connect_streaming_software_to_go_live'];?>
</div></div><div class="vy_lv_a17cam"><div class="vy_lv_a18camsvg"><?php echo $_smarty_tpl->tpl_vars['this']->value->svg['camera'];?>
</div><div id="vy_lv_a17cam_wait"><div class="vy_lv_a19camt"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['waiting_for_camera'];?>
</div></div><div id="vy_lv_camera_err_msg_markup"></div></div></div><div id="vy_lv_prelive_bsettings" class="vy_lv_a6s"><div class="vy_lv_a20opt"><ul><li class="vy_lv_a21optsli"><div class="vy_lv_a27ppt"><div class="vy_lv_a21optli_title"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['get_started'];?>
</div><div class="vy_lv_a22optli_desc"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['choose_how_to_starting_your_stream'];?>
</div></div></li><li class="vy_lv_a21optsli"><div class="vy_lv_a26ppt"><a class="vy_lv_a23opt __active" href="javascript:void(0);" data-liveoption="camera" onclick="vy_lvst.changeLiveOption(event,this);"><div class="vy_lv_a24opt_ic"><?php echo $_smarty_tpl->tpl_vars['this']->value->svg['camera'];?>
</div><div class="vy_lv_a24opt_title"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['use_camera'];?>
</div></a></div></li><?php if ($_smarty_tpl->tpl_vars['this']->value->settings['obs_enabled'] == true) {?><li class="vy_lv_a21optsli"><div class="vy_lv_a26ppt"><a class="vy_lv_a23opt" href="javascript:void(0);" data-liveoption="stream" onclick="vy_lvst.changeLiveOption(event,this);"><div class="vy_lv_a24opt_ic"><?php echo $_smarty_tpl->tpl_vars['this']->value->svg['key'];?>
</div><div class="vy_lv_a24opt_title"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['use_stream_key'];?>
</div></a></div></li><?php }?></ul></div><div class="vy_lv_a20opt _vylv3_ii2"><div class="vy_lv_a24opt_ic"><?php echo $_smarty_tpl->tpl_vars['this']->value->svg['info'];?>
</div><div class="vy_lv_a22optli_desc"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['live_description_info'];?>
</div></div><section id="vy_lv_stopts"><div class="vy_lv_a20opt" id="vy_lv_camera_set"><!-- Camera settings --><div class="vy_lv_a28c_s"><div class="vy_lv_a24opt_ic"><?php echo $_smarty_tpl->tpl_vars['this']->value->svg['camera'];?>
</div><Select class="vy_lv_aselect" id="vy_lv_select_videoSource"></select></div><!-- Microphone settings --><div class="vy_lv_a28c_s"><div class="vy_lv_a24opt_ic"><?php echo $_smarty_tpl->tpl_vars['this']->value->svg['microphone'];?>
</div><Select class="vy_lv_aselect" id="vy_lv_select_audioSource"></select></div><div class="vy_lv_a28c_s"><div class="vy_lv_a24opt_ic"><?php echo $_smarty_tpl->tpl_vars['this']->value->svg['speaker'];?>
</div><Select class="vy_lv_aselect" id="vy_lv_select_audioOutput"></select></div></div><div class="vy_lv_a20opt __none" id="vy_lv_stream_set"><h3 class="fbold"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['live_setup'];?>
</h3><div class="vy-lv-sfd321"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['live_setup_info'];?>
</div><br/><div class="xbe3435"><div class="xbet5235"><div class="vy_lv_sf34"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['live_server_url'];?>
</div><input dir="ltr" aria-invalid="false" class="oajrlxb2 rq0escxv f1sip0of hidtqoto e70eycc3 lzcic4wl b3i9ofy5 l6v480f0 maa8sdkg s1tcr66n aypy0576 beltcj47 p86d2i9g aot14ch1 kzx2olss oo9gr5id l94mrbxd ekzkrbhg cxgpxx05 d1544ag0 sj5x9vvc tw6a2znq k4urcfbm o8yuz56k ehryuci6 duhwxc4d bs68lrl8 f56r29tw e16z4an2 ei4baabg b4hei51z hzawbc8m tv7at329" type="text" value="rtmp://<?php echo $_smarty_tpl->tpl_vars['wo']->value['nodejs_server']['url'];
if ($_smarty_tpl->tpl_vars['wo']->value['port']['rtmp'] != 1935) {?>:<?php echo $_smarty_tpl->tpl_vars['wo']->value['port']['rtmp'];
}?>/<?php echo $_smarty_tpl->tpl_vars['wo']->value['record']['app_name'];?>
"><div class="fr53oto"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['obs_url_info'];?>
</div></div><div class="h676nmdw c4hnarmi"><div aria-label="Copy" onclick="vy_lvst._copy(this,event);" class="oajrlxb2 g5ia77u1 qu0x051f esr5mh6w e9989ue4 r7d6kgcz rq0escxv nhd2j8a9 pq6dq46d p7hjln8o kvgmc6g5 cxmmr5t8 oygrvhab hcukyx3x jb3vyjys rz4wbd8a qt6c0cv9 a8nywdso i1ao9s8h esuyzwwr f1sip0of lzcic4wl l9j0dhe7 abiwlrkh p8dawk7l cbu4d94t taijpn5t k4urcfbm" role="button" tabindex="0"><div class="rq0escxv l9j0dhe7 du4w35lb j83agx80 pfnyh3mw taijpn5t bp9cbjyn owycx6da btwxx1t3 kt9q3ron ak7q8e6j isp2s0ed ri5dt5u2 rt8b4zig n8ej3o3l agehan2d sk4xxmp2 d1544ag0 tw6a2znq oo1teu6h tv7at329"><div class="bp9cbjyn j83agx80 taijpn5t c4xchbtz by2jbhx6 a0jftqn4"><div class="rq0escxv l9j0dhe7 du4w35lb d2edcug0 hpfvmrgz bp9cbjyn j83agx80 pfnyh3mw j5wkysh0 hytbnt81"><span class="d2edcug0 hpfvmrgz qv66sw1b c1et5uql lr9zc1uh a8c37x1j keod5gw0 nxhoafnm aigsh9s9 d3f4x2em fe6kdd0r mau55g9w c8b282yb iv3no6db jq4qci2q a3bd9o3v lrazzd5p knomaqxo" dir="auto"><span class="a8c37x1j ni8dbmo4 stjgntxs l9j0dhe7 ltmttdrg g0qnabr5 _tsucc">Copy</span></span></div></div><div class="n00je7tq arfg74bv qs9ysxi8 k77z8yql i09qtzwb n7fi1qx3 b5wmifdl hzruof5a pmk7jnqg j9ispegn kr520xx4 c5ndavph art1omkt ot9fgl3s rnr61an3" data-visualcompletion="ignore"></div></div></div></div></div><div class="s1tcr66n bi6gxh9e aov4n071"></div><div class="xbe3435"><div class="xbet5235"><div class="vy_lv_sf34"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['obs_stream_key'];?>
</div><input dir="ltr" aria-invalid="false" class="oajrlxb2 rq0escxv f1sip0of hidtqoto e70eycc3 lzcic4wl b3i9ofy5 l6v480f0 maa8sdkg s1tcr66n aypy0576 beltcj47 p86d2i9g aot14ch1 kzx2olss oo9gr5id l94mrbxd ekzkrbhg cxgpxx05 d1544ag0 sj5x9vvc tw6a2znq k4urcfbm o8yuz56k ehryuci6 duhwxc4d bs68lrl8 f56r29tw e16z4an2 ei4baabg b4hei51z hzawbc8m tv7at329 __loadingkey js__loading_Key" type="text" value="Please wait for generating your stream key ..."><div class="fr53oto"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['obs_stream_key_info'];?>
</div></div><div class="h676nmdw c4hnarmi"><div onclick="vy_lvst._copy(this,event);" aria-label="Copy" class="oajrlxb2 g5ia77u1 qu0x051f esr5mh6w e9989ue4 r7d6kgcz rq0escxv nhd2j8a9 pq6dq46d p7hjln8o kvgmc6g5 cxmmr5t8 oygrvhab hcukyx3x jb3vyjys rz4wbd8a qt6c0cv9 a8nywdso i1ao9s8h esuyzwwr f1sip0of lzcic4wl l9j0dhe7 abiwlrkh p8dawk7l cbu4d94t taijpn5t k4urcfbm" role="button" tabindex="0"><div class="rq0escxv l9j0dhe7 du4w35lb j83agx80 pfnyh3mw taijpn5t bp9cbjyn owycx6da btwxx1t3 kt9q3ron ak7q8e6j isp2s0ed ri5dt5u2 rt8b4zig n8ej3o3l agehan2d sk4xxmp2 d1544ag0 tw6a2znq oo1teu6h tv7at329"><div class="bp9cbjyn j83agx80 taijpn5t c4xchbtz by2jbhx6 a0jftqn4"><div class="rq0escxv l9j0dhe7 du4w35lb d2edcug0 hpfvmrgz bp9cbjyn j83agx80 pfnyh3mw j5wkysh0 hytbnt81"><span class="d2edcug0 hpfvmrgz qv66sw1b c1et5uql lr9zc1uh a8c37x1j keod5gw0 nxhoafnm aigsh9s9 d3f4x2em fe6kdd0r mau55g9w c8b282yb iv3no6db jq4qci2q a3bd9o3v lrazzd5p knomaqxo" dir="auto"><span class="a8c37x1j ni8dbmo4 stjgntxs l9j0dhe7 ltmttdrg g0qnabr5 _tsucc">Copy</span></span></div></div><div class="n00je7tq arfg74bv qs9ysxi8 k77z8yql i09qtzwb n7fi1qx3 b5wmifdl hzruof5a pmk7jnqg j9ispegn kr520xx4 c5ndavph art1omkt ot9fgl3s rnr61an3" data-visualcompletion="ignore"></div></div></div></div></div></div></section></div></div><div id="vy_lv_prelive_st"></div></div><?php }
}
