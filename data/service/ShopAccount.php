<?php
namespace data\service;
use addons\shop\model\VslShopModel;
use addons\shop\model\VslShopAccountModel;
use addons\shop\model\VslShopAccountRecordsModel;
use addons\shop\model\VslShopOrderReturnModel;
use data\model\VslOrderGoodsModel;
use addons\shop\model\VslShopOrderGoodsReturnModel;
use data\model\VslAccountModel;
use data\model\VslAccountOrderRecordsModel;
use data\model\VslAccountRecordsModel;
use data\model\VslAccountWithdrawUserRecordsModel;
use think\Log;
/**
 * 店铺账户管理
 */
class ShopAccount extends BaseService
{

    /**
     * **************************************************店铺账户计算--Start****************************************************************
     */
    /**
     * 更新店铺的可用总额
     *
     * @param unknown $shop_id
     * @param unknown $money
     */
    public function updateShopAccountMoney($shop_id,$money)
    {
        $account_model = new VslShopAccountModel();
        $account_info = $account_model->getInfo(['shop_id'=>$shop_id],'*');
        // 没有的话新建账户
        if (empty($account_info)) {
            $data = array(
                'shop_id' => $shop_id,
            );
            $account_model->save($data);
            $account_info = $account_model->getInfo(['shop_id'=>$shop_id],'*');
        }
        $data1 = array(
            "shop_total_money" => $account_info["shop_total_money"] + $money
        );
        $retval = $account_model->save($data1, [
            'shop_id' => $shop_id
        ]);
        return $retval;
    }
    /**
     * 更新店铺的营业额
     * 
     * @param unknown $shop_id            
     * @param unknown $money            
     */
    public function updateShopAccountTotalMoney($shop_id, $money)
    {
        $account_model = new VslShopAccountModel();
        $account_info = $account_model->getInfo(['shop_id'=>$shop_id],'*');
        // 没有的话新建账户
        if (empty($account_info)) {
            $data = array(
                'shop_id' => $shop_id
            );
            $account_model->save($data);
            $account_info = $account_model->getInfo(['shop_id'=>$shop_id],'*');
        }
        $data1 = array(
            "shop_entry_money" => $account_info["shop_entry_money"] + $money
        );

        $retval = $account_model->save($data1, [
            'shop_id' => $shop_id,
        ]);
        return $retval;
    }
    /**
     * 更新店铺的优惠总额
     *
     * @param unknown $shop_id
     * @param unknown $money
     */
    public function updateShopPromotionMoney($shop_id,$money)
    {
        $account_model = new VslShopAccountModel();
        $account_info = $account_model->getInfo(['shop_id'=>$shop_id],'*');
        // 没有的话新建账户
        if (empty($account_info)) {
            $data = array(
                'shop_id' => $shop_id
            );
            $account_model->save($data);
            $account_info = $account_model->getInfo(['shop_id'=>$shop_id],'*');
        }
        $data1 = array(
            "shop_promotion_money" => $account_info["shop_promotion_money"] + $money
        );
        $retval = $account_model->save($data1, [
            'shop_id' => $shop_id
        ]);
        return $retval;
    }

    /**
     * 添加店铺的整体的资金流水
     * 
     * @param unknown $serial_no            
     * @param unknown $shop_id            
     * @param unknown $money            
     * @param unknown $account_type            
     * @param unknown $type_alis_id            
     * @param unknown $remark            
     * @param unknown $title            
     */
    public function addShopAccountRecords($serial_no, $shop_id, $money, $account_type, $type_alis_id, $remark, $title,$website_id='')
    {
        if($website_id){
            $websiteid = $website_id;
        }else{
            $websiteid = $this->website_id;
        }
        $model = new VslShopAccountRecordsModel();
        $data = array(
            'shop_id' => $shop_id,
            'serial_no' => $serial_no,
            'account_type' => $account_type,
            'money' => $money,
            'type_alis_id' => $type_alis_id,
            'remark' => $remark,
            'title' => $title,
            'create_time' => time(),
            'website_id' => $websiteid
        );
         $model->save($data);
    }

