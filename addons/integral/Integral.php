<?php
namespace addons\integral;
use addons\Addons as Addo;
use addons\coupontype\model\VslCouponTypeModel;
use addons\giftvoucher\model\VslGiftVoucherModel;
use addons\integral\model\VslIntegralCategoryModel;
use addons\integral\model\VslIntegralGoodsModel;
use addons\miniprogram\model\WeixinAuthModel;
use data\model\AddonsConfigModel;
use data\model\AlbumPictureModel;
use addons\integral\service\Integral AS integralServer;
use data\model\WebSiteModel;
use data\service\AddonsConfig;
use data\service\GoodsGroup as GoodsGroup;
use data\service\Express as Express;
use data\service\Goods as GoodsService;
use data\service\GoodsBrand as GoodsBrand;
use data\service\GoodsCategory as GoodsCategory;
use data\service\Album;

class Integral extends Addo
{
    public $info = array(
        'name' => 'integral',//插件名称标识
        'title' => '积分商城',//插件中文名
        'description' => '积分多元化兑换',//插件描述
        'status' => 1,//状态 1使用 0禁用
        'author' => 'vslaishop',// 作者
        'version' => '1.0',//版本号
        'has_addonslist' => 1,//是否有下级插件
        'content' => '',//插件的详细介绍或者使用方法
        'config_hook' => 'integralGoodsList',//
        'config_admin_hook' => 'integralGoodsList', //
        'logo' => 'https://pic.vslai.com.cn/upload/common/1554197061.png',
        'logo_small' => 'https://pic.vslai.com.cn/upload/common/1563782111.png',
        'logo_often' => 'https://pic.vslai.com.cn/upload/common/1563782234.png',
    );//设置文件单独的钩子

    public $menu_info = array(
        //platform
        [
            'module_name' => '积分商城',
            'parent_module_name' => '应用',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '积分多元化兑换',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'integralGoodsList',
            'module' => 'platform',
            'is_main' => 1
        ],
        [
            'module_name' => '商品列表',
            'parent_module_name' => '积分商城', //上级模块名称 确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 1, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '积分商城商品列表，目前支持“普通商品”、“优惠券”、“礼品券”、“余额”四种商品类型。', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'integralGoodsList',
            'module' => 'platform'
        ],
        [
            'module_name' => '发布商品',
            'parent_module_name' => '积分商城', //上级模块名称 确定上级目录
            'sort' => 0, //菜单排序
            'is_menu' => 0, // 是否为菜单
            'is_dev' => 0,  // 是否为开发者模式可见
            'desc' => '', //菜单描述
            'module_picture' => '', //图片，一般为空
            'icon_class' => '', //字体图标class
            'is_control_auth' => 1, //是否有控制权限
            'hook_name' => 'addIntegralGoods',
            'module' => 'platform'
        ],
        [
            'module_name' => '商品分类',
            'parent_module_name' => '积分商城',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 1,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '商品分类最多为一级，分类可关联的品类，品类将读取原先商城设置好的商品品类列表。',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'integralCategory',
            'module' => 'platform'
        ],
        [
            'module_name' => '添加积分分类',
            'parent_module_name' => '积分商城',//上级模块名称 用来确定上级目录
            'sort' => 0,//菜单排序
            'is_menu' => 0,//是否为菜单
            'is_dev' => 0,//是否是开发模式可见
            'desc' => '',//菜单描述
            'module_picture' => '',//图片（一般为空）
            'icon_class' => '',//字体图标class（一般为空）
            'is_control_auth' => 1,//是否有控制权限
            'hook_name' => 'addIntegralCategory',
            'module' => 'platform'
        ],
        [
            'module_name' => '商品回收站',
            'parent_module_name' => '积分商城', // 上级模块名称 用来确定上级目录
            'sort' => 2, //菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '商品回收站的商品一经删除即无法恢复，请谨慎操作。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'integralGoodsRecycle',
            'module' => 'platform',
        ],
        [
            'module_name' => '基础设置',
            'parent_module_name' => '积分商城', // 上级模块名称 用来确定上级目录
            'sort' => 3, //菜单排序
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '用于开启与关闭积分商城，关闭后积分商城将不能访问。', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'integralSetting',
            'module' => 'platform',
        ],

    ); // 钩子名称（需要该钩子调用的页面）

