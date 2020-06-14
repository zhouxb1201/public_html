<?php
namespace data\service;
use addons\shop\model\VslShopWithdrawModel;
use data\model\AlbumPictureModel as AlbumPictureModel;
use data\model\VslAccountRecordsModel;
use addons\shop\model\VslShopModel;
use addons\shop\model\VslShopAccountRecordsModel;
use data\model\VslGoodsGroupModel;
use data\model\VslGoodsViewModel;
use data\model\VslNoticeModel;
use think\Db;

/**
 * 
 */
class Platform extends BaseService
{


    /* (non-PHPdoc)
    * @see \data\api\IPlatform::getFinanceCount()
    */
    public function getFinanceCount($start_date,$end_date)
    {
        $start_date = strtotime($start_date);//date('Y-m-d 00:00:00', time());
        $end_date = strtotime($end_date);//date('Y-m-d 00:00:00', strtotime('this day + 1 day'));
        $end_date = $end_date+24*3600-1;
        $account_info = array();
        $account_records = new VslAccountRecordsModel();
        //平台入账总金额(订单支付和充值)
        $condition4 = ['create_time' => [[">",$start_date],["<",$end_date]],'website_id'=>$this->website_id,'account_type'=>12];
        $account_info['wx_balance_entry'] = $account_records->getSum($condition4,'money');//余额微信充值
        $condition30 = ['create_time' => [[">",$start_date],["<",$end_date]],'website_id'=>$this->website_id,'account_type'=>13];
        $account_info['ali_balance_entry'] = $account_records->getSum($condition30,'money');//余额支付宝充值
        $condition32 = ['create_time' => [[">",$start_date],["<",$end_date]],'website_id'=>$this->website_id,'account_type'=>47];
        $account_info['gp_balance_entry'] = $account_records->getSum($condition32,'money');//余额Globepay充值
        $account_info['balance_entry'] = $account_info['wx_balance_entry'] + $account_info['ali_balance_entry'] + $account_info['gp_balance_entry'];//余额充值       
        //平台成交额(订单支付)
        $condition1 = ['create_time' => [[">",$start_date],["<",$end_date]],'website_id'=>$this->website_id,'account_type'=>14];
        $account_info['balance_payment'] = $account_records->getSum($condition1,'money');//余额支付
        $condition2 = ['create_time' => [[">",$start_date],["<",$end_date]],'website_id'=>$this->website_id,'account_type'=>15];
        $account_info['wx_payment'] = $account_records->getSum($condition2,'money');//微信支付
        $condition3 = ['create_time' => [[">",$start_date],["<",$end_date]],'website_id'=>$this->website_id,'account_type'=>16];
        $account_info['ali_payment'] = $account_records->getSum($condition3,'money');//支付宝支付
        $condition31 = ['create_time' => [[">",$start_date],["<",$end_date]],'website_id'=>$this->website_id,'account_type'=>46];
        $account_info['gp_payment'] = $account_records->getSum($condition31,'money');//GlobePay支付
        $account_info['platform_trunover'] = $account_info['balance_payment']+$account_info['wx_payment']+$account_info['ali_payment']+$account_info['gp_payment'];        
        $account_info['wx_payments'] = $account_info['wx_payment']+$account_info['wx_balance_entry'];//微信入账
        $account_info['ali_payments'] = $account_info['ali_payment']+$account_info['ali_balance_entry'];//支付宝入账
		$account_info['gp_payments'] = $account_info['gp_payment']+$account_info['gp_balance_entry'];//GlobePay入账
        $account_info['account_entry'] = $account_info['wx_payment']+$account_info['ali_payment']+$account_info['balance_entry'];
        //自营成交额
        $condition5 = ['create_time' => [[">",$start_date],["<",$end_date]],'website_id'=>$this->website_id,'shop_id'=>0,'account_type'=>14];
        $account_info['self_balance_payment'] = $account_records->getSum($condition5,'money');//自营余额支付
        $condition6 = ['create_time' => [[">",$start_date],["<",$end_date]],'website_id'=>$this->website_id,'shop_id'=>0,'account_type'=>15];
        $account_info['self_wx_payment'] = $account_records->getSum($condition6,'money');//自营微信支付
        $condition7 = ['create_time' => [[">",$start_date],["<",$end_date]],'website_id'=>$this->website_id,'shop_id'=>0,'account_type'=>16];
        $account_info['self_ali_payment'] = $account_records->getSum($condition7,'money');//自营支付宝支付
        $account_info['self_trunover'] = $account_info['self_balance_payment']+$account_info['self_wx_payment']+$account_info['self_ali_payment'];
        //后台余额调整
        $condition8 = ['create_time' => [[">",$start_date],["<",$end_date]],'website_id'=>$this->website_id,'account_type'=>11];
        $account_info['balance_adjust'] = $account_records->getSum($condition8,'money');
        //余额提现金额
        $condition9 = ['create_time' => [[">",$start_date],["<",$end_date]],'website_id'=>$this->website_id,'account_type'=>7];
        $account_info['wx_balance_withdraw'] =$account_records->getSum($condition9,'money');//余额微信提现
        $condition10 = ['create_time' => [[">",$start_date],["<",$end_date]],'website_id'=>$this->website_id,'account_type'=>8];
        $account_info['ali_balance_withdraw'] =$account_records->getSum($condition10,'money');//余额支付宝提现
        $condition11 = ['create_time' => [[">",$start_date],["<",$end_date]],'website_id'=>$this->website_id,'account_type'=>36];
        $account_info['bank_balance_withdraw'] =$account_records->getSum($condition11,'money');//余额银行卡提现
        $account_info['balance_withdraw'] = $account_info['wx_balance_withdraw']+$account_info['ali_balance_withdraw']+$account_info['bank_balance_withdraw'];
        //佣金提现金额
        $condition12 = ['create_time' => [[">",$start_date],["<",$end_date]],'website_id'=>$this->website_id,'account_type'=>1];
        $account_info['wx_commission_withdraw'] =$account_records->getSum($condition12,'money');//佣金微信提现
        $condition13 = ['create_time' => [[">",$start_date],["<",$end_date]],'website_id'=>$this->website_id,'account_type'=>2];
        $account_info['ali_commission_withdraw'] =$account_records->getSum($condition13,'money');//佣金支付宝提现
        $condition14 = ['create_time' => [[">",$start_date],["<",$end_date]],'website_id'=>$this->website_id,'account_type'=>38];
        $account_info['bank_commission_withdraw'] =$account_records->getSum($condition14,'money');//佣金银行卡提现
        $account_info['commission_withdraw'] = $account_info['wx_commission_withdraw']+$account_info['ali_commission_withdraw']+$account_info['bank_commission_withdraw'];
        //店铺提现金额
        $condition15 = ['create_time' => [[">",$start_date],["<",$end_date]],'website_id'=>$this->website_id,'account_type'=>19];
        $account_info['wx_shop_withdraw'] =$account_records->getSum($condition15,'money');//店铺微信提现
        $condition16 = ['create_time' => [[">",$start_date],["<",$end_date]],'website_id'=>$this->website_id,'account_type'=>20];
        $account_info['ali_shop_withdraw'] =$account_records->getSum($condition16,'money');//店铺支付宝提现
        $condition17 = ['create_time' => [[">",$start_date],["<",$end_date]],'website_id'=>$this->website_id,'account_type'=>40];
        $account_info['bank_shop_withdraw'] =$account_records->getSum($condition17,'money');//店铺银行卡提现
        $account_info['shop_withdraw'] = $account_info['wx_shop_withdraw']+$account_info['ali_shop_withdraw']+$account_info['bank_shop_withdraw'];
        //收益提现金额
        $condition26 = ['create_time' => [[">",$start_date],["<",$end_date]],'website_id'=>$this->website_id,'account_type'=>28];
        $account_info['wx_microshop_withdraw'] =$account_records->getSum($condition26,'money');//收益微信提现
        $condition27 = ['create_time' => [[">",$start_date],["<",$end_date]],'website_id'=>$this->website_id,'account_type'=>29];
        $account_info['ali_microshop_withdraw'] =$account_records->getSum($condition27,'money');//收益支付宝提现
        $condition28 = ['create_time' => [[">",$start_date],["<",$end_date]],'website_id'=>$this->website_id,'account_type'=>37];
        $account_info['bank_microshop_withdraw'] =$account_records->getSum($condition28,'money');//收益银行卡提现
        $account_info['microshop_withdraw'] = $account_info['wx_microshop_withdraw']+$account_info['ali_microshop_withdraw']+$account_info['bank_microshop_withdraw'];
        //微信提现
        $account_info['wx_withdraw'] = $account_info['wx_balance_withdraw']+$account_info['wx_commission_withdraw']+$account_info['wx_shop_withdraw']+$account_info['wx_microshop_withdraw'];
        //支付宝提现
        $account_info['ali_withdraw'] = $account_info['ali_balance_withdraw']+$account_info['ali_commission_withdraw']+$account_info['ali_shop_withdraw']+$account_info['ali_microshop_withdraw'];
        //银行卡提现
        $account_info['bank_withdraw'] = $account_info['bank_balance_withdraw']+$account_info['bank_commission_withdraw']+$account_info['bank_shop_withdraw']+$account_info['bank_microshop_withdraw'];
        //提现总金额
        $account_info['account_withdrawals'] = $account_info['balance_withdraw']+$account_info['shop_withdraw']+$account_info['commission_withdraw']+$account_info['microshop_withdraw'];
        //赠送佣金
        $condition18 = ['create_time' => [[">",$start_date],["<",$end_date]],'website_id'=>$this->website_id,'account_type'=>5];
        $account_info['commission_total'] =$account_records->getSum($condition18,'money');
        //赠送收益
        $condition29 = ['create_time' => [[">",$start_date],["<",$end_date]],'website_id'=>$this->website_id,'account_type'=>39];
        $account_info['microshop_total'] =$account_records->getSum($condition29,'money');
        //赠送分红
        $condition19 = ['create_time' => [[">",$start_date],["<",$end_date]],'website_id'=>$this->website_id,'account_type'=>22];
        $account_info['bonus_total'] =$account_records->getSum($condition19,'money');
        //平台优惠
        $condition20 = ['create_time' => [[">",$start_date],["<",$end_date]],'website_id'=>$this->website_id,'account_type'=>23];
        $account_info['platform_preference'] =$account_records->getSum($condition20,'money');
        //平台利润
        $condition25 = ['create_time' => [[">",$start_date],["<",$end_date]],'website_id'=>$this->website_id,'account_type'=>41];
        $account_info['platform_profit'] =$account_records->getSum($condition25,'money');
        //个人所得税
        $condition21 = ['create_time' => [[">",$start_date],["<",$end_date]],'website_id'=>$this->website_id,'account_type'=>['in','24,27']];
        $account_info['income_tax_total'] =$account_records->getSum($condition21,'money');
        //手续税
        $condition22 = ['create_time' => [[">",$start_date],["<",$end_date]],'website_id'=>$this->website_id,'account_type'=>25];
        $account_info['service_charge_total'] =$account_records->getSum($condition22,'money');
        //积分抵扣
        $condition23 = ['create_time' => [[">",$start_date],["<",$end_date]],'website_id'=>$this->website_id,'account_type'=>33];
        $account_info['point_discount_total'] =$account_records->getSum($condition23,'money');
        //订单退款
        $condition24 = ['create_time' => [[">",$start_date],["<",$end_date]],'website_id'=>$this->website_id,'account_type'=>18];
        $account_info['refund_total'] = abs($account_records->getSum($condition24,'money'));
        //店铺待结算金额
        if(getAddons('shop', $this->website_id)){
            $shop_account = new VslShopWithdrawModel();
            $condition24 = ['ask_for_date' => [[">",$start_date],["<",$end_date]],'website_id'=>$this->website_id,'status'=>3];
            $account_info['shop_withdraw3'] =$shop_account->getSum($condition24,'cash');//已打款
            $shop_account_records = new VslAccountRecordsModel();
            $condition23 = ['create_time' => [[">",$start_date],["<",$end_date]],'website_id'=>$this->website_id,'account_type'=>50];
            $account_info['shop_total'] =$shop_account_records->getSum($condition23,'money');//入账金额
            $account_info['shop_preference'] =$account_info['shop_total']-$account_info['shop_withdraw3'];
        }
        return $account_info;
    }
    public function  getPlatformAccountMonthRecord(){
        $begin = getTimeTurnTimeStamp(date('Y-m-01', strtotime(date("Y-m-d"))));
        $end = getTimeTurnTimeStamp(date('Y-m-d', strtotime("$begin +1 month -1 day")));
        $account_records = new VslAccountRecordsModel();
        $condition["create_time"] = [
            [
                ">",
                $begin
            ],
            [
                "<",
                $end
            ]
        ];
        $condition["website_id"] = $this->website_id;
        $account_records_list = $account_records->all($condition);
        $begintime = strtotime($begin);
        $endtime = strtotime($end);
        $list= array();
        for ($start = $begintime; $start <= $endtime; $start += 24 * 3600) {
            $list[date("d",$start)] =array();
            $money = 0;
            foreach($account_records_list as $v){
                if(getTimeTurnTimeStamp(date("Y-m-d",$v["create_time"]))== getTimeTurnTimeStamp(date("Y-m-d",$start)) ){
                    $money = $money +$v["money"];                    
                }
            }
            $list[date("d",$start)]["money"] = $money;           
        }
        return $list;
    }
	/* (non-PHPdoc)
     * @see \data\api\IPlatform::getPlatformAccountRecordsList()
     */
    public function getPlatformAccountRecordsList($page_index, $page_size = 0, $condition = '', $order = '')
    {
        // TODO Auto-generated method stub
        $account_records = new VslAccountRecordsModel();
        $list = $account_records->pageQuery($page_index, $page_size, $condition, $order, '*');
        foreach($list["data"] as  $k=>$v){
            $shop = new VslShopModel();
            $shop_info = $shop->getInfo(["shop_id"=>$v["shop_id"],"website_id"=>$this->website_id],"*");
            $shop_name = $shop_info["shop_name"];
            $list["data"][$k]["shop_name"] =$shop_name;
        }       
        return $list;
    }


