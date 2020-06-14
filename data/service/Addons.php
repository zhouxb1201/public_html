<?php

namespace data\service;

/**
 * 扩展（插件与钩子）
 */
use data\model\AuthGroupModel;
use data\model\InstanceModel;
use data\model\InstanceTypeModel;
use data\model\ModuleModel;
use data\service\BaseService as BaseService;
use think\Db;
use data\model\SysAddonsCategoryModel;
use data\model\SysAddonsModel;
use data\model\SysAddonsClicksModel;
use data\model\VslIncreMentOrderModel;

use data\model\MessageCountModel;

class Addons extends BaseService {

    /**
     * (non-PHPdoc)
     * @see \data\api\IAddons::getModuleList()
     */
    public function getModuleList($search_text = '') {
        $addonsCategoryModel = new SysAddonsCategoryModel();
        $addonsModel = new SysAddonsModel();
        $addonsClickModel = new SysAddonsClicksModel();
        $module_id_array = explode(',', $this->module_id_array);
        $modules = \think\Request::instance()->module();
        $auth_group = new AuthGroupModel();
        $system_auth = $auth_group->getInfo(['is_system'=>1,'instance_id'=>0,'website_id'=>$this->website_id],'order_id,group_id');
        if($modules=='admin' && $system_auth['order_id']){
            $module = new ModuleModel();
            $order_ids = explode(',',$system_auth['order_id']);
            $order = new VslIncreMentOrderModel();
            $shop_module_id_arrays=',';
            $default_module_id_array =$module_id_array;
            foreach ($order_ids as $value){
                $addons_id = $order->getInfo(['order_id'=>$value],'*');
                $shop_module_id = $module->Query(['addons_sign'=>$addons_id['addons_id'],'module'=>'admin'],'module_id');
                //查出当前店铺是否有此增值应用的权限
                $instance_model = new InstanceModel();
                $instance_type_model = new InstanceTypeModel();
                $instance_type_id = $instance_model->Query(['website_id' => $this->website_id,'instance_id' => $this->instance_id],'instance_typeid')[0];
                $type_module_array = $instance_type_model->Query(['instance_typeid' => $instance_type_id],'type_module_array')[0];
                $type_module_array = explode(',',$type_module_array);
                if(in_array($shop_module_id,$type_module_array)) {
                    if($addons_id['expire_time']>time() && !in_array($addons_id['addons_id'],$default_module_id_array)){
                        $shop_module_id_array = implode(',',$shop_module_id);
                        $shop_module_id_arrays.= $shop_module_id_array;
                    }else{
                        $default_module_id_array= array_diff($default_module_id_array,$shop_module_id);
                    }
                }
            }
            $module_id_array = implode(',',$default_module_id_array).$shop_module_id_arrays;
            $module_id_array = explode(',',$module_id_array);
        }
        $addonsCategory = array();
        if ($search_text) {//有关键词查询，以搜索结果组装数组
            $addonsList = $addonsModel->getQuery(['title' => ['like', "%" . $search_text . "%"]], 'id', 'create_time desc');
            foreach ($addonsList as $aKey => $aVal) {
                $addonsList[$aKey] = $aVal->toArray();
            }
            $addonsCategory[] = ['category_name' => '搜索结果', 'addons' => implode(',', array_column($addonsList, 'id'))];
        } else {
            $addonsCategory = $addonsCategoryModel->getQuery([], 'category_name,category_id', 'sort asc');
            $oftenUseAddons = ['category_name' => '常用功能', 'addons' => '']; //查找常用应用组装成数组放入插件分类数组起始位置
            array_unshift($addonsCategory, ['category_name' => '未分类', 'category_id' => 0]);
            $clicksAddons = $addonsClickModel->pageQuery(1, 5, ['shop_id' => $this->instance_id, 'website_id' => $this->website_id], 'clicks desc', 'addons_id');
            foreach ($clicksAddons['data'] as $aKey => $aVal) {
                $clicksAddons[$aKey] = $aVal->toArray();
            }
            if ($clicksAddons) {
                $oftenUseAddons['addons'] = implode(',', array_column($clicksAddons, 'addons_id'));
            }
            array_unshift($addonsCategory, $oftenUseAddons);
        }
        foreach ($addonsCategory as $key => $category) {
            $aAddons = [];
            if ($category['category_id']) {
                $category['addons'] = $addonsModel->Query(['category_id' => $category['category_id']], 'id');
                if (!$category['addons']) {
                    unset($addonsCategory[$key]);
                    continue;
                }
                $aAddons = $category['addons'];
                if (!$aAddons) {
                    continue;
                }
            } elseif ($category['addons']) {
                $aAddons = explode(',', $category['addons']);
            }
            $moduleList = [];//有权限
            $unmodule = [];//没权限
            $soon =[];//即将上线
            foreach ($aAddons as $k1 => $vAddons) {
                $condition = 'sm.module_id = sa.module_id';
                if ($modules == 'admin') {
                    $condition = 'sm.module_id = sa.admin_module_id';
                }
                $module = Db::table("sys_module sm")->join('sys_addons sa', $condition, 'left')->where(['sa.id' => $vAddons])->field('sm.*,sa.name,sa.logo,sa.description,sa.logo_small,sa.logo_often,sa.status,sa.up_status,sa.is_value_add,sa.id,sa.sort as ssort,sa.title')->find();
                
                if (!$module) {
                    continue;
                }
                if(!file_exists(ADDON_PATH . $module['name'])){
                    continue;
                }
                $module['permission'] = 1;
                if (!in_array($module['module_id'], $module_id_array) || $module['up_status'] == 2) {//判断权限，加入标识
                    $module['permission'] = 0;
                    if($module['up_status'] == 2){
                        $soon[] = $module;
                        continue;
                    }
                    $unmodule[] = $module;
                    continue;
                }
                $moduleList[] = $module;
            }

            $moduleList = $this->list_sort_by($moduleList, 'ssort', 'asc');
            $unmodule = $this->list_sort_by($unmodule, 'ssort', 'asc');
            $soon = $this->list_sort_by($soon, 'ssort', 'asc');
            $moduleList = array_merge($moduleList, $unmodule, $soon);
            unset($vAddons);
            if (!$moduleList) {
                unset($addonsCategory[$key]);
            } else {
                $addonsCategory[$key]['addons'] = $moduleList;
            }
        }
        unset($category);
        return array_values($addonsCategory);
    }