    public function __construct(){
        parent::__construct();
        $this->integralServer = new integralServer();
        $this->assign('website_id', $this->website_id);
        $this->assign('instance_id', $this->instance_id);
        $this->assign("pageshow", PAGESHOW);
        if ($this->module == 'platform') {
            $this->assign('addIntegralSetting', __URL(addons_url_platform('integral://Integral/addIntegralSetting')));
            $this->assign('addIntegralCategory', __URL(addons_url_platform('integral://Integral/addIntegralCategory')));
            $this->assign('changeIntegralCategorySort', __URL(addons_url_platform('integral://Integral/changeIntegralCategorySort')));
            $this->assign('deleteIntegralCategory', __URL(addons_url_platform('integral://Integral/deleteIntegralCategory')));
            $this->assign('changeIntegralCategoryName', __URL(addons_url_platform('integral://Integral/changeIntegralCategoryName')));
            $this->assign('modalIntegralGoodsList', __URL(addons_url_platform('integral://Integral/modalIntegralGoodsList')));
            $this->assign('modalIntegralCouponList', __URL(addons_url_platform('integral://Integral/modalIntegralCouponList')));
            $this->assign('modalIntegralGiftList', __URL(addons_url_platform('integral://Integral/modalIntegralGiftList')));
            $this->assign('integralGoodsCreateOrUpdate', __URL(addons_url_platform('integral://Integral/integralGoodsCreateOrUpdate')));
            $this->assign('refreshIntegralCate', __URL(addons_url_platform('integral://Integral/refreshIntegralCate')));
            $this->assign('selfIntegralgoodsList', __URL(addons_url_platform('integral://Integral/selfIntegralgoodsList')));
            $this->assign('getIntegralGoodsCount', __URL(addons_url_platform('integral://Integral/getIntegralGoodsCount')));
            $this->assign('modifyIntegralGoodsOutline', __URL(addons_url_platform('integral://Integral/modifyIntegralGoodsOutline')));
            $this->assign('modifyIntegralGoodsOnline', __URL(addons_url_platform('integral://Integral/modifyIntegralGoodsOnline')));
            $this->assign('integralQuiklyEdit', __URL(addons_url_platform('integral://Integral/integralQuiklyEdit')));
            $this->assign('deleteIntegralGoods', __URL(addons_url_platform('integral://Integral/deleteIntegralGoods')));
            $this->assign('integralGoodsRecycle', __URL(addons_url_platform('integral://Integral/integralGoodsRecycle')));
            $this->assign('confirmPinleiByCate', __URL(addons_url_platform('integral://Integral/confirmPinleiByCate')));
            $this->assign('isCardStock', __URL(addons_url_platform('integral://Integral/isCardStock')));
            $this->assign('refreshCate', __URL(addons_url_platform('integral://Integral/refreshIntegralCate')));
        }
    }
    /*
     * 商品列表
     * **/
    public function integralGoodsList()
    {
        $goods_group = new GoodsGroup();
        $groupList = $goods_group->getGoodsGroupList(1, 0, [
            'shop_id' => 0,
            'website_id' => $this->website_id,
            'pid' => 0
        ]);
        if (! empty($groupList['data'])) {
            foreach ($groupList['data'] as $k => $v) {
                $v['sub_list'] = $goods_group->getGoodsGroupList(1, 0, 'pid = ' . $v['group_id']);
            }
        }
        $type = request()->get('type','0');
        $this->assign("goods_group", $groupList['data']);
        $this->assign("type", $type);
        $search_info = request()->get('search_info', '');
        $this->assign("search_info", $search_info);
        // 查找一级商品分类
        $goodsCategory = new GoodsCategory();
        $oneGoodsCategory = $goodsCategory->getGoodsCategoryListByParentId(0);
        $this->assign("oneGoodsCategory", $oneGoodsCategory);
        // 上下架
        $state = request()->get("state", "");
        $this->assign("state", $state);
        // 库存预警
        $stock_warning = request()->get("stock_warning", 0);
        $this->assign("stock_warning", $stock_warning);
        //类型
        $type= $_REQUEST['type']?$_REQUEST['type']:'';
        $this->assign("type", $type);
        //判断pc端、小程序是否开启
        $addons_conf = new AddonsConfig();
        $pc_conf = $addons_conf->getAddonsConfig('pcport', $this->website_id);
        $is_minipro = getAddons('miniprogram', $this->website_id);
        if($is_minipro){
            $weixin_auth = new WeixinAuthModel();
            $new_auth_state = $weixin_auth->getInfo(['website_id' => $this->website_id], 'new_auth_state')['new_auth_state'];
            if(isset($new_auth_state) && $new_auth_state == 0){
                $is_minipro = 1;
            }else{
                $is_minipro = 0;
            }
        }
        $website_mdl = new WebSiteModel();
        //查看移动端的状态
        $wap_status = $website_mdl->getInfo(['website_id' => $this->website_id], 'wap_status')['wap_status'];
        $this->assign('wap_status', $wap_status);
        $this->assign('is_pc_use', $pc_conf['is_use']);
        $this->assign('is_minipro', $is_minipro);
        $this->assign("website_id", $this->website_id);
        $this->fetch('template/'.$this->module.'/integralGoodsList');
    }
    /*
     * 发布商品
     * **/
    public function addIntegralGoods()
    {
        $goods_group = new GoodsGroup();
        $express = new Express();
        $goods = new GoodsService();
        //判断优惠券、礼品券应用是否存在、关闭
        $coupon = getAddons('coupontype', $this->website_id);
        $giftvoucher = getAddons('giftvoucher', $this->website_id);
        $this->assign('coupon', $coupon);
        $this->assign('giftvoucher', $giftvoucher);
        $goodsId = isset($_REQUEST["goods_id"]) ? $_REQUEST["goods_id"] : 0;
        $origin_goods_id = isset($_REQUEST["origin_goods_id"]) ? $_REQUEST["origin_goods_id"] : 0;
        $integral_goods_id = isset($_REQUEST["integral_goods_id"]) ? $_REQUEST["integral_goods_id"] : 0;
        if($integral_goods_id){
            $this->assign('integral_goods_id', $integral_goods_id);
        }
        //查询优惠券、礼品券的id
        $integral_goods_mdl = new VslIntegralGoodsModel();
        $gift_coupon_list = $integral_goods_mdl->getInfo(['goods_id'=>$goodsId],'gift_voucher_id,coupon_type_id');
        //优惠券
        $coupon_type_id = isset($_REQUEST["coupon_type_id"]) ? $_REQUEST["coupon_type_id"] : $gift_coupon_list['coupon_type_id'];
        //礼品券id
        $gift_voucher_id = isset($_REQUEST["gift_voucher_id"]) ? $_REQUEST["gift_voucher_id"] : $gift_coupon_list['gift_voucher_id'];
        $goods_type = isset($_REQUEST["goods_type"]) ? $_REQUEST["goods_type"] : 0;
        $this->assign('goods_type', $goods_type);
        $groupList = $goods_group->getGoodsGroupList(1, 0, [
            'shop_id' => $this->instance_id,
            'website_id' => $this->website_id,
        ]);
//        p($groupList);
        $integral_cate_mdl = new VslIntegralCategoryModel();
        $category_list = $integral_cate_mdl->getQuery(['website_id' => $this->website_id],'*', 'integral_category_id desc');
        $category_list = $this->object2array($category_list);
//        p($category_list);
        $goods_attr_id = 0; // 商品类目关联id
//        var_dump($_COOKIE["goods_category_id"]);
        if (isset($_COOKIE["goods_category_id"])) {
            $this->assign("goods_category_id", $_COOKIE["goods_category_id"]);
            $name = str_replace(":", "&gt;", $_COOKIE["goods_category_name"]);
            $this->assign("goods_category_name", $name);
            $goods_attr_id = $_COOKIE["goods_attr_id"];
        } else {
            $this->assign("goods_category_id", 0); // 修改商品时，会进行查询赋值
            $this->assign("goods_category_name", "");
        }
        //print_r($this->object2array($goods_attr_id));exit;
        $this->assign("goods_attr_id", $goods_attr_id);
        $goods_attribute_list = $goods->getAttributeServiceList(1, 0,['website_id'=>$this->website_id,'is_use'=>1]);
        $this->assign("goods_attribute_list", $goods_attribute_list['data']); // 商品类型
        $shi_condition['website_id'] = $this->website_id;
        $shi_condition['shop_id'] = 0;
        $shi_condition['is_enabled'] = 1;
        $this->assign("shipping_list", $express->shippingFeeQuery($shi_condition)); // 物流
        $this->assign("group_list", $groupList['data']); // 分组
        $this->assign("goods_id", $goodsId);
        $this->assign("shop_type", 2);
        $this->assign("category_list",$category_list);
        // 相册列表
        $album = new Album();
        $album_list = $album->getAlbumClassAll([
            'shop_id' => $this->instance_id,
            'website_id' => $this->website_id
        ]);
        $this->assign('album_list', $album_list);
        if ($coupon_type_id > 0) {
            //获取优惠券的信息
            $coupon_type = new VslCouponTypeModel();
            $coupon_list = $coupon_type->getInfo(['coupon_type_id'=>$coupon_type_id],'*');
            $this->assign('coupon_list', $coupon_list);
            $this->assign('coupon_type_id', $coupon_type_id);
        }
        if ($gift_voucher_id > 0) {
            //获取优惠券的信息
            $gift_mdl = new VslGiftVoucherModel();
            $gift_list = $gift_mdl->getInfo(['gift_voucher_id'=>$gift_voucher_id],'*');
            $this->assign('gift_list', $gift_list);
            $this->assign('gift_voucher_id', $gift_voucher_id);
        }
        if ($goodsId > 0 || $origin_goods_id > 0 || $coupon_type_id > 0 || $gift_voucher_id > 0) {
            if($goodsId > 0){
                $this->assign("goodsid", $goodsId);
                $goods_info = $this->integralServer->getIntegralGoodsDetail($goodsId);
            }elseif( $origin_goods_id > 0 ){
                $goods_info = $goods->getGoodsDetail($origin_goods_id);
                //将原来的商品sku去掉
                $goods_info['sku_picture_array'] = [];
//                $goods_info['img_temp_array'] = [];
                $goods_info['sku_list'] = [];
                $goods_info['limit_num'] = 0;
                $goods_info['day_num'] = 0;
                $goods_info->balance = 0;
            }

            $goods_info['sku_list'] = json_encode($goods_info['sku_list']);
            $goods_info['goods_group_list'] = json_encode($goods_info['goods_group_list']);
            $goods_info['img_list'] = json_encode($goods_info['img_list']);
            $goods_info['goods_attribute_list'] = json_encode($goods_info['goods_attribute_list']);
            if ($goods_info["group_id_array"] == "") {
                $this->assign("edit_group_array", array());
            } else {
                $this->assign("edit_group_array", explode(",", $goods_info["group_id_array"]));
            }
            /**
             * 当前cookie中存的goodsid
             */
            $update_goods_id = isset($_COOKIE["goods_update_goodsid"]) ? $_COOKIE["goods_update_goodsid"] : 0;
            if ($update_goods_id == $goodsId) {
                // $category_name = str_replace(":", "&gt;", $_COOKIE["goods_category_name"]);
                $categroy_name = str_replace(":", "", $_COOKIE["goods_category_name"]);
                $goods_info["category_id"] = $_COOKIE["goods_category_id"];
                $goods_info["category_name"] = $categroy_name;
            }
            //print_r($this->object2array($goods_info));exit;
            $this->assign("category_name_1", $goods_info['category_name_1']);
            $this->assign("category_name_2", $goods_info['category_name_2']);
            $this->assign("category_name_3", $goods_info['category_name_3']);
            if($goods_info['distribution_rule_val']){
                $goods_info['distribution_rule_val'] = json_decode(htmlspecialchars_decode($goods_info['distribution_rule_val']),true);
            }
            if($goods_info['bonus_rule_val']){
                $goods_info['bonus_rule_val'] = json_decode(htmlspecialchars_decode($goods_info['bonus_rule_val']),true);
            }
            $this->assign("goods_info", $goods_info);
            $this->fetch('template/' . $this->module . "/updateIntegralGoods");
        } else {
            $this->fetch('template/' . $this->module . '/addIntegralGoods');
        }
    }