	/* (non-PHPdoc)
     * @see \data\api\IPlatform::setGoodsRecommend()
     */
    public function setGoodsGroupRecommend($shop_id, $group_id_array, $is_show)
    {
        // TODO Auto-generated method stub
        $platform_goods_group_recommend = new VslPlatformGoodsGroupRecommendModel();
        $data = array(
            "is_show"=>$is_show,
            "group_id_array"=>$group_id_array,
            "modify_time"=>time()
        );
        $retval = $platform_goods_group_recommend->save($data, ["shop_id"=>$shop_id]);
        return $retval;    
    }
	/* (non-PHPdoc)
     * @see \data\api\IPlatform::getRecommendGoodsQuery()
     */
    public function getRecommendGoodsQuery($shop_id)
    {
        // TODO Auto-generated method stub
        $group = new VslGoodsGroupModel();
        $goods = new VslGoodsViewModel();
        $group_goods_list = array();
    
        $group_list = $group->getQuery(["shop_id"=>$shop_id], "*", "sort asc");
        foreach($group_list as $k=>$v){
            //查询标签图片
            $picture = new AlbumPictureModel();
            $picture_info = $picture ->get($v['group_pic']);
            $group_list[$k]['pic_cover'] = $picture_info['pic_cover'];
            
            
            $goods_list = $goods->getGoodsViewList(1, PAGESIZE, "FIND_IN_SET(".$v["group_id"].",ng.group_id_array) AND ng.state = 1", "ng.sort asc");
//             var_dump($goods_list);
            if(!empty($goods_list["data"])){
                foreach($goods_list["data"] as $t=>$m){
//                     $is_exist = true;
//                     foreach($group_goods_list as $q=>$w){
//                         if($w["goods_id"] == $m["goods_id"]){
//                             $is_exist = false;
//                             break;
//                         }
//                     }

//                     if($is_exist){
                        $m["group_name"] = $v["group_name"];
                        $m["pic_cover"] = $group_list[$k]['pic_cover'];
                        $group_goods_list[] = $m;
//                     }
                } 
            }
        }

        return $group_goods_list;
    }

