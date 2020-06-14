<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/18 0018
 * Time: 17:37
 */

namespace addons\groupshopping\controller;

use addons\groupshopping\Groupshopping as baseGroupShopping;
use data\model\AddonsConfigModel;
use data\service\Goods;
use addons\groupshopping\server\GroupShopping as groupShoppingServer;
use data\service\AddonsConfig;
use data\service\User;
use think\Validate;

class Groupshopping extends baseGroupShopping
{
    public function __construct()
    {
        parent::__construct();
    }

    public function saveGroupShoppingSetting()
    {
        $addonsConfigSer = new AddonsConfig();
        $post_data = request()->post();
        $is_group_shopping = $post_data['is_group_shopping'];
        unset($post_data['is_group_shopping']);
        $post_data['value'] = $post_data;
        $post_data['addons'] = parent::$addons_name;
        $post_data['desc'] = '拼团设置';
        $post_data['is_use'] = $is_group_shopping;
        if(!$is_group_shopping){
            $groupServer = new groupShoppingServer();
            $checkGroupStatus = $groupServer->checkGroupStatus();
            if($checkGroupStatus){
                return AjaxReturn(-10015);
            }
        }
        $res = $addonsConfigSer->setAddonsConfig($post_data);
        if(!$res){
            return AjaxReturn(0);
        }
        setAddons('groupshopping', $this->website_id, $this->instance_id);
        setAddons('groupshopping', $this->website_id, $this->instance_id, true);
        $this->addUserLog('拼团设置',$res);
        return AjaxReturn($res);
    }

    public function groupShoppingList()
    {
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post("page_size", PAGESIZE);
        $search_text = request()->post("search_text",'');
        $groupType = request()->post("group_type",'all');
        $groupServer = new groupShoppingServer();
        $condition['ng.website_id'] = $this->website_id;
        $condition['ng.shop_id'] = $this->instance_id;
        if ($search_text) {
            $condition['vg.goods_name'] = ['like', "%" . $search_text . "%"];
        }

        if($groupType!='all'){
            $condition['ng.status'] = $groupType;
        }
        $list = $groupServer->groupShoppingList($page_index, $page_size, $condition);
        return $list;
    }
    /**
     * 拼团商品选择
     */
    public function modalGroupShoppingGoodsList()
    {
        if (request()->post('page_index')) {
            $index = request()->post('page_index', 1);
            $search_text = request()->post('search_text');
            if ($search_text) {
                $condition['ng.goods_name'] = ['LIKE', '%' . $search_text . '%'];
            }
            $condition['ng.website_id'] = $this->website_id;
            $condition['ng.shop_id'] = $this->instance_id;
            $condition['ng.state'] = 1;
            $condition['ng.goods_type'] = ['<>',4];
            $goods = new Goods();
            $list = $goods->getModalGoodsList($index, $condition);
            return $list;
        }
        $this->fetch('template/' . $this->module . '/groupShoppingGoodsDialog');
       
    }
    
    /*
     * 添加拼团活动及其商品
     * **/
    public function addGroupShopping()
    {
        $groupServer = new groupShoppingServer();
        //验证group_shopping表
        $validate = new Validate([
            'group_name'  =>  'require',
            'group_time' =>  'require',
            'group_num' =>  'require',
            'goods_id' =>  'require',
        ]);
        if( !$validate->check(request()->post()) ){
            return ['code'=>0,'message'=>$validate->getError()];
        }
        //验证验证group_shopping_goods表
        $sku_goods = [];
        $goods_info = request()->post('goods_info/a',array());
        if(!$goods_info){
            return ['code'=>0,'message'=>'请选择商品'];
        }
        foreach($goods_info as $sku_id=>$goods){
            $sku_goods['group_price'] = $goods['group_price'];
            if(empty($sku_goods['group_price'])){
                return ['code'=>0,'message'=>'商品所有规格的活动价格不能为空并且须大于0'];
            }
        }
        if(request()->post('group_id',0)){
            $ret_val = $groupServer->updateGroupShopping(request()->post());
        }else{
            $ret_val = $groupServer->addGroupShopping(request()->post());
        }
        
        
        if($ret_val <= 0){
            return AjaxReturn($ret_val);
        }
        $this->addUserLog('添加拼团活动及其商品', $ret_val);
        return AjaxReturn(1);
    }
    
