<?php
/* Smarty version 3.1.34-dev-7, created on 2025-02-02 19:12:32
  from 'C:\xampp\htdocs\vy-livestream\layout\popups\mob\contents\pre-live-settings.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.34-dev-7',
  'unifunc' => 'content_679fb5908b9ee8_42138740',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '01827608330dcbe02992a941246a001b4a8a72f7' => 
    array (
      0 => 'C:\\xampp\\htdocs\\vy-livestream\\layout\\popups\\mob\\contents\\pre-live-settings.html',
      1 => 1694864531,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_679fb5908b9ee8_42138740 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_assignInScope('pr_opts', $_smarty_tpl->tpl_vars['this']->value->getPrivacyOpts());?><section class="vy_lv_mob_popup_allincard"><fieldset><legend><b> <?php echo $_smarty_tpl->tpl_vars['this']->value->lang['privacy'];?>
 </b> </legend><div class="vy_lv__ios-selector_parent"><input type="hidden" value="1" name="vy_lv_audience" /><div id="vy_lv__ios-selector" class="background-gradient1"><?php echo $_smarty_tpl->tpl_vars['pr_opts']->value[0]['title'];?>
</div></div></fieldset></section><section class="vy_lv_mob_popup_allincard"><fieldset><legend><b> <?php echo $_smarty_tpl->tpl_vars['this']->value->lang['title'];?>
 </b> </legend><div class="vy_lv__mobpost_title"><input name="vy_lv_title" oninput="vy_lvst.updateGoLiveData(this,'title');" onkeyup="vy_lvst.updateGoLiveData(this,'title');" type="text" placeholder="Aa.." class="vy_lv_mob_newpost_title" maxlength="40" /></div></fieldset></section><section class="vy_lv_mob_popup_allincard"><fieldset><legend><b> <?php echo $_smarty_tpl->tpl_vars['this']->value->lang['description'];?>
 </b> </legend><div class="vy_lv__mobpost_title"><textarea name="vy_lv_descr" onkeyup="vy_lvst.updateGoLiveData(this,'description');" oninput="vy_lvst.updateGoLiveData(this,'description');this.style.height = '5px';this.style.height = (this.scrollHeight)+'px';" placeholder="<?php echo $_smarty_tpl->tpl_vars['this']->value->lang['join_to_my_stream'];?>
" class="vy_lv_mob_newpost_title vy_lv_mob_newpost_desc" maxlength="250"></textarea></div></fieldset></section><section style="display:none;"class="vy_lv_mob_popup_allincard"><fieldset><legend><b> <?php echo $_smarty_tpl->tpl_vars['this']->value->lang['add_product'];?>
 </b> </legend><div class="vy_lv__mobpost_title"><a href="javascript:void(0);" class="vy_lv_a24t" id="vy_lv_addproduct_btn" onclick="vy_lvst.addProduct(this,event);"><?php echo $_smarty_tpl->tpl_vars['this']->value->svg['plus_ic'];?>
 <?php echo $_smarty_tpl->tpl_vars['this']->value->lang['add_product'];?>
</a></div></fieldset></section><?php if ($_smarty_tpl->tpl_vars['this']->value->recording) {?><section class="vy_lv_mob_popup_allincard"><fieldset><legend><b> <?php echo $_smarty_tpl->tpl_vars['this']->value->lang['recording'];?>
</b> </legend><div class="vy_lv__mobpost_recording"><div class="vy_lv__mobpost_recording_t"><label for="vy_ch_recordingtotimeline"><?php echo $_smarty_tpl->tpl_vars['this']->value->lang['record_stream'];?>
</label></div><label class="el-switch"><input type="checkbox" ontouchend="vy_lvst.updateGoLiveData(this,'record');" onclick="vy_lvst.updateGoLiveData(this,'record');" id="vy_ch_recordingtotimeline" name="vy_record_to_timeline" checked><span class="el-switch-style"></span></label></div></fieldset></section><?php }
}
}