	/* (non-PHPdoc)
     * @see \data\api\IPlatform::getRecommendGoodsList()
     */
    public function getRecommendGoodsList($shop_id,$show_num = 4)
    {
        // TODO Auto-generated method stub
        $group = new VslGoodsGroupModel();
        $goods = new VslGoodsViewModel();

        $group_list = $group->getQuery(["shop_id"=>$shop_id,"website_id" => $this->website_id], "*", "sort desc");
        foreach($group_list as $k=>$v){
            $group_goods_list = array();
            $goods_list = $goods->getGoodsViewList(1, $show_num, "FIND_IN_SET(".$v["group_id"].",ng.group_id_array) AND ng.state = 1 AND ng.website_id = ".$this->website_id, "ng.sort desc");
            //var_dump($goods_list);
            if(!empty($goods_list["data"])){
                foreach($goods_list["data"] as $t=>$m){
                    $is_exist = true;
                    foreach($group_goods_list as $q=>$w){
                        if($w["goods_id"] == $m["goods_id"]){
                            $is_exist = false;
                            break;
                        }
                    }
                    if($is_exist){
                        $group_goods_list[] = $m;
                    }
                } 
            }
            $group_list[$k]["goods_list"] = $group_goods_list;
        }
        return $group_list;
    }
    
