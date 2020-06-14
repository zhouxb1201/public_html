<?php

namespace data\service;

/**
 * 扩展（插件与钩子）
 */
use data\service\BaseService as BaseService;
use data\model\SysAddonsModel;
use data\model\SysAddonsCategoryModel;
use data\model\SysHooksModel;
use data\model\MerchantVersionModel;
use data\model\VslIncreMentOrderModel;
use data\model\WebSiteModel;
use data\model\AuthGroupModel;
use think\Db;

class Extend extends BaseService {

    /**
     * (non-PHPdoc)
     * @see \data\api\IExtend::getAddonsList()
     */
    public function getAddonsList($condition = '') {

        $sys_addons = new SysAddonsModel();
        $sys_addons_category = new SysAddonsCategoryModel();
        if (!$addon_dir)
            $addon_dir = ADDON_PATH;
        $dirs = array_map('basename', glob($addon_dir . '*', GLOB_ONLYDIR));
        if ($dirs === FALSE || !file_exists($addon_dir)) {
            $this->error = '插件目录不可读或者不存在';
            return FALSE;
        }
        $addons = array();
        $uninstallAddons = array();
        $where['name'] = array('in', $dirs);
        if ($condition['title']) {
            $where['title'] = ['like', "%" . $condition['title'] . "%"];
        }
        if ($condition['type'] != 'all') {
            $where['is_value_add'] = $condition['type'];
        }
        $list = $sys_addons->getQuery($where, '*', 'create_time desc');
        $listCategory = $sys_addons_category->getQuery([], 'category_id,category_name', 'sort asc');
        array_unshift($listCategory, ['category_id' => -1, 'category_name' => '未安装'], ['category_id' => 0, 'category_name' => '未分类']);
        foreach ($listCategory as $kcat => $vcat) {
            $where['category_id'] = $vcat['category_id'];
            $addonslist = $sys_addons->getQuery($where, '*', 'sort asc');
            foreach ($addonslist as $ka => $va) {
                $addonslist[$ka]['uninstall'] = 0;
                $versions = $this->getInvolvingVersion($va['module_id']);
                if ($condition['version'] && !in_array($condition['version'], $versions['id'])) {
                    unset($addonslist[$ka]);
                    continue;
                }
                $addonslist[$ka]['versions'] = $versions['type_name'] ? implode(',', $versions['type_name']) : '';
                $cycle_price = $va['cycle_price'] ? json_decode(str_replace("&quot;", "\"", $va['cycle_price']), true) : '';
                $addonslist[$ka]['cycle_price'] = $this->dealCycle($cycle_price);
            }
            $listCategory[$kcat]['addonlist'] = array_values($addonslist);
            $listCategory[$kcat]['count'] = count($addonslist);
        }

        // print_r($listCategory);die;
//        foreach ( $list as $key => $value ) {
//            $list [$key] = $value->toArray ();  //对象转数组
//            $categorys='';
//            foreach($listCategory  as $category){
//                $add = explode(',', $category['addons']);
//                if(in_array($list [$key]['id'], $add)){
//                    $categorys .=$category['category_name'].',';
//                    $list [$key]['category'] =substr($categorys, 0, -1);
//                }
//            }
//            unset($category);
//        }
//        
        foreach ($list as $addon) {
            $addons [$addon ['name']] = $addon;
        }

        foreach ($dirs as $value) {
            if (!isset($addons [$value])) {
                $class = get_addon_class($value);

                if (!class_exists($class)) { // 实例化插件失败忽略执行
                    trace($class);
                    \think\Log::record('插件' . $value . '的入口文件不存在！');
                    continue;
                }
                $obj = new $class ();
                $uninstallAddons [$value] = $obj->info;

                if ($uninstallAddons [$value]) {
                    $uninstallAddons [$value] ['uninstall'] = 1;
                    $uninstallAddons [$value] ['sort'] = 0;
                    $uninstallAddons [$value] ['versions'] = '';
                    $uninstallAddons [$value] ['cycle_price'] = '';
                    unset($uninstallAddons [$value] ['status']);
                }
            }
        }

        //根据名称条件筛选相应的插件
        if ($condition['title']) {
            foreach ($uninstallAddons as $ak => $av) {
                if (strpos($av['title'], $condition['title']) === false) {
                    unset($uninstallAddons[$ak]);
                }
            }
        }
        $uninstallAddons = array_values($uninstallAddons);
        if (!$condition['version'] && $condition['type'] == 'all') {
            $listCategory[0]['addonlist'] = $uninstallAddons;
            $listCategory[0]['count'] = count($uninstallAddons);
        }

        foreach ($listCategory as $kcat2 => $vcat2) {
            if (!$vcat2['addonlist']) {
                unset($listCategory[$kcat2]);
            }
        }
        $listCategory = array_values($listCategory);
        //print_r($listCategory);die;
        //$addons = $this->list_sort_by ( $addons, 'status', 'desc' );
        return $listCategory;
    }