    /*
     * 商品分类列表
     * **/
    public function integralCategory()
    {
        //获取分类列表
        $integral_cate_list = $this->integralServer->getIntegralCategoryList(['website_id'=>$this->website_id]);
        $this->assign('integral_cate_list',$integral_cate_list);
        $this->fetch('template/'.$this->module.'/integralCategoryList');
    }

    /*
     * 添加分类
     * **/
    public function addIntegralCategory()
    {
        $integral_category_id = request()->get('category_id',0);
        if($integral_category_id){
            $condition['integral_category_id'] = $integral_category_id;
            $integral_cate_list = $this->integralServer->getIntegralCategoryList($condition);
            $integral_cate_list[0]['category_pic'] = getApiSrc($integral_cate_list[0]['category_pic']);
            $integral_cate_list = objToArr($integral_cate_list);
            $this->assign('integral_cate_list', $integral_cate_list[0]);
        }
        $goods = new GoodsService();
        $goodsAttributeList = $goods->getAttributeServiceList(1, 0,['website_id'=>$this->website_id]);
        $this->assign("goodsAttributeList", $goodsAttributeList['data']);
        $this->fetch('template/'.$this->module.'/addIntegralCategory');
    }
    /*
     * 积分基础设置
     * **/
    public function integralSetting()
    {
        //查出设置信息
        $addons_config_model = new AddonsConfigModel();
        $addons_info = $addons_config_model::get(['website_id' => $this->website_id, 'addons' => 'integral']);
        $addons_data['is_use'] = $addons_info['is_use'] ?: 0;
        $this->assign('addons_data', $addons_data);
        $this->fetch('template/'.$this->module.'/integralSetting');
    }
    /*
     * 商品回收站
     * **/
    public function integralGoodsRecycle()
    {
        $search_info = request()->post('search_info', '');
        $this->assign('regainIntegralgoodsdeleted', __URL(call_user_func('addons_url_'.$this->module,'integral://Integral/regainIntegralgoodsdeleted')));
        $this->assign('emptyDeleteIntegralGoods', __URL(call_user_func('addons_url_'.$this->module,'integral://Integral/emptyDeleteIntegralGoods')));
        $this->assign("search_info", $search_info);
        // 查找一级商品分类
//        $goodsCategory = new VslIntegralCategoryModel();
//        $oneGoodsCategory = $goodsCategory->getInfo('','*');
//        $this->assign("oneGoodsCategory", $oneGoodsCategory);
        $this->fetch('template/'.$this->module.'/integralRecycleList');
    }


    public function run(){

    }
    /*
     * 安装方法
     */
    public function install()
    {
        // TODO: Implement install() method.

        return true;
    }

    /*
     * 卸载方法
     */
    public function uninstall()
    {

        return true;
        // TODO: Implement uninstall() method.
    }

}