    /**
     * 计算某个订单针对平台的利润
     *
     * @param unknown $order_id
     * @param unknown $order_no
     * @param unknown $shop_id
     * @param unknown $real_pay
     * @return unknown
     */
    public function addShopOrderAccountRecords($order_id, $order_no, $shop_id, $real_pay)
    {
        $shop_order_account_model = new VslShopOrderReturnModel();
        $order_goods_model = new VslOrderGoodsModel();
        $order_account_list = $shop_order_account_model->getInfo([
            "order_id" => $order_id
        ]);
        if (empty($order_account_list)) {
            $shop_order_account_model->startTrans();
            try {
                $rate = $this->getShopAccountRate( $shop_id);
                // 查询订单项的信息
                $condition["order_id"] = $order_id;
                $order_goods_list = $order_goods_model->getQuery($condition, '*', '');
                // 订单抽取的总额
                $order_return_money = 0;
                if (! empty($order_goods_list) && $rate >= 0) {
                    foreach ($order_goods_list as $k => $order_goods) {
                        // 订单项的订单商品实付金额
                        $order_goods_real_money = $order_goods['real_money'];
                        $order_goods_return_money = $order_goods_real_money * $rate / 100;
                        // 计算订单的抽取总额
                        $order_return_money = $order_return_money + $order_goods_return_money;
                        //更新店铺账户中平台抽取店铺利润总额shop_platform_commission
                        $shop = new VslShopAccountModel();
                        $account = $shop->getInfo(['shop_id'=>$shop_id],'*');
                        if($account){
                            $real_shop_platform_commission = $account['shop_platform_commission']+$order_return_money;
                            $shop->save(['shop_platform_commission'=>$real_shop_platform_commission],['shop_id'=>$shop_id]);
                            //更新平台账户中平台抽取利润总额account_return
                            $platform_shop = new VslAccountModel();
                            $platform_account = $platform_shop->getInfo(['website_id'=>$this->website_id],'*');
                            if($platform_account){
                                $real_platform_commission = $order_return_money+$platform_account['account_return'];
                                $platform_shop->save(['account_return'=>$real_platform_commission],['website_id'=>$this->website_id]);
                            }
                        }
                        $goods_data = array(
                            "shop_id" => $shop_id,
                            "order_id" => $order_id,
                            "order_goods_id" => $order_goods["order_goods_id"],
                            "goods_pay_money" => $order_goods_real_money,
                            "rate" => $rate,
                            "return_money" => $order_goods_return_money,
                            "create_time" => time(),
                            "website_id" => $this->website_id
                        );
                         $order_goods_return_model = new VslShopOrderGoodsReturnModel();
                         $order_goods_return_model->save($goods_data);
                    }
                    $data = array(
                        "shop_id" => $shop_id,
                        "order_id" => $order_id,
                        "order_no" => $order_no,
                        "order_pay_money" => $real_pay,
                        "platform_money" => $order_return_money,
                        "create_time" => time(),
                        "website_id" => $this->website_id
                    );
                    $shop_order_account_model->save($data);
                    $shop_order_account_model->commit();
                }
            } catch (\Exception $e) {
            recordErrorLog($e);
                $shop_order_account_model->rollback();
                Log::write("错误addShopOrderAccountRecords".$e->getMessage());
            }
        }
    }
    /**
     * 订单退款 更新平台抽取金额
     * 
     * @param unknown $order_id            
     * @param unknown $order_goods_id            
     * @param unknown $shop_id            
     */
    public function updateShopOrderGoodsReturnRecords($order_id, $order_goods_id,$shop_id)
    {
        $order_goods_return_model = new VslShopOrderGoodsReturnModel();
        $order_goods_model = new VslOrderGoodsModel();
        $order_return_model = new VslShopOrderReturnModel();
        $order_goods_count = $order_goods_return_model->getCount([
            "order_goods_id" => $order_goods_id
        ]);
        if ($order_goods_count > 0) {
            try {
                $order_goods_return_model->startTrans();
                // 得到订单项的基本信息
                $order_goods = $order_goods_model->getInfo(['order_goods_id'=>$order_goods_id,'order_id'=>$order_id],'*');
                // 获取商品利润的基本信息
                $order_goods_refund_info = $order_goods_return_model->getInfo(['order_goods_id'=>$order_goods_id,'order_id'=>$order_id],'*');
                // 获取利润的基本信息
                $order_return_info = $order_return_model->getInfo(['order_id'=>$order_id],'*');
                // 订单项的实际付款金额
                $order_goods_real_money = $order_goods['real_money'];
                // 订单项的实际退款金额
                $order_goods_require_money = $order_goods['refund_require_money'];
                $return_data = array(
                    "platform_money" => $order_return_info['platform_money']- $order_goods_real_money,
                );
                $order_return_model->save($return_data, [
                    "order_id" => $order_id
                ]);
                //更新店铺账户中平台抽取店铺利润总额shop_platform_commission
                $shop = new VslShopAccountModel();
                $account = $shop->getInfo(['shop_id'=>$shop_id],'*');
                if($account){
                    $real_shop_platform_commission = $account['shop_platform_commission']-$order_goods_real_money;
                    //退款金额差价给店铺
                    $real_shop_commission = $account['shop_total_money']+$order_goods_real_money-$order_goods_require_money;
                    $shop->save(['shop_platform_commission'=>$real_shop_platform_commission,'shop_total_money'=>$real_shop_commission],['shop_id'=>$shop_id]);
                }
                //更新平台账户中平台抽取利润总额account_return
                $platform_shop = new VslAccountModel();
                $platform_account = $platform_shop->getInfo(['website_id'=>$order_goods['website_id']],'*');
                if($platform_account){
                    $real_platform_commission = $platform_account['account_return']-$order_goods_real_money;
                    $platform_shop->save(['account_return'=>$real_platform_commission],['website_id'=>$order_goods['website_id']]);
                }
                $goods_data = array(
                    "return_money" => $order_goods_refund_info['return_money']-$order_goods_real_money
                );
                $order_goods_return_model->save($goods_data, [
                    "order_id" => $order_id,
                    "order_goods_id" => $order_goods_id
                ]);
                $order_goods_return_model->commit();
            } catch (\Exception $e) {
            recordErrorLog($e);
                $order_goods_return_model->rollback();
            }
        }
    }



    /**
     * 得到订单项的的对平台的提成比率
     * 
     * @param unknown $shop_id            
     */
    private function getShopAccountRate($shop_id)
    {
        $shop_model = new VslShopModel();
        // 得到店铺的信息
        $shop_obj = $shop_model->getInfo(['shop_id'=>$shop_id],'shop_platform_commission_rate');
        if (empty($shop_obj)) {
            return 0;
        } else {
            return $shop_obj["shop_platform_commission_rate"];
        }
    }

    /**
     * 店铺详情
     *
     * @param unknown $shop_id
     * @param unknown $shop_name
     */
    public function getStoreInformation($shop_id,$shop_name)
    {
        $model = new VslShopModel();
        // 得到店铺的信息
        $shop_obj = $model->getInfo(['shop_id'=>$shop_id,'shop_name'=>$shop_name],'shop_name,shop_logo,comprehensive,shop_deliverycredit,shop_desccredit,shop_servicecredit');
        if (empty($shop_obj)) {
            return 0;
        } else {
            return $shop_obj;
        }
    }


    /**
     * 得到店铺的账户情况
     * 
     * @param unknown $shop_id            
     * @return \think\static
     */
    public function getShopAccount($shop_id)
    {
        // TODO Auto-generated method stub
        $shop_account = new VslShopAccountModel();
        $account_obj = $shop_account->get($shop_id);
        if (empty($account_obj)) {
            // 默认添加
            $data = array(
                "shop_id" => $shop_id,
                'website_id' => $this->website_id
            );
            $shop_account->save($data);
            $account_obj = $shop_account->get($shop_id);
        }
        // 店铺收益总额
        $shop_proceeds = $account_obj["shop_proceeds"];
        // 平台抽取利润总额
        $shop_platform_commission = $account_obj["shop_platform_commission"];
        // 店铺提现总额
        $shop_withdraw = $account_obj["shop_withdraw"];
        // 店铺可用总额
        $shop_balance = $shop_proceeds - $shop_platform_commission - $shop_withdraw;
        $account_obj["shop_balance"] = $shop_balance;
        return $account_obj;
    }

    /**
     * **************************************************店铺账户计算--End****************************************************************
     */
    