   /**
    * 分页获取公告列表
    * @param unknown $page_index
    * @param unknown $page_size
    * @param unknown $condition
    * @param string $order
    * @param string $field
    * @return number[]|unknown[]
    */
    public function getNoticeList($page_index, $page_size, $condition, $order = "", $field = "*"){
        $notice = new VslNoticeModel();
        return $notice -> pageQuery($page_index, $page_size, $condition, $order, $field);
    }
    
    /**
     * 获取公告详情
     * @param unknown $id
     * @param unknown $shop_id
     */
    public function getNoticeDetail($id){
        $notice = new VslNoticeModel();
        return $res = $notice -> getInfo(["id"=>$id,"website_id"=>$this->website_id]);
    }

    //判断是否开启自定义模板
    public function check_custom_template(){

        $id = Db::table('sys_custom_template')->where('shop_id',0)->where('website_id',$this->website_id)->where('type','1')->select();

        return $id;
    }

    /**
     * 添加或修改公告
     * @param unknown $notice_title
     * @param unknown $notice_content
     * @param unknown $shop_id
     * @param unknown $sort
     * @param unknown $id
     */
    public function addOrModifyNotice($notice_title, $notice_content, $shop_id, $sort, $id){
        $data = array(
            "notice_title" => $notice_title,
            "notice_content" => $notice_content,
            "shop_id" => $shop_id,
            "website_id" => $this->website_id,
            "sort" => $sort,
        );
        $notice = new VslNoticeModel();
        if($id == 0){
            $data["create_time"] = time();
            return $notice -> save($data);
        }else if($id > 0){
            $data["modify_time"] = time();
            return $notice -> save($data, ["id" => $id]);
        }
    }
    
    /**
     * 删除公告
     */
    public function deleteNotice($id){
        $notice = new VslNoticeModel();
        $retval = $notice -> destroy($id);
        return $retval;
    }
    
    /**
     * 更改公告排序
     */
    public function updateNoticeSort($sort, $id){
        $notice = new VslNoticeModel();
        $retval = $notice->save([
            'sort' => $sort
        ], [
            'id' => $id
        ]);
        return $retval;
    }
}