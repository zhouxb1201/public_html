<?php
namespace data\model;
use data\model\BaseModel as BaseModel;
/**
 * 微信菜单
 *
 */
class WeixinMenuModel extends BaseModel {
     protected $table = 'sys_weixin_menu';
     protected $rule = [
         'menu_id'  =>  '',
         'menu_event_url'  =>  'no_html_parse'
     ];
     protected $msg = [
         'menu_id'  =>  '',
     ];
}