    /**
     * **************************************************平台账户--Start****************************************************************
     */
    /**
     * 添加平台的订单入帐记录
     * 
     * @param unknown $shop_id            
     * @param unknown $money            
     * @param unknown $account_type            
     * @param unknown $type_alis_id            
     * @param unknown $remark            
     */
    public function addAccountOrderRecords($shop_id, $money, $account_type, $type_alis_id, $remark,$uid=0,$website_id=0)
    {
        if($website_id){
            $websiteid = $website_id;
        }else{
            $websiteid = $this->website_id;
        }
        $order_model = new VslAccountOrderRecordsModel();
        $order_model->startTrans();
        try {
            $data = array(
                'serial_no' => getSerialNo(),
                'shop_id' => $shop_id,
                'money' => $money,
                'account_type' => $account_type,
                'type_alis_id' => $type_alis_id,
                'create_time' => time(),
                'remark' => $remark,
                'website_id'=>$websiteid
            );
            $order_model->save($data);
            $order_model->commit();
        } catch (\Exception $e) {
            recordErrorLog($e);
            Log::write("addAccountOrderRecords".$e->getMessage());
            $order_model->rollback();
        }
    }

    /**
     * 更新平台账户的订单总额
     * 
     * @param unknown $money            
     */
    public function updateAccountOrderMoney($money)
    {
        $account_model = new VslAccountModel();
        $account_obj = $account_model->getInfo([
            'website_id' => $this->website_id
        ]);
        if($account_obj){
            $data = array(
                "account_order_money" => $account_obj["account_order_money"] + abs($money)
            );
            $account_model->save($data, ['website_id'=>$this->website_id]);
        }else{
            $data = array(
                'website_id' => $this->website_id,
                "account_order_money" => abs($money)
            );
            $account_model->save($data);
        }
    }
    /**
     *  更新平台账户的订单总额和退款总额
     *
     * @param unknown $money
     */
    public function updateAccountMoney($money,$website_id=0)
    {
        if($website_id){
            $websiteid = $website_id;
        }else{
            $websiteid = $this->website_id;
        }
        $account_model = new VslAccountModel();
        $account_obj = $account_model->getInfo([
            'website_id' => $websiteid
        ]);
        if($account_obj){
            $data = array(
                "account_order_money" => $account_obj["account_order_money"] - abs($money)
            );
            $account_model->save($data, [
                'website_id' => $websiteid
            ]);
            $data1 = array(
                "order_refund_money" => $account_obj["order_refund_money"] + abs($money)
            );
            $account_model->save($data1, [
                'website_id' => $websiteid
            ]);
        }
    }
    /**
     * 更新个人账户的订单余额支付总额
     *
     * @param unknown $money
     */
    public function updateAccountOrderBalance($money,$website_id=0)
    {
        if($website_id){
            $websiteid = $website_id;
        }else{
            $websiteid = $this->website_id;
        }
        $account_model = new VslAccountModel();
        $account_obj = $account_model->getInfo([
            'website_id' => $websiteid
        ]);
        if($account_obj){
            $data = array(
                "order_balance_money" => $account_obj["order_balance_money"] + $money
            );
            $account_model->save($data, [
                'website_id' => $websiteid
            ]);
        }else{
            $data = array(
                'website_id' => $websiteid,
                "order_balance_money" => $money
            );
            $account_model->save($data);
        }
    }
    /**
     * 更新个人账户的订单积分抵扣总额
     *
     * @param unknown $money
     */
    public function updateAccountOrderPoint($money,$website_id=0)
    {
        if($website_id){
            $websiteid = $website_id;
        }else{
            $websiteid = $this->website_id;
        }
        $account_model = new VslAccountModel();
        $account_obj = $account_model->getInfo([
            'website_id' => $websiteid
        ]);
        if($account_obj){
            $data = array(
                "order_point_money" => $account_obj["order_point_money"] + $money
            );
            $account_model->save($data, [
                'website_id' => $websiteid
            ]);
        }else{
            $data = array(
                'website_id' => $websiteid,
                "order_point_money" => $money
            );
            $account_model->save($data);
        }
    }

    /**
     * 更新平台的抽取例利润的总额
     * 
     * @param unknown $money            
     */
    private function updateAccountReturn($money)
    {
        $account_model = new VslAccountModel();
        $account_obj = $account_model->getInfo([
            'website_id' => $this->website_id
        ]);
        $data = array(
            "account_return" => $account_obj["account_return"] + $money
        );
        $account_model->save($data, [
            'website_id' => $this->website_id
        ]);
    }