    /*
     * 编辑拼团获取规格
     */
    public function getSkuList(){
        $goodsId = request()->post('goods_id',0);
        $group_id = request()->post('group_id',0);
        $groupServer = new groupShoppingServer();
        $skuList = $groupServer->groupShoppingSku($goodsId,$group_id);
        return $skuList;
    }
    /*
     * 编辑拼团获取规格
     */
    public function getGroupRecordList(){
        $pageIndex = request()->post("page_index", 1);
        $pageSize = request()->post("page_size", PAGESIZE);
        $searchText = request()->post("search_text",'');
        $recordStatus = request()->post("record_status",1);
        $startDate = request()->post("startDate",'');
        $endDate = request()->post("endDate",'');
        $group_id = request()->post("group_id",'');
        $groupServer = new groupShoppingServer();
        $condition = array(
            'ng.website_id' => $this->website_id,
            'ng.shop_id' => $this->instance_id,
            'ng.status' => $recordStatus,
            'ng.group_id' => $group_id
        );
        if($searchText){
            $condition['ng.record_no'] = $searchText;
        }
        if ($startDate != "") {
                $condition["create_time"][] = [
                    ">",
                    getTimeTurnTimeStamp($startDate)
                ];
        }
        if ($endDate != "") {
            $condition["create_time"][] = [
                "<",
                getTimeTurnTimeStamp($endDate)
            ];
        }
        $list = $groupServer->getGroupRecordViewList($pageIndex, $pageSize, $condition);
        return $list;
    }
    /*
     * 获取每个团会员列表
     */
    public function getGroupMemberList(){
        $pageIndex = request()->post("page_index", 1);
        $pageSize = request()->post("page_size", PAGESIZE);
        $record_id = request()->post("record_id",'');
        $buyer_id = request()->post("buyer_id",'');//团长id
        $groupServer = new groupShoppingServer();
        $condition = array(
            'website_id' => $this->website_id,
            'shop_id' => $this->instance_id,
            'group_record_id' => $record_id
        );
        $list = $groupServer->getGroupMemberList($pageIndex, $pageSize, $condition,$buyer_id);
        return $list;
    }
    /*
     * 开启拼团活动
     */
    public function groupShoppingOn(){
        $group_id = request()->post("group_id",0);
        if(!$group_id){
            return AjaxReturn(0);
        }
        $groupServer = new groupShoppingServer();
        $retval = $groupServer->groupShoppingOn($group_id);
        if($retval <= 0){
            return AjaxReturn($retval);
        }
        $this->addUserLog('开启拼团活动', $retval);
        return AjaxReturn(1);
    }
    /*
     * 关闭拼团活动
     */
    public function groupShoppingOff(){
        $group_id = request()->post("group_id",0);
        if(!$group_id){
            return AjaxReturn(0);
        }
        $groupServer = new groupShoppingServer();
        $retval = $groupServer->groupShoppingOff($group_id);
        if($retval <= 0){
            return AjaxReturn($retval);
        }
        $this->addUserLog('关闭拼团活动', $retval);
        return AjaxReturn(1);
    }
    /*
     * 删除拼团活动
     */
    public function groupShoppingDelete(){
        $group_id = request()->post("group_id",0);
        if(!$group_id){
            return AjaxReturn(0);
        }
        $groupServer = new groupShoppingServer();
        $retval = $groupServer->deleteGroup($group_id);
        if($retval <= 0){
            return AjaxReturn($retval);
        }
        $this->addUserLog('删除拼团活动', $retval);
        return AjaxReturn(1);
    }
    /**
     * wap 商品详情、拼团列表接口
     */
    public function goodsGroupRecordListForWap()
    {
        $groupServer = new groupShoppingServer();
        $goods_id = request()->post('goods_id');
        $num = request()->post('num',0);//查询数量,0代表全部
        if (empty($goods_id)){
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        $goodsGroupRecordList = $groupServer->goodsGroupRecordListForWap($goods_id,$num);
        return json(['code' => 1, 'message' => '获取成功', 'data' => $goodsGroupRecordList]);
    }
    /**
     * wap 商品详情、拼团人数接口
     */
    public function goodsGroupRecordCount()
    {
        $groupServer = new groupShoppingServer();
        $goods_id = request()->post('goods_id');
        if (empty($goods_id)){
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        $groupRecordCount = $groupServer->goodsGroupRecordCount($goods_id);
        return json(['code' => 1, 'message' => '获取成功', 'data' => $groupRecordCount]);
    }
    /**
     * wap 拼团列表接口
     */
    public function groupShoppingListForWap()
    {
        $groupServer = new groupShoppingServer();
        $pageIndex = request()->post('page_index',1);
        $pageSize = request()->post('page_size',PAGESIZE);
        $shop_id = request()->post('shop_id');
        // by sgw
        if (isset($shop_id)) {
            $condition['ng.shop_id'] = $shop_id;
        }
        $condition['ng.website_id'] = $this->website_id;
        $condition['ng.status'] = 1;
        $condition['vg.goods_id'] = [">", 0];
        // 获取该用户的权限
        if($this->uid) {
            $userService = new User();
            $userLevle = $userService->getUserLevelAndGroupLevel($this->uid);// code | <0 错误; 1系统会员; 2;分销商; 3会员
            if (!empty($userLevle)) {
            $sql1 = '';
            $sql2 = '(';
            // 会员权限
            if ($userLevle['user_level']) {
                $u_id = $userLevle['user_level'];
                $sql1 .= "instr(CONCAT( ',', vgd.browse_auth_u, ',' ), ',".$u_id.",' ) OR ";
                $sql2 .= "vgd.browse_auth_u IS NULL OR vgd.browse_auth_u = '' ";
            }
            // 分销商权限
            if ($userLevle['distributor_level']) {
                $d_id = $userLevle['distributor_level'];
                $sql1 .= "instr(CONCAT( ',', vgd.browse_auth_d, ',' ), ',".$d_id.",' ) OR ";
                    $sql2 .= " OR vgd.browse_auth_d IS NULL OR vgd.browse_auth_d = '' ";
            }
            // 标签权限
            if ($userLevle['member_group']) {
                $g_ids = explode(',',$userLevle['member_group']);
                foreach ($g_ids as $g_id) {
                    $sql1 .= "instr(CONCAT( ',', vgd.browse_auth_s, ',' ), ',".$g_id.",' ) OR ";
                        $sql2 .= " OR vgd.browse_auth_s IS NULL OR vgd.browse_auth_s = '' ";
            }
            } else {
                $sql1 .= "  ";
            }
            $sql2 .= " )";
            $condition[] = ['exp', $sql1 . $sql2];
            }
        }

        $groupShoppingList = $groupServer->groupShoppingList($pageIndex,$pageSize,$condition);
        foreach($groupShoppingList['data'] as $key => $val){
            $groupShoppingList['data'][$key]['pic_cover'] = $val['pic_cover'] ? getApiSrc($val['pic_cover']) : '';
            $groupShoppingList['data'][$key]['pic_cover_mid'] = $val['pic_cover_mid'] ? getApiSrc($val['pic_cover_mid']) : '';
        }
        $groupShoppingList['group_shopping_list'] = $groupShoppingList['data'];
        unset($groupShoppingList['data']);
        return json(['code' => 1, 'message' => '获取成功', 'data' => $groupShoppingList]);
    }
    /**
     * wap 查询是否能参团
     */
    public function checkGroupIsCan()
    {
        $groupServer = new groupShoppingServer();
        $recordId = request()->post('record_id',0);
        if (!$recordId){
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        $result = $groupServer->checkGroupIsCan($recordId);
        if($result < 0){
            return json(AjaxReturn($result));
        }
        return json(['code' => 1, 'message' => '获取成功', 'data' => ['canJoinGroup' => true]]);
    }
    
     /*
     * 拼团支付成功获取团购详情
     */
    public function getGroupMemberListForWap(){
        $record_id = request()->post("record_id",0);
        if (!$record_id){
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }
        $groupServer = new groupShoppingServer();
        $list = $groupServer->getGroupMemberListForWap($record_id);
        if(!$list){
            return json(AjaxReturn(0));
        }
        return json(['code' => 1, 'message' => '获取成功', 'data' => $list]);
    }
}