<?php
namespace app\shop\controller;
use addons\shop\model\VslShopModel;
use think\Controller;
use addons\shop\service\Shop as ShopService;
use data\extend\custom\Common;
use data\service\Member;
/**
 * 菜单
 */
class Menu extends BaseController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function addonmenu()
    {
        $addons = request()->param('addons'); // 插件名称
        if($addons=='couponList' || $addons=='shopCollectionList' || $addons=='distributionIndex'){
            $uid = $this->user->getSessionUid();
            if (empty($uid)) {
                $redirect = __URL( __URL__ . "/login");
                $this->redirect($redirect);
            }
        }
        $shop_id = (int)request()->param('shop_id',-1); // 插件名称
        if($this->shopStatus && $shop_id){
            $shop = new VslShopModel();
            $shop_name = $shop->getInfo(['shop_id'=>$shop_id,'website_id'=>$this->website_id],'shop_name')['shop_name'];
        }else{
            $shop_name = '自营店';
        }
        $params = request()->param(); // 插件参数
        $this->assign('params', $params);
        $this->assign('hook_name', $addons);
        $this->assign('shop_id', $shop_id);
        $this->assign('uid', $this->uid);
        if($shop_id >=0){
            $shop = new ShopService();
            $com = new Common($shop_id, $this->website_id);
            $dir_shop_common = ROOT_PATH . 'public/static/custompc/data/web_'.$this->website_id.'/shop_'.$shop_id.'/common';
            $shopBanner = $com->get_html_file($dir_shop_common . '/shopbanner_html.php');
            $this->assign('shopBanner', $shopBanner);
            $shop_info = $shop->getShopDetail($params['shop_id']);
            $this->assign('shop_info', $shop_info);
            $ntype = 'shop';
            $navigator_list = $com->get_navigator($ntype);
            $this->assign('navigator_list', $navigator_list);
            $this->assign('ntype', $ntype);
             // 当前用户是否收藏了该店铺
            $member = new Member();
            $is_member_fav_shop = $member->getIsMemberFavorites($this->uid, $shop_id, 'shop');
            $this->assign("is_member_fav_shop", $is_member_fav_shop);
        }
        if($addons=='shopCollectionList'){
            $this->assign("title_before", '店铺收藏');
            $this->assign('hook_name', 'shopCollectionList');
        }
        if($addons=='couponList'){
            $this->assign("title_before", '我的优惠券');
        }
        if($addons=='distributionIndex'){
            $this->assign("title_before", '分销中心');
        }
        if($addons=='shopStreet'){
            $this->assign("title_before", '店铺街');
        }
        if($addons=='shopIndex'){
            $this->assign("title_before", $shop_name);
        }
        if($addons=='distributionIndex' || $addons=='couponList' || $addons=='shopCollectionList'){
            return view($this->style . 'Menu/addonmenu1');
        }else{
            return view($this->style . 'Menu/addonmenu');
        }
    }
}