    public function addAddons($name, $title, $description, $status, $config, $author, $version, $has_adminlist, $has_addonslist, $config_hook, $content, $module_id, $admin_module_id, $config_admin_hook, $no_set, $category_id = 0, $logo = '', $logo_small = '', $logo_often = '') {
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
            'module_id' => $module_id,
            'admin_module_id' => $admin_module_id,
            'config_admin_hook' => $config_admin_hook,
            'no_set' => $no_set,
            'category_id' => $category_id,
            'logo' => $logo,
            'logo_small' => $logo_small,
            'logo_often' => $logo_often
        );
        $res = $sys_addons->save($data);
        return $sys_addons->id;
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

    public function getHooksName() {
        $sys_hooks = new SysHooksModel();
        $hooks = $sys_hooks->column('name');
        if ($hooks) {
            return $hooks;
        }
        return false;
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

    public function updateAddonsSort($id, $sort) {
        $sys_addons = new SysAddonsModel();
        return $sys_addons->save(['sort' => $sort], ['id' => $id]);
    }

    /**
     * (non-PHPdoc)
     */
    public function updateAddonsLogo($id, $upload) {
        $sys_addons = new SysAddonsModel();
        return $sys_addons->save(['logo' => $upload], ['id' => $id]);
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
    public function list_sort_by($list, $field, $sortby = 'asc') {

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
     * (non-PHPdoc)
     * @see \data\api\IExtend::getAddonsCategoryList()
     */
    public function getAddonsCategoryList($page_index = 1, $page_size = PAGESIZE, $condition = '', $order = '', $field = '*') {
        $sys_addons_category = new SysAddonsCategoryModel();
        return $sys_addons_category->pageQuery($page_index, $page_size, $condition, $order, $field);
    }

    /**
     * (non-PHPdoc)
     * @see \data\api\IExtend::deleteCategory()
     */
    public function deleteCategory($category_id = 0) {
        $sys_addons_category = new SysAddonsCategoryModel();

        $category_info = $sys_addons_category->getInfo(['category_id' => $category_id], 'category_id');
        if (empty($category_info)) {
            return 0;
        }
        $res = $sys_addons_category->where('category_id', $category_id)->delete();
        return $res;
    }

    /**
     * (non-PHPdoc)
     * @see \data\api\IExtend::getCategoryDetail()
     */
    public function getCategoryDetail($category_id) {
        $sys_addons_category = new SysAddonsCategoryModel();
        return $sys_addons_category->getInfo(['category_id' => $category_id], '*');
    }

    /**
     * (non-PHPdoc)
     * @see \data\api\IExtend::updateCategory()
     */
    public function updateCategory($category_id, $category_name, $sort) {
        $sys_addons_category = new SysAddonsCategoryModel();
        $category_info = $sys_addons_category->getInfo(['category_id' => $category_id], '*');
        if ($category_name != $category_info['category_name']) {
            $count = $sys_addons_category->getCount(['category_name' => $category_name]);
            if ($count > 0) {
                return ADDONS_CATEGORY_NAME_REPEAT;
            }
        }
        $data = array(
            'category_name' => $category_name,
            'sort' => $sort,
            'update_time' => time()
        );
        $res = $sys_addons_category->save($data, ['category_id' => $category_id]);
        return $res;
    }

    /**
     * (non-PHPdoc)
     * @see \data\api\IExtend::addCategory()
     */
    public function addCategory($category_name, $sort) {
        $sys_addons_category = new SysAddonsCategoryModel();
        $count = $sys_addons_category->getCount(['category_name' => $category_name]);
        if ($count > 0) {
            return ADDONS_CATEGORY_NAME_REPEAT;
        }
        $data = array(
            'category_name' => $category_name,
            'sort' => $sort,
            'update_time' => time(),
            'create_time' => time()
        );
        $res = $sys_addons_category->save($data);
        return $res;
    }

    /**
     * (non-PHPdoc)
     * @see \data\api\IExtend::getInstallAddonsList()
     */
    public function getInstallAddonsList($page_index = 1, $page_size = PAGESIZE, $condition = '', $order = '', $field = '*') {

        $sys_addons = new SysAddonsModel();
        if ($page_size == 0) {
            $page_size = PAGESIZE;
        }
        return $sys_addons->getQuery($condition, $field, $order);
    }

    /*
     * 获取涉及版本
     */

    public function getInvolvingVersion($module_id = 0) {
        $merchant = new MerchantVersionModel();
        $version_list = $merchant->getQuery([], 'merchant_versionid,type_name,type_module_array', '');
        $result = [
            'id' => [],
            'type_name' => []
        ];
        if (!$version_list || !$module_id) {
            return $result;
        }
        foreach ($version_list as $val) {
            if (!$val['type_module_array']) {
                continue;
            }
            $module_id_array = explode(',', $val['type_module_array']);
            if (in_array($module_id, $module_id_array)) {
                $result['id'][] = $val['merchant_versionid'];
                $result['type_name'][] = $val['type_name'];
            }
        }
        return $result;
    }

    /*
     * 编辑应用
     * @param array $data
     * @return int
     */

    public function editAddons(array $data) {
        $addonsModel = new SysAddonsModel();
        $addonsModel->startTrans();
        $addons_id = $data['id'];
        if (!$addons_id) {
            return -1006;
        }
        try {
            $editData = array(
                'title' => $data['title'],
                'category_id' => $data['category_id'],
                'description' => $data['description'],
                'content' => $data['content'],
                'is_value_add' => $data['is_value_add'],
                'logo' => $data['logo'],
                'cycle_price' => $data['cycle_price'],
                'sort' => $data['sort'],
            );
            $addonsModel->save($editData, ['id' => $addons_id]);
            $addonsModel->commit();
            return $addons_id;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $addonsModel->rollback();
            return $e->getMessage();
        }
    }

    /*
     * 处理周期数组
     */

    public function dealCycle($cycle_price = []) {
        if (!$cycle_price) {
            return '';
        }
        $result = [
            'cycle' => [],
            'price' => [],
            'market_price' => []
        ];
        foreach ($cycle_price as $val) {
            $result['cycle'][] = $val['cycle_name'];
            $result['price'][] = $val['price'];
            $result['market_price'][] = $val['market_price'];
        }
        return $result;
    }

    /*
     * 订购记录
     */

    public function getOrderRecordList($page_index, $page_size, $condition, $order) {
        $vinoModel = new VslIncreMentOrderModel();
        $order_list = $vinoModel->getOrderRecordViewList($page_index, $page_size, $condition, $order);
        if ($order_list['data']) {
            foreach ($order_list['data'] as $k => $v) {
                $order_list['data'][$k]['expire_time'] = $v['expire_time'] ? date('Y-m-d H:i:s', $v['expire_time']) : '待付款';
                switch ($v['circle_time']) {
                    case 1:
                        $order_list['data'][$k]['circle_time'] = '一个月';
                        break;
                    case 2:
                        $order_list['data'][$k]['circle_time'] = '三个月';
                        break;
                    case 3:
                        $order_list['data'][$k]['circle_time'] = '五个月';
                        break;
                    case 4:
                        $order_list['data'][$k]['circle_time'] = '一年';
                        break;
                    case 5:
                        $order_list['data'][$k]['circle_time'] = '两年';
                        break;
                    case 6:
                        $order_list['data'][$k]['circle_time'] = '三年';
                        break;
                    case 7:
                        $order_list['data'][$k]['circle_time'] = '四年';
                        break;
                    default:
                        $order_list['data'][$k]['circle_time'] = '--';
                        break;
                }
                switch ($v['order_status']) {
                    case 0:
                        $order_list['data'][$k]['status_name'] = '待付款';
                        break;
                    case 1:
                        $order_list['data'][$k]['status_name'] = '已支付';
                        break;
                    case 2:
                        $order_list['data'][$k]['status_name'] = '已关闭';
                        break;
                    default:
                        break;
                }
                switch ($v['payment_type']) {
                    case 1:
                        $order_list['data'][$k]['payment_type'] = '微信';
                        break;
                    case 2:
                        $order_list['data'][$k]['payment_type'] = '支付宝';
                        break;
                    case 3:
                        $order_list['data'][$k]['payment_type'] = '线下打款';
                        break;
                    default:
                        $order_list['data'][$k]['payment_type'] = '待付款';
                        break;
                }
            }
        }
        return $order_list;
    }

    /**
     * (non-PHPdoc)获取未购买的增值应用
     */
    public function getValueAddonsList($website_id = 0) {
        $sysAddons = new SysAddonsModel();
        $addonsList = $sysAddons->getQuery(['is_value_add' => 1], 'title,id', 'sort asc');
        $viOrderModel = new VslIncreMentOrderModel();
        $hasBuy = $viOrderModel->Query(['website_id' => $website_id, 'expire_time' => ['>', time()]], 'addons_id');
        foreach ($addonsList as $key => $val) {
            if (in_array($val['id'], $hasBuy)) {
                unset($addonsList[$key]);
            }
        }
        return $addonsList;
    }

    /*
     * 添加订购记录
     */

    public function addAddonsRecord(array $data) {
        if (!$data || !$data['addons']) {
            return 0;
        }
        $order = new VslIncrementOrderModel();
        $sys_addons = new SysAddonsModel();
        $order->startTrans();
        try {
            $insertData = [];
            $order_ids = [];
            foreach ($data['addons'] as $key => $val) {
                $module_info = $sys_addons->getInfo(['id' => $val]);
                $insertData[$key] = array(
                    'order_no' => time() . mt_rand(100000, 999999) . $data['website_id'],
                    'out_trade_no' => 'IN' . time() . $data['website_id'],
                    'order_money' => $data['order_money'],
                    'addons_id' => $val,
                    'website_id' => $data['website_id'],
                    'create_time' => time(),
                    'circle_time' => 0,
                    'payment_type' => $data['payment_type'],
                    'order_status' => 2,
                    'expire_time' => $data['expire_time'],
                    'order_type' => 1,
                    'addons_name' => $module_info['name']
                );
            }
            $res = $order->saveAll($insertData);
            foreach($res as $valo){
                $order_ids[] = $valo['order_id'];
            }
            $websiteModel = new WebSiteModel();
            $userGroup = $websiteModel->alias('sw')
            ->join('sys_user_admin sua', 'sw.uid=sua.uid', 'left')
            ->join('sys_user_group sug', 'sua.group_id_array=sug.group_id', 'left')
            ->field('sug.group_id,sug.order_id')
            ->find();
            if($userGroup){
                $userGroupModel = new AuthGroupModel();
                $orderIdArr = $userGroup['order_id'] ? explode(',', $userGroup['order_id']) : [];
                $resultOrderId = array_unique(array_merge($order_ids,$orderIdArr)); 
                $userGroupModel ->save(['order_id' => implode(',', $resultOrderId)],['group_id' => $userGroup['group_id']]);
            }
            $order->commit();
            return 1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $order->rollback();
            return $e->getMessage();
        }
    }
    /*
     * 安装完应用，更新用户组和版本权限
     */
    public function updateModule(){
        $moduleModel = new \data\model\ModuleModel();
        $moduleArray = $moduleModel->Query(['module' => 'platform'], 'module_id');
        if(!$moduleArray){
            return;
        }
        $moduleStr = implode(',', $moduleArray);
        $shopModuleArray = $moduleModel->Query(['module' => 'admin'], 'module_id');
        $shopModuleStr = implode(',', $shopModuleArray);
        $userGroupModel = new AuthGroupModel();
        $userGroupModel->save(['module_id_array' => $moduleStr, 'shop_module_id_array' => $shopModuleStr],['is_system' => 1]);
        $merchantModel = new MerchantVersionModel();
$merchantModel->save(['type_module_array' => $moduleStr, 'shop_type_module_array' => $shopModuleStr],['merchant_versionid' => ['>', 0]]);
        return true;
    }

}