    /**
     * 更新店铺在平台端的提现字段
     * 
     * @param unknown $money            
     */
   public function updateAccountWithdraw($money)
    {
        $account_model = new VslAccountModel();
        $account_model->startTrans();
        $account_obj = $account_model->getInfo([
            'website_id' => $this->website_id
        ]);
        $data = array(
            "account_withdraw" => $account_obj["account_withdraw"] + $money
        );
        try {
        $account_model->save($data, [
            'website_id' => $this->website_id
        ]);
        $account_model->commit();
        } catch (\Exception $e) {
            recordErrorLog($e);
            $account_model->rollback();
        }
    }


    /**
     * 针对平台 会员的提现金额
     * 
     * @param unknown $shop_id            
     * @param unknown $money            
     * @param unknown $account_type            
     * @param unknown $type_alis_id            
     * @param unknown $remark            
     */
    public function addAccountWithdrawUserRecords($shop_id, $money, $account_type, $type_alis_id, $remark)
    {
        $withdraw_model = new VslAccountWithdrawUserRecordsModel();
        $withdraw_model->startTrans();
        try {
            $data = array(
                'serial_no' => 'MT'.getSerialNo(),
                'shop_id' => $shop_id,
                'money' => $money,
                'account_type' => $account_type,
                'type_alis_id' => $type_alis_id,
                'create_time' => time(),
                'remark' => $remark,
                "website_id" => $this->website_id
            );
            $withdraw_model->save($data);
            $withdraw_model->commit();
        } catch (\Exception $e) {
            recordErrorLog($e);
            $withdraw_model->rollback();
        }
    }
    /**
     * 更新平台的 会员充值金额
     *
     * @param unknown $money
     */
    public function addAccountUserWithdraw($money,$data_id=0)
    {
        $account_model = new VslAccountModel();
        $account_model->startTrans();
        $account_obj = $account_model->getInfo([
            'website_id' => $this->website_id
        ]);
        if($data_id){
            $data = array(
                'account_order_money'=> $account_obj["account_order_money"] +$money,
            );
        }else{
            $data = array(
                'back_recharge'=> $account_obj["back_recharge"] +$money,
            );
        }
        try {
            $account_model->save($data, ['website_id' => $this->website_id]);
            $account_model->commit();
        } catch (\Exception $e) {
            recordErrorLog($e);
            $account_model->rollback();
        }
    }
    /**
     * 更新平台的 会员提现金额
     * 
     * @param unknown $money            
     */
    public function updateAccountUserWithdraw($money)
    {
        $account_model = new VslAccountModel();
        $account_obj = $account_model->getInfo([
            'website_id' => $this->website_id
        ]);
        $data = array(
            "account_user_withdraw" => $account_obj["account_user_withdraw"] + abs($money)
        );
        $account_model->save($data, [
            'website_id' => $this->website_id
        ]);
    }

    /**
     * 添加平台的整体资金流水
     * 
     * @param unknown $shop_id            
     * @param unknown $user_id            
     * @param unknown $title            
     * @param unknown $money            
     * @param unknown $account_type            
     * @param unknown $type_alis_id
     * @param unknown $remark            
     */
    public function addAccountRecords($shop_id, $user_id, $title, $money, $account_type, $type_alis_id, $remark,$website_id=0)
    {
        if($website_id){
            $websiteid = $website_id;
        }else{
            $websiteid = $this->website_id;
        }
        $account_model = new VslAccountRecordsModel();
        $plat_obj = $this->getPlatformAccount();
        $balance = $plat_obj["balance"];
        $data = array(
            "serial_no" => 'PT'.getSerialNo(),
            "shop_id" => $shop_id,
            "user_id" => $user_id,
            "title" => $title,
            "money" => $money,
            "account_type" => $account_type,
            "type_alis_id" => $type_alis_id,
            "balance" => $balance,
            "create_time" => time(),
            "remark" => $remark,
            "website_id" => $websiteid
        );
        $res = $account_model->save($data);
        return $res;
    }

    /**
     * 查询平台账户的资金情况
     * 
     * @return unknown
     */
    public function getPlatformAccount()
    {
        $plat_model = new VslAccountModel();
        $plat_obj = $plat_model->getInfo([
            "website_id" => $this->website_id
        ]);
        $plat_obj["balance"] = $plat_obj["account_order_money"];
        return $plat_obj;
    }

    /**
     * **************************************************平台账户--End****************************************************************
     */
}