    public function addAddons($name, $title, $description, $status, $config, $author, $version, $has_adminlist, $has_addonslist, $config_hook, $content) {
        $sys_addons = new SysAddonsModel();
        $data = array(
            'name' => $name,
            'title' => $title,
            'description' => $description,
            'status' => $status,
            'config' => $config,
            'author' => $author,
            'version' => $version,
            'has_adminlist' => $has_adminlist,
            'has_addonslist' => $has_addonslist,
            'config_hook' => $config_hook,
            'content' => $content,
            'create_time' => time(),
        );
        $res = $sys_addons->save($data);
        return $sys_addons->id;
    }

    public function getModuleInfo($addons_id) {
        $sys_addons = new SysAddonsModel();
        $res = $sys_addons->getInfo(['id' => $addons_id]);
        return $res;
    }

    public function getOrderInfo($order_id) {
        $order = new VslIncrementOrderModel();
        $res = $order->getInfo(['order_id' => $order_id]);
        return $res;
    }

    public function createOrder($addons_id, $time) {
        $sys_addons = new SysAddonsModel();
        $module_info = $sys_addons->getInfo(['id' => $addons_id]);
        if($module_info['is_code']){
            return -1;
        }
        $info['cycle_price'] = $module_info['cycle_price'] ? json_decode(str_replace("&quot;", "\"", $module_info['cycle_price']), true) : '';
        $pay_money = 0;
        $canBuy = 1;
        $shopStatus = getAddons('shop', $this->website_id);
        foreach ($info['cycle_price'] as $k => $value) {
            if ($time == $value['cycle']) {
                switch ($value['port']) {
                    case 1:
                        $canBuy = $shopStatus ? -2 : 1;
                        break;
                    case 2:
                        $canBuy = $shopStatus ? 1 : -3;
                        break;
                }
                $pay_money = $value['price'];
            }
        }
        if($canBuy < 0){
            return $canBuy;
        }
        $data = array(
            'order_no' => time() . mt_rand(100000, 999999) . $this->uid,
            'out_trade_no' =>  time() . $this->uid,
            'order_money' => $pay_money,
            'addons_id' => $addons_id,
            'website_id' => $this->website_id,
            'create_time' => time(),
            'circle_time' => $time,
            'order_type' => 1,
            'addons_name' => $module_info['name']
        );
        $order = new VslIncrementOrderModel();
        $order->save($data);
        $order_id = $order->order_id;
        $pay = new UnifyPay();
        $pay->createIncrementPayment($data['out_trade_no'], 1, $order_id, $module_info['title'] . '订单', $module_info['title'] . '订单', $pay_money, $data['create_time']);
        return $order_id;
    }

