<?php
/* Smarty version 3.1.34-dev-7, created on 2025-02-02 19:12:32
  from 'C:\xampp\htdocs\vy-livestream\layout\popups\mob\content.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.34-dev-7',
  'unifunc' => 'content_679fb59073e3c4_32684909',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'daffa9de3352c6144e2eb0df82405cd30d1f688f' => 
    array (
      0 => 'C:\\xampp\\htdocs\\vy-livestream\\layout\\popups\\mob\\content.html',
      1 => 1694532496,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_679fb59073e3c4_32684909 (Smarty_Internal_Template $_smarty_tpl) {
?><section id="vy_lv__mob_popup"><?php ob_start();
echo dirname('__FILE__');
$_prefixVariable1=ob_get_clean();
$_smarty_tpl->_subTemplateRender($_prefixVariable1."/header.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
?><p hide-on-bottom><?php ob_start();
echo dirname('__FILE__');
$_prefixVariable2=ob_get_clean();
$_smarty_tpl->_subTemplateRender($_prefixVariable2."/contents/".((string)$_smarty_tpl->tpl_vars['file_content']->value), $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
?></p><?php ob_start();
echo dirname('__FILE__');
$_prefixVariable3=ob_get_clean();
$_smarty_tpl->_subTemplateRender($_prefixVariable3."/footer.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
?></section><?php }
}