    public function createOrderForMessage($num) {
        $config = new Config();
        $info = $config->getConfigbyWebsiteId(0, 0, 'MOBILEMESSAGE');
        $info = json_decode($info['value'], true);
        $message_price = $info['message_price'] ? json_decode(str_replace("&quot;", "\"", $info['message_price']), true) : [];
        $pay_money = 0;
        foreach ($message_price as $value) {
            if ($num == $value['num']) {
                $pay_money = $value['price'];
            }
        }
        $data = array(
            'order_no' => time() . mt_rand(100000, 999999) . $this->uid,
            'out_trade_no' =>  time() . $this->uid,
            'order_money' => $pay_money,
            'addons_id' => 0,
            'website_id' => $this->website_id,
            'create_time' => time(),
            'circle_time' => $num,
            'order_type' => 2
        );
        $order = new VslIncrementOrderModel();
        $order->save($data);
        $order_id = $order->order_id;
        $pay = new UnifyPay();
        $pay->createIncrementPayment($data['out_trade_no'], 1, $order_id, '短信充值订单', '短信充值订单', $pay_money, $data['create_time']);
        return $order_id;
    }

    /**
     * (non-PHPdoc)
     * @see \data\api\IExtend::updateHooks()
     */
    public function updateHooks($addons_name) {
        $sys_hooks = new SysHooksModel();
        $addons_class = get_addon_class($addons_name); //获取插件名
        if (!class_exists($addons_class)) {
            $this->error = "未实现{$addons_name}插件的入口文件";
            return false;
        }
        $methods = get_class_methods($addons_class);
        $hooks = $sys_hooks->column('name');
        $common = array_intersect($hooks, $methods); //对比返回交集
        if (!empty($common)) {
            foreach ($common as $hook) {
                $flag = $this->updateAddons($hook, array($addons_name));
                if (false === $flag) {
                    $this->removeHooks($addons_name);
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * (non-PHPdoc)
     * @see \data\api\IExtend::updateAddons()
     */
    public function updateAddons($hook_name, $addons_name) {
        $sys_hooks = new SysHooksModel();
        $hooks_info = $sys_hooks->getInfo(['name' => $hook_name], 'addons');
        $o_addons = $hooks_info['addons'];
        if ($o_addons) {
            $o_addons = explode(',', $o_addons);
        }
        if ($o_addons) {
            $addons = array_merge($o_addons, $addons_name);
            $addons = array_unique($addons);
        } else {
            $addons = $addons_name;
        }
        $addons = implode(',', $addons);
        if ($o_addons) {
            $o_addons = implode(',', $o_addons);
        }
        $res = $sys_hooks->save(['addons' => $addons], ['name' => $hook_name]);
        if (false === $res) {
            $sys_hooks->save(['addons' => $o_addons], ['name' => $hook_name]);
        }
        return $res;
    }

    /**
     * (non-PHPdoc)
     * @see \data\api\IExtend::removeHooks()
     */
    public function removeHooks($addons_name) {
        $sys_hooks = new SysHooksModel();
        $addons_class = get_addon_class($addons_name);
        if (!class_exists($addons_class)) {
            return false;
        }
        $methods = get_class_methods($addons_class);
        $hooks = $sys_hooks->column('name');
        $common = array_intersect($hooks, $methods);
        if ($common) {
            foreach ($common as $hook) {
                $flag = $this->removeAddons($hook, array($addons_name));
                if (false === $flag) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * (non-PHPdoc)
     * @see \data\api\IExtend::removeAddons()
     */
    public function removeAddons($hook_name, $addons_name) {
        $sys_hooks = new SysHooksModel();
        $hooks_info = $sys_hooks->getInfo(['name' => $hook_name], 'addons');
        $o_addons = explode(',', $hooks_info['addons']);
        if ($o_addons) {
            $addons = array_diff($o_addons, $addons_name);
        } else {
            return true;
        }
        $addons = implode(',', $addons);
        $o_addons = implode(',', $o_addons);
        $flag = $sys_hooks->save(['addons' => $addons], ['name' => $hook_name]);
        if (false === $flag) {
            $sys_hooks->save(['addons' => $o_addons], ['name' => $hook_name]);
        }
        return $flag;
    }

    /**
     * (non-PHPdoc)
     * @see \data\api\IExtend::deleteAddons()
     */
    public function deleteAddons($condition) {
        $sys_addons = new SysAddonsModel();
        return $sys_addons->destroy($condition);
    }

    /**
     * (non-PHPdoc)
     * @see \data\api\IExtend::getAddonsInfo()
     */
    public function getAddonsInfo($condition, $field = '*') {
        $sys_addons = new SysAddonsModel();
        return $sys_addons->getInfo($condition, $field);
    }

    /**
     * (non-PHPdoc)
     * @see \data\api\IExtend::updateAddonsStatus()
     */
    public function updateAddonsStatus($id, $status) {
        $sys_addons = new SysAddonsModel();
        return $sys_addons->save(['status' => $status], ['id' => $id]);
    }

    /**
     * (non-PHPdoc)
     * @see \data\api\IExtend::getPluginList()
     */
    public function getPluginList($id) {
        $sys_addons = new SysAddonsModel();
        $addons_info = $sys_addons->getInfo(['id' => $id], 'name');

        $addon_name = $addons_info['name'];

        $addon_dir = ADDON_PATH . $addon_name . '/';

        $dirs = array_map('basename', glob($addon_dir . '*', GLOB_ONLYDIR));
        if ($dirs === FALSE || !file_exists($addon_dir)) {
            $this->error = '插件目录不可读或者不存在';
            return FALSE;
        }
        $addon_type_class = get_addon_class($addon_name);
        if (!class_exists($addon_type_class)) { // 实例化插件失败忽略执行
            trace($addon_type_class);
            \think\Log::record('插件' . $value . '的入口文件不存在！');
            return false;
//             continue;
        }
        $obj = new $addon_type_class ();
        $table = $obj->table;

        $addons = array(); //已安装的数组
//         var_dump($dirs);
        $where ['name'] = array('in', $dirs);
        $list = Db::table("$table")->where($where)->select();
        foreach ($list as $addon) {
            $addon ['uninstall'] = 0;
            $addons [$addon ['name']] = $addon;
        }

        foreach ($dirs as $value) {
            if (!isset($addons [$value]) && ($value != 'core')) {
                //不在已安装插件数组中
                //读取配置文件
                $temp_arr = array();
                if (is_file($addon_dir . $value . '/config.php')) {
                    $temp_arr = include $addon_dir . $value . '/config.php';
                }
                $addons [$value] = $temp_arr;
            }
        }
        $addons = $this->list_sort_by($addons, 'id');
        return $addons;
    }

    /**
     * (non-PHPdoc)
     * @see \data\api\IExtend::getHooksList()
     */
    public function getHooksList($page_index = 1, $page_size = 0, $condition = '', $order = '', $field = '*') {
        $sys_hooks = new SysHooksModel();
        return $sys_hooks->pageQuery($page_index, $page_size, $condition, $order, $field);
    }

    /**
     * (non-PHPdoc)
     * @see \data\api\IExtend::getHoodsInfo()
     */
    public function getHoodsInfo($condition, $field = '*') {
        $sys_hooks = new SysHooksModel();
        $info = $sys_hooks->getInfo($condition, $field);
        return $info;
    }

    /**
     * (non-PHPdoc)
     * @see \data\api\IExtend::addHooks()
     */
    public function addHooks($name, $description, $type) {
        $sys_hooks = new SysHooksModel();
        $data = array(
            'name' => $name,
            'description' => $description,
            'type' => $type,
            'update_time' => time(),
        );
        $sys_hooks->save($data);
        return $sys_hooks->id;
    }

    /**
     * (non-PHPdoc)
     * @see \data\api\IExtend::editHooks()
     */
    public function editHooks($id, $name, $description, $type, $addons) {
        $sys_hooks = new SysHooksModel();
        $data = array(
            'name' => $name,
            'description' => $description,
            'type' => $type,
//             'addons' => $addons,
            'update_time' => time(),
        );
        $res = $sys_hooks->save($data, ['id' => $id]);
        return $res;
    }

    /**
     * (non-PHPdoc)
     * @see \data\api\IExtend::deleteHooks()
     */
    public function deleteHooks($id) {
        $sys_hooks = new SysHooksModel();
        return $sys_hooks->destroy($id);
    }

    /**
     * (non-PHPdoc)
     * @see \data\api\IExtend::updateAddonsConfig()
     */
    public function updateAddonsConfig($condition, $config) {
        $sys_addons = new SysAddonsModel();
        return $sys_addons->save(['config' => $config], $condition);
    }

    /**
     * 重新排序
     * @param unknown $list
     * @param unknown $field
     * @param string $sortby
     */
    protected function list_sort_by($list, $field, $sortby = 'asc') {
        if (is_array($list)) {
            $refer = $resultSet = array();
            foreach ($list as $i => $data)
                $refer[$i] = &$data[$field];
            switch ($sortby) {
                case 'asc': // 正向排序
                    asort($refer);
                    break;
                case 'desc':// 逆向排序
                    arsort($refer);
                    break;
                case 'nat': // 自然排序
                    natcasesort($refer);
                    break;
            }
            foreach ($refer as $key => $val)
                $resultSet[] = &$list[$key];
            return $resultSet;
        }
        return false;
    }

    /**
     *  增加应用访问量
     *  @param string $addons
     */
    public function addAddonsClicks($addons = '', $port = 'platform') {
        $addonsClicksModel = new SysAddonsClicksModel();
        $addonsModel = new SysAddonsModel();
        $condition = ['config_hook' => $addons];
        if ($port == 'admin') {
            $condition = ['config_admin_hook' => $addons];
        }
        $addons_id = $addonsModel->getInfo($condition)['id'];
        $checkIsAdd = $addonsClicksModel->getInfo(['addons_id' => $addons_id, 'shop_id' => $this->instance_id, 'website_id' => $this->website_id]);
        if (!$checkIsAdd) {
            $data = [
                'clicks' => 1,
                'addons_id' => $addons_id,
                'shop_id' => $this->instance_id,
                'website_id' => $this->website_id
            ];
            $addonsClicksModel->save($data);
        } else {
            $addonsClicksModel->save(['clicks' => $checkIsAdd['clicks'] + 1], ['id' => $checkIsAdd['id']]);
        }
        return true;
    }
    
    /*
     * 增加商家短信余额
     */
    public function addMessageCount($count, $website_id){
        if(!$count || !$website_id){
            return false;
        }
        $messageCount = new MessageCountModel();
        $checkMessageCount = $messageCount->getInfo(['website_id' => $website_id],'count');
        if(!$checkMessageCount){
            return $messageCount->save(['count' => $count, 'website_id' => $website_id, 'update_time'=>time()]);
        }
        return $messageCount->save(['count' => $count + $checkMessageCount['count'],'update_time'=>time()],['website_id' => $website_id]);
    }
    /*
     * 减少商家短信余额
     */
    public function reduceMessageCount($count, $website_id){
        if(!$count || !$website_id){
            return false;
        }
        $messageCount = new MessageCountModel();
        $checkMessageCount = $messageCount->getInfo(['website_id' => $website_id],'count');
        if(!$checkMessageCount){
            return false;
        }
        if($checkMessageCount['count'] < $count){
            $count = $checkMessageCount['count'];
        }
        return $messageCount->save(['count' => $checkMessageCount['count'] - $count,'update_time'=>time()],['website_id' => $website_id]);
    }

}
