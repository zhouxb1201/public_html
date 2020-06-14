<?php
namespace addons\shop\service\shopAccount;

use addons\shop\model\VslShopBankAccountModel;
use addons\shop\model\VslShopWithdrawModel;
use data\service\Pay\AliPay;
use data\service\Pay\tlPay;
use data\service\Pay\WeiXinPay;
use data\service\BaseService;
use addons\shop\model\VslShopModel;
use addons\shop\model\VslShopAccountModel;
use addons\shop\model\VslShopAccountRecordsModel;
use addons\shop\model\VslShopOrderReturnModel;
use data\model\VslOrderGoodsModel;
use data\model\VslOrderGoodsPromotionDetailsModel;
use addons\shop\model\VslShopOrderGoodsReturnModel;
use data\model\VslAccountModel;
use data\model\VslAccountOrderRecordsModel;
use data\model\VslAccountRecordsModel;
use data\model\VslAccountWithdrawUserRecordsModel;
use think\Log;
use data\model\UserModel;
use data\service\Config as WebConfig;
/**
 * 店铺账户管理
 */
class ShopAccount extends BaseService
{

    /**
     * **************************************************店铺账户计算--Start****************************************************************
     */
    /**
     * 更新店铺的入账总额
     * 
     * @param unknown $shop_id            
     * @param unknown $money            
     */
    public function updateShopAccountTotalMoney($shop_id, $money)
    {
        $account_model = new VslShopAccountModel();
        $account_info = $account_model->get($shop_id);
        // 没有的话新建账户
        if (empty($account_info)) {
            $data = array(
                'shop_id' => $shop_id,
                'website_id' => $this->website_id
            );
            $account_model->save($data);
            $account_info = $account_model->get($shop_id);
        }
        $data = array(
            "shop_total_money" => $account_info["shop_total_money"] + $money
        );
        $retval = $account_model->save($data, [
            'shop_id' => $shop_id,
            'website_id' => $this->website_id
        ]);
        return $retval;
    }

    /*--------------------------------------------------------  提现过程  -------------------------------------------------*/
    /**
     * 添加账户流水（店铺余额提现）
     * @param unknown $shop_id
     */
    public function addShopAccountData($shop_id, $number, $data_id,$is_examine='',$make_money='',$wx_openid='',$withdraw_no='',$type='',$account_number='',$service_charge,$charge,$platform_money)
    {
        $shop_account = new VslShopAccountModel();
        $all_info = $shop_account->getInfo(['shop_id'=> $shop_id,'website_id'=>$this->website_id], '*');
        $shop_total_money = $all_info['shop_total_money'];//可提现金额
        $freezing_balance = $all_info['shop_freezing_money'];//冻结金额
            if($is_examine==1 && $make_money==1 && $type==2){//提现自动审核并且自动打款(微信提现)
                //店铺账户改变
                $data_member = array(
                    'shop_freezing_money'=>$freezing_balance+abs($service_charge)+abs($charge)+$platform_money,//冻结余额增加
                    'shop_total_money' => $shop_total_money-abs($service_charge)-abs($charge)-$platform_money //可提现余额减少
                );
                $shop_account->save($data_member,['shop_id'=> $shop_id,'website_id'=>$this->website_id]);
                $weixin_pay = new WeiXinPay();
                $retval = $weixin_pay->EnterprisePayment($wx_openid,$withdraw_no,'',abs($service_charge),'店铺可用余额微信提现',$this->website_id);
                    if($retval['is_success']==1){//自动打款成功
                        return $this->addShopAccountRecords($platform_money,$charge,$service_charge,$number,$data_id,$shop_id,3,'店铺可用余额微信提现成功');
                    }else{//自动打款失败
                        return $this->addShopAccountRecords($platform_money,$charge,$service_charge,$number,$data_id,$shop_id,5,'店铺可用余额微信提现失败',$retval['msg']);
                    }
            }elseif($is_examine==1 && $make_money==1 && $type==1){//提现自动审核自动打款（银行卡提现）
                //店铺账户改变
                $data_member = array(
                    'shop_freezing_money'=>$freezing_balance+abs($service_charge)+abs($charge)+$platform_money,//冻结余额增加
                    'shop_total_money' => $shop_total_money-abs($service_charge)-abs($charge)-$platform_money //可提现余额减少
                );
                $shop_account->save($data_member,['shop_id'=> $shop_id,'website_id'=>$this->website_id]);
                $bank = new VslShopBankAccountModel();
                $bank_id = $bank->getInfo(['account_number'=>$account_number,'shop_id'=>$shop_id],'id')['id'];
                $tlpay_pay = new tlPay();
                $retval = $tlpay_pay->tlWithdraw($withdraw_no,$shop_id,$bank_id,abs($service_charge),$shop_id);
                if($retval['is_success']==1){//打款成功
                    return $this->addShopAccountRecords($platform_money,$charge,$service_charge,$number,$data_id,$shop_id,6,'店铺余额银行卡提现审核通过，提现成功');
                }else{//打款失败
                    return $this->addShopAccountRecords($platform_money,$charge,$service_charge,$number,$data_id,$shop_id,5,'店铺余额银行卡提现审核通过，提现失败',$retval['msg']);
                }
            }elseif($is_examine==1 && $make_money==1 && $type==3){//提现自动审核并且自动打款(支付宝提现)
                //店铺账户改变
                $data_member = array(
                    'shop_freezing_money'=>$freezing_balance+abs($service_charge)+abs($charge)+$platform_money,//冻结余额增加
                    'shop_total_money' => $shop_total_money-abs($service_charge)-abs($charge)-$platform_money //可提现余额减少
                );
                $shop_account->save($data_member,['shop_id'=> $shop_id,'website_id'=>$this->website_id]);
                $alipay_pay = new AliPay();
                $retval = $alipay_pay->aliPayTransferNew($withdraw_no,$account_number,abs($service_charge));
                if($retval['is_success']==1){
                    return $this->addShopAccountRecords($platform_money,$charge,$service_charge,$number,$data_id,$shop_id,2,'店铺可用余额支付宝提现成功');
                }else{//自动打款失败
                    return $this->addShopAccountRecords($platform_money,$charge,$service_charge,$number,$data_id,$shop_id,5,'店铺可用余额支付宝提现失败');
                }
            }elseif($is_examine==1 && $make_money==2 && $type==2 ){//自动审核待打款微信
                return $this->addShopAccountRecords($platform_money,$charge,$service_charge,$number,$data_id,$shop_id,0,'店铺可用余额微信提现审核通过，待打款');
            }elseif($is_examine==1 && $make_money==2 && $type==3 ){//自动审核待打款
                return $this->addShopAccountRecords($platform_money,$charge,$service_charge,$number,$data_id,$shop_id,0,'店铺可用余额支付宝提现审核通过，待打款');
            }elseif($is_examine==2 && $make_money==2 && $type==2){//手动审核,微信提现
                    return $this->addShopAccountRecords($platform_money,$charge,$service_charge,$number,$data_id,$shop_id,1,'店铺可用余额微信提现待审核');
            }elseif($is_examine==2 && $make_money==2 && $type==3){//手动审核,支付宝提现
                    return $this->addShopAccountRecords($platform_money,$charge,$service_charge,$number,$data_id,$shop_id,1,'店铺可用余额支付宝提现待审核');
            }elseif($is_examine==2 && $make_money==1 && $type==2){//手动审核自动打款,微信提现
                return $this->addShopAccountRecords($platform_money,$charge,$service_charge,$number,$data_id,$shop_id,1,'店铺可用余额微信提现待审核');
            }elseif($is_examine==2 && $make_money==1 && $type==3){//手动审核自动打款,支付宝提现
                return $this->addShopAccountRecords($platform_money,$charge,$service_charge,$number,$data_id,$shop_id,1,'店铺可用余额支付宝提现待审核');
            }elseif($is_examine==2 && $make_money==1 && $type==1){//手动审核自动打款,银行卡提现
                return $this->addShopAccountRecords($platform_money,$charge,$service_charge,$number,$data_id,$shop_id,1,'店铺可用余额银行卡提现待审核');
            }elseif($is_examine==2 && $make_money==2 && $type==1){//手动审核,银行卡提现
                return $this->addShopAccountRecords($platform_money,$charge,$service_charge,$number,$data_id,$shop_id,1,'店铺可用余额银行卡提现待审核');
            }elseif($is_examine==1 && $make_money==2 && $type==1){//自动审核待打款银行卡
                return $this->addShopAccountRecords($platform_money,$charge,$service_charge,$number,$data_id,$shop_id,0,'店铺可用余额银行卡提现待打款');
            }elseif($is_examine==1 && $make_money==2 && $type==4){//自动审核待打款银行卡
                return $this->addShopAccountRecords($platform_money,$charge,$service_charge,$number,$data_id,$shop_id,0,'店铺可用余额银行卡提现待打款');
            }elseif($is_examine==2 && $make_money==1 && $type==4){//手动审核自动打款,银行卡提现
                return $this->addShopAccountRecords($platform_money,$charge,$service_charge,$number,$data_id,$shop_id,1,'店铺可用余额银行卡提现待审核');
            }elseif($is_examine==1 && $make_money==1 && $type==4){//自动审核自动打款,银行卡提现
                return $this->addShopAccountRecords($platform_money,$charge,$service_charge,$number,$data_id,$shop_id,0,'店铺可用余额银行卡提现待打款');
            }elseif($is_examine==2 && $make_money==2 && $type==4){//手动审核,银行卡提现
                return $this->addShopAccountRecords($platform_money,$charge,$service_charge,$number,$data_id,$shop_id,1,'店铺可用余额银行卡提现待审核');
            }

    }
    /**
     * 添加账户流水（余额提现审核通过，自动打款）
     * @param unknown $shop_id
     */
    public function addAuditShopAccountData($platform_money,$charge,$service_charge,$data_id,$shop_id,$number,$uid,$withdraw_no,$type,$account_number='')
    {
        $Config = new WebConfig();
        $withdraw_type = $Config->getConfig(0, 'WITHDRAW_BALANCE');
        $withdraw_type['value'] = json_decode($withdraw_type['value'], true);
        $make_money = $withdraw_type['value']['make_money'];
        $params['shop_id'] = 0;
        $params['takeoutmoney'] = abs($number);
        $params['uid'] = $uid;
        $params['website_id'] = $this->website_id;
        if($type==1 && $make_money==1){//提现审核通过，自动打款(银行卡提现)
            $bank = new VslShopBankAccountModel();
            $bank_id = $bank->getInfo(['account_number'=>$account_number,'shop_id'=>$shop_id],'id')['id'];
            $tlpay_pay = new tlPay();
            $retval = $tlpay_pay->tlWithdraw($withdraw_no,$uid,$bank_id,abs($service_charge),$shop_id);
            if($retval['is_success']==1){//打款成功
                runhook('Notify', 'withdrawalSuccessBySms', $params);
                return $this->addShopAccountRecords($platform_money,$charge,$service_charge,$number,$data_id,$shop_id,6,'店铺余额银行卡提现审核通过，提现成功');
            }else{//打款失败
                return $this->addShopAccountRecords($platform_money,$charge,$service_charge,$number,$data_id,$shop_id,5,'店铺余额银行卡提现审核通过，提现失败',$retval['msg']);
            }
        }
        if($type==2 && $make_money==1){//提现审核通过，自动打款(微信提现)
            $weixin_pay = new WeiXinPay();
            $user = new UserModel();
            $member_info = $user->getInfo(['user_tel'=>$account_number,'is_member'=>1,'website_id'=>$this->website_id],'wx_openid');
            $wx_openid = $member_info['wx_openid'];
            $retval = $weixin_pay->EnterprisePayment($wx_openid,$withdraw_no,'',abs($service_charge),'余额微信提现审核通过',$this->website_id);
            if($retval['is_success']==1){//打款成功
                runhook('Notify', 'withdrawalSuccessBySms', $params);
                return $this->addShopAccountRecords($platform_money,$charge,$service_charge,$number,$data_id,$shop_id,3,'余额微信提现审核通过，提现成功');
            }else{//打款失败
                return $this->addShopAccountRecords($platform_money,$charge,$service_charge,$number,$data_id,$shop_id,5,'余额微信提现审核通过，提现失败',$retval['msg']);
            }
        }
        if($type==3 && $make_money==1){//提现审核通过，自动打款(支付宝提现)
            $alipay_pay = new AliPay();
            $retval = $alipay_pay->aliPayTransferNew($withdraw_no,$account_number,abs($service_charge));
            if($retval['is_success']==1){//打款成功
                 runhook('Notify', 'withdrawalSuccessBySms', $params);
                return $this->addShopAccountRecords($platform_money,$charge,$service_charge,$number,$data_id,$shop_id,2,'余额支付宝提现审核通过，提现成功');
            }else{//打款失败
                return $this->addShopAccountRecords($platform_money,$charge,$service_charge,$number,$data_id,$shop_id,5,'余额支付宝提现审核通过，提现失败',$retval['msg']);
            }
        }

        if($type==3 && $make_money==2){//提现审核通过，手动打款(支付宝提现)
            return $this->addShopAccountRecords($platform_money,$charge,$service_charge,$number,$data_id,$shop_id,7,'店铺余额支付宝提现审核通过，待打款');
        }
        if($type==2 && $make_money==2){//提现审核通过，手动打款(微信提现)
            return $this->addShopAccountRecords($platform_money,$charge,$service_charge,$number,$data_id,$shop_id,7,'店铺余额微信提现审核通过，待打款');
        }
        if($type==2 && $make_money==2){//提现审核通过，手动打款(银行卡提现)
            return $this->addShopAccountRecords($platform_money,$charge,$service_charge,$number,$data_id,$shop_id,7,'店铺余额银行卡提现审核通过，待打款');
        }
        if($type==4 && $make_money==2){//提现审核通过，手动打款(银行卡提现)
            return $this->addShopAccountRecords($platform_money,$charge,$service_charge,$number,$data_id,$shop_id,7,'店铺余额银行卡提现审核通过，待打款');
        }
        if($type==4 && $make_money==1){//提现审核通过，手动打款(银行卡提现)
            return $this->addShopAccountRecords($platform_money,$charge,$service_charge,$number,$data_id,$shop_id,7,'店铺余额银行卡提现审核通过，待打款');
        }
    }
    /**
     * 添加店铺账户流水（余额提现审核通过自动打款，拒绝打款，审核不通过）
     * @param unknown $shop_id
     */
    public function addShopAccountRecords($platform_money,$charge,$service_charge,$number,$data_id,$shop_id,$status,$text,$msg=''){
        $balance_withdraw = new VslShopWithdrawModel();
        if($status==7){//手动审核通过，待打款
            // 修改店铺提现状态
            $withdraw_data = array(
                'status' => 2,
                'memo'=>$msg,
                'modify_date' => time(),
            );
            $retval = $balance_withdraw->save($withdraw_data,['id'=> $data_id]);
            return $retval;
        }
        $shop_account = new VslShopAccountModel();
        $all_info = $shop_account->getInfo(['shop_id'=> $shop_id,'website_id'=>$this->website_id], '*');
        $shop_withdraw = $all_info['shop_withdraw'];//已提现金额
        $freezing_balance = $all_info['shop_freezing_money'];//冻结金额
        $shop_total_money = $all_info['shop_total_money'];//可提现金额
        $shop_charge = $all_info['shop_charge '];//手续费
        $shop_account_record = new VslShopAccountRecordsModel();
        $shop_account_record->startTrans();
        try {
            if($status==3){//微信打款成功
                // 修改店铺提现状态
                $balance_withdraw = new VslShopWithdrawModel();
                $withdraw_data = array(
                    'status' => 3,
                    'payment_date'=>time(),
                    'modify_date' => time(),
                );
                $balance_withdraw->save($withdraw_data,['id'=> $data_id]);
                //店铺账户改变
                $data_member = array(
                    'shop_freezing_money'=>$freezing_balance-abs($service_charge)-abs($charge)-abs($platform_money),//冻结余额减少
                    'shop_withdraw' => $shop_withdraw +abs($number), //已提现余额增加
                    'shop_charge' => $shop_charge +abs($charge)
                );
                $shop_account->save($data_member,['shop_id'=> $shop_id,'website_id'=>$this->website_id]);
                //添加店铺账户流水
                $data = array(
                    'serial_no' => 'St'.getSerialNo(),
                    'account_type' => 8,
                    'money' => $number,
                    'type_alis_id' => $data_id,
                    'title'=>'提现',
                    'charge'=>$charge,
                    'remark' => '微信提现成功',
                    'create_time' => time(),
                    'website_id'=>$this->website_id
                );
                $datas = array(
                    'serial_no' => 'St'.getSerialNo(),
                    'account_type' => 8,
                    'money' => $platform_money,
                    'type_alis_id' => $data_id,
                    'title'=>'店铺提现',
                    'remark' => '店铺微信提现成功,可提现金额减少,平台抽取利润',
                    'create_time' => time(),
                    'website_id'=>$this->website_id
                );
                $shop_account_record->isUpdate(false)->save($datas);
                // 添加平台的整体资金流水和平台提现流水
                //添加平台利润
                $this->addShopOrderAccountRecords($shop_id,$platform_money,$data_id);
                //更新平台提现金额
                $this->updateAccountWithdraw($number);
                if(abs($charge)>0){
                    $this->addAccountRecords($shop_id, 0, '店铺提现成功手续费', abs($charge), 25, $data_id, "店铺微信提现成功，提现手续费增加!");
                }
                $this->addAccountRecords($shop_id, 0, '店铺提现成功', abs($number), 19, $data_id, "店铺微信提现成功，平台账户提现金额增加!");
            }
            if($status==6){//银行卡打款成功
                // 修改店铺提现状态
                $balance_withdraw = new VslShopWithdrawModel();
                $withdraw_data = array(
                    'status' => 3,
                    'payment_date'=>time(),
                    'modify_date' => time()
                );
                $balance_withdraw->save($withdraw_data,['id'=> $data_id]);
                //店铺账户改变
                $data_member = array(
                    'shop_freezing_money'=>$freezing_balance-abs($service_charge)-abs($charge)-abs($platform_money),//冻结余额减少
                    'shop_withdraw' => $shop_withdraw +abs($number), //已提现余额增加
                    'shop_charge' => $shop_charge +abs($charge)
                );
                $shop_account->save($data_member,['shop_id'=> $shop_id,'website_id'=>$this->website_id]);
                //添加店铺账户流水
                $data = array(
                    'serial_no' => 'St'.getSerialNo(),
                    'account_type' => 8,
                    'money' => $number,
                    'charge'=>$charge,
                    'type_alis_id' => $data_id,
                    'title'=>'提现',
                    'remark' => '银行卡提现成功，冻结余额减少，已提现余额增加',
                    'create_time' => time(),
                    'website_id'=>$this->website_id
                );
                $datas = array(
                    'serial_no' => 'St'.getSerialNo(),
                    'account_type' => 8,
                    'money' => $platform_money,
                    'type_alis_id' => $data_id,
                    'title'=>'店铺提现',
                    'remark' => '店铺提现成功,可提现金额减少,平台抽取利润',
                    'create_time' => time(),
                    'website_id'=>$this->website_id
                );
                $shop_account_record->isUpdate(false)->save($datas);
                // 添加平台的整体资金流水和平台提现流水
                //添加平台利润
                $this->addShopOrderAccountRecords($shop_id,$platform_money,$data_id);
                //更新平台提现金额
                $this->updateAccountWithdraw($number);
                if(abs($charge)>0){
                    $this->addAccountRecords($shop_id, 0, '店铺提现成功手续费', abs($charge), 25, $data_id, "店铺银行卡提现成功，提现手续费增加!");
                }
                $this->addAccountRecords($shop_id, 0, '店铺提现成功', abs($number), 40, $data_id, "店铺银行卡提现成功，平台账户提现金额增加!");
            }
            if($status==2){//支付宝打款成功
                // 修改店铺提现状态
                $balance_withdraw = new VslShopWithdrawModel();
                $withdraw_data = array(
                    'status' => 3,
                    'payment_date'=>time(),
                    'modify_date' => time()
                );
                $balance_withdraw->save($withdraw_data,['id'=> $data_id]);
                //店铺账户改变
                $data_member = array(
                    'shop_freezing_money'=>$freezing_balance-abs($service_charge)-abs($charge)-abs($platform_money),//冻结余额减少
                    'shop_withdraw' => $shop_withdraw +abs($number), //已提现余额增加
                    'shop_charge' => $shop_charge +abs($charge)
                );
                $shop_account->save($data_member,['shop_id'=> $shop_id,'website_id'=>$this->website_id]);
                //添加店铺账户流水
                $data = array(
                    'serial_no' => 'St'.getSerialNo(),
                    'account_type' => 8,
                    'money' => $number,
                    'charge'=>$charge,
                    'type_alis_id' => $data_id,
                    'title'=>'提现',
                    'remark' => '支付宝提现成功，冻结余额减少，已提现余额增加',
                    'create_time' => time(),
                    'website_id'=>$this->website_id
                );
                $datas = array(
                    'serial_no' => 'St'.getSerialNo(),
                    'account_type' => 8,
                    'money' => $platform_money,
                    'type_alis_id' => $data_id,
                    'title'=>'店铺提现',
                    'remark' => '店铺提现成功,可提现金额减少,平台抽取利润',
                    'create_time' => time(),
                    'website_id'=>$this->website_id
                );
                $shop_account_record->isUpdate(false)->save($datas);
                // 添加平台的整体资金流水和平台提现流水
                //添加平台利润
                $this->addShopOrderAccountRecords($shop_id,$platform_money,$data_id);
                //更新平台提现金额
                $this->updateAccountWithdraw($number);
                if(abs($charge)>0){
                    $this->addAccountRecords($shop_id, 0, '店铺提现成功手续费', abs($charge), 25, $data_id, "店铺支付宝提现成功，提现手续费增加!");
                }
                $this->addAccountRecords($shop_id, 0, '店铺提现成功', abs($number), 20, $data_id, "店铺支付宝提现成功，平台账户提现金额增加!");
            }
            if($status==5){//打款失败
                // 修改会员提现状态
                $balance_withdraw = new VslShopWithdrawModel();
                $withdraw_data = array(
                    'status' => $status,
                    'modify_date' => time(),
                );
                $balance_withdraw->save($withdraw_data,['id'=> $data_id,'website_id'=>$this->website_id]);
                //添加店铺账户流水
                $data = array(
                    'serial_no' => 'St'.getSerialNo(),
                    'account_type' => 8,
                    'money' => $number,
                    'type_alis_id' => $data_id,
                    'title'=>'提现',
                    'charge'=>$charge,
                    'msg'=>$msg,
                    'remark' => '打款失败，重新打款',
                    'create_time' => time(),
                    'website_id'=>$this->website_id
                );
            }
            if($status==1){//待审核
                //店铺账户改变
                $data_member = array(
                    'shop_freezing_money'=>$freezing_balance+abs($service_charge)+abs($charge)+abs($platform_money),//冻结余额增加
                    'shop_total_money' => $shop_total_money - abs($service_charge)-abs($charge)-abs($platform_money)//可提现余额减少
                );
                $shop_account->save($data_member,['shop_id'=> $shop_id,'website_id'=>$this->website_id]);
                //添加店铺账户流水
                $data = array(
                    'serial_no' => 'St'.getSerialNo(),
                    'account_type' => 8,
                    'money' => $number,
                    'charge'=>$charge,
                    'type_alis_id' => $data_id,
                    'title'=>'提现',
                    'remark' => '待审核，冻结余额增加，可提现余额减少',
                    'create_time' => time(),
                    'website_id'=>$this->website_id
                );
            }
            if($status==-1){//审核不通过
                // 修改会员提现状态
                $balance_withdraw = new VslShopWithdrawModel();
                $withdraw_data = array(
                    'status' => $status,
                    'modify_date' => time(),
                );
                $balance_withdraw->save($withdraw_data,['id'=> $data_id]);
                //店铺账户改变
                $data_member = array(
                    'shop_freezing_money'=>$freezing_balance- abs($service_charge)-abs($charge)-abs($platform_money),//冻结余额减少
                    'shop_total_money' => $shop_total_money +abs($service_charge)+abs($charge)+abs($platform_money) //可提现余额增加
                );
                $shop_account->save($data_member,['shop_id'=> $shop_id,'website_id'=>$this->website_id]);
                //添加店铺账户流水
                $data = array(
                    'serial_no' => 'St'.getSerialNo(),
                    'account_type' => 8,
                    'charge'=>$charge,
                    'money' => $number,
                    'type_alis_id' => $data_id,
                    'title'=>'提现',
                    'remark' => '审核不通过，冻结余额减少，可提现余额增加',
                    'create_time' => time(),
                    'website_id'=>$this->website_id
                );
                $acount = new ShopAccount();
                // 添加平台的会员提现资金流水和添加平台的整体资金流水
                $acount->addAccountRecords($shop_id, 0, '店铺提现审核不通过', abs($number), 21, $data_id, "店铺提现申请，平台审核不通过!");
            }
            if($status==4){//拒绝打款
                // 修改会员提现状态
                $balance_withdraw = new VslShopWithdrawModel();
                $withdraw_data = array(
                    'status' => $status,
                    'modify_date' => time(),
                );
                $balance_withdraw->save($withdraw_data,['id'=> $data_id]);
                //店铺账户改变
                $data_member = array(
                    'shop_freezing_money'=>$freezing_balance- abs($service_charge)-abs($charge)-abs($platform_money),//冻结余额减少
                    'shop_total_money' => $shop_total_money +abs($service_charge)+abs($charge)+abs($platform_money) //可提现余额增加
                );
                $shop_account->save($data_member,['shop_id'=> $shop_id,'website_id'=>$this->website_id]);
                //添加店铺账户流水
                $data = array(
                    'serial_no' => 'St'.getSerialNo(),
                    'account_type' => 8,
                    'money' => $number,
                    'charge'=>$charge,
                    'type_alis_id' => $data_id,
                    'title'=>'提现',
                    'remark' => '拒绝打款，冻结余额减少，可提现余额增加',
                    'create_time' => time(),
                    'website_id'=>$this->website_id
                );
                // 添加平台的会员提现资金流水和添加平台的整体资金流水
                $this->addAccountRecords($shop_id, 0, '店铺提现拒绝打款', $number, 26, $data_id, "店铺提现拒绝打款!");
            }
            if($status==0){//自动审核，待打款
                //店铺账户改变
                $data_member = array(
                    'shop_freezing_money'=>$freezing_balance+abs($service_charge)+abs($charge)+abs($platform_money),//冻结余额增加
                    'shop_total_money' => $shop_total_money - abs($service_charge)-abs($charge)-abs($platform_money)//可提现余额减少
                );
                $shop_account->save($data_member,['shop_id'=> $shop_id,'website_id'=>$this->website_id]);
                //添加店铺账户流水
                $data = array(
                    'serial_no' => 'St'.getSerialNo(),
                    'money' => $number,
                    'account_type' => 8,
                    'charge'=>$charge,
                    'type_alis_id' => $data_id,
                    'title'=>'提现',
                    'remark' => '提现已审核，可用余额减少，冻结余额增加',
                    'create_time' => time(),
                    'website_id'=>$this->website_id
                );
            }
            $res = $shop_account_record->save($data);
            $shop_account_record->commit();
            return $res;
        }catch (\Exception $e)
        {
            $shop_account_record->rollback();
            return $e->getMessage();
        }

    }
    /*
     * 同意打款（在线打款）
     * */
    public function addAgreeShopAccountData($service_charge,$data_id,$shop_id,$number,$text){
        $shop_account = new VslShopAccountModel();
        $all_info = $shop_account->getInfo(['shop_id'=> $shop_id,'website_id'=>$this->website_id], '*');
        $freezing_balance = $all_info['shop_freezing_money'];
        $withdraw_balance = $all_info['shop_withdraw'];
        $shop_charge = $all_info['charge'];
        $shop_account_record = new VslShopAccountRecordsModel();
        $balance_withdraw = new VslShopWithdrawModel();
        $withdraw_info  = $balance_withdraw->getInfo(['id'=> $data_id], '*');
            $params['shop_id'] = 0;
            $params['takeoutmoney'] = abs($number);
            $params['uid'] = $withdraw_info['uid'];
            $params['website_id'] = $this->website_id;
            if($withdraw_info['type']==3){
                $alipay_pay = new AliPay();
                $retval = $alipay_pay->aliPayTransferNew($withdraw_info['withdraw_no'],$withdraw_info['account_number'],abs($service_charge));
            }
            if($withdraw_info['type']==2){
                $weixin_pay = new WeiXinPay();
                $user = new UserModel();
                $member_info = $user->getInfo(['user_tel'=>$withdraw_info['account_number'],'is_member'=>1,'website_id'=>$this->website_id],'wx_openid');
                $wx_openid = $member_info['wx_openid'];
                $retval = $weixin_pay->EnterprisePayment($wx_openid,$withdraw_info['withdraw_no'],'',abs($service_charge),'店铺余额微信提现,手动打款',$this->website_id);
            }
            if($withdraw_info['type']==1){
                $bank = new VslShopBankAccountModel();
                $bank_id = $bank->getInfo(['account_number'=>$withdraw_info['account_number'],'shop_id'=>$shop_id],'id')['id'];
                $tlpay_pay = new tlPay();
                $retval = $tlpay_pay->tlWithdraw($withdraw_info['withdraw_no'],$withdraw_info['uid'],$bank_id,abs($service_charge),$shop_id);
            }
            if($retval['is_success']==1){//打款成功
                runhook('Notify', 'withdrawalSuccessBySms', $params);
                // 修改会员提现状态
                $withdraw_data = array(
                    'status' => 3,
                    'payment_date'=>time(),
                    'modify_date' => time(),
                );
                $res= $balance_withdraw->save($withdraw_data,['id'=> $data_id]);
                //店铺账户改变
                $data_member = array(
                    'shop_freezing_money'=>$freezing_balance-abs($service_charge)-abs($withdraw_info['charge'])-abs($withdraw_info['platform_money']),
                    'shop_withdraw' => $withdraw_balance +abs($number),//已提现余额增加
                    'shop_charge'=>$shop_charge+abs($withdraw_info['charge'])
                );
                $shop_account->save($data_member,['shop_id'=> $shop_id,'website_id'=>$this->website_id]);
                //添加店铺账户流水
                $data = array(
                    'serial_no' => 'St'.getSerialNo(),
                    'account_type' => 8,
                    'money' => $number,
                    'type_alis_id' => $data_id,
                    'title'=>'店铺提现',
                    'charge'=>$withdraw_info['charge'],
                    'remark' => '提现成功，冻结余额减少',
                    'create_time' => time(),
                    'website_id'=>$this->website_id
                );
                $datas = array(
                    'serial_no' => 'St'.getSerialNo(),
                    'account_type' => 8,
                    'money' => $withdraw_info['platform_money'],
                    'type_alis_id' => $data_id,
                    'title'=>'店铺提现',
                    'remark' => '店铺提现成功,可提现金额减少,平台抽取利润',
                    'create_time' => time(),
                    'website_id'=>$this->website_id
                );
                $shop_account_record->isUpdate(false)->save($datas);
                //平台账户改变
                $this->updateAccountWithdraw($number);
                //添加平台利润
                $this->addShopOrderAccountRecords($shop_id,$withdraw_info['platform_money'],$data_id);
                // 添加平台的整体资金流水
                if($withdraw_info['type']==3) {
                    if($withdraw_info['charge']){
                        $this->addAccountRecords($shop_id, 0, $text, abs($withdraw_info['charge']), 25, $data_id, "店铺支付宝提现成功，提现手续费增加!");
                    }
                    $this->addAccountRecords($shop_id, 0, $text, abs($number), 20, $data_id, "店铺支付宝提现成功，平台账户提现金额增加!");
                    $res = $shop_account_record->save($data);
                }
                if($withdraw_info['type']==2) {
                    if($withdraw_info['charge']){
                        $this->addAccountRecords($shop_id, 0, $text, abs($withdraw_info['charge']), 25, $data_id, "店铺微信提现成功，提现手续费增加!");
                    }
                    $this->addAccountRecords($shop_id, 0, $text, abs($number), 19, $data_id, "店铺微信提现成功，平台账户提现金额增加!");
                    $res = $shop_account_record->save($data);
                }
                if($withdraw_info['type']==1) {
                    if($withdraw_info['charge']){
                        $this->addAccountRecords($shop_id, 0, $text, abs($withdraw_info['charge']), 25, $data_id, "店铺银行卡提现成功，提现手续费增加!");
                    }
                    $this->addAccountRecords($shop_id, 0, $text, abs($number), 40, $data_id, "店铺银行卡提现成功，平台账户提现金额增加!");
                    $res = $shop_account_record->save($data);
                }
                return $res;
            }else{//打款失败
                if($withdraw_info['type']==1) {
                    $this->addShopAccountRecords($withdraw_info['platform_money'],$withdraw_info['charge'],$service_charge,$number, $data_id, $shop_id, 5, '店铺余额银行卡提现，在线打款失败', $retval['msg']);
                }
                if($withdraw_info['type']==2) {
                    $this->addShopAccountRecords($withdraw_info['platform_money'],$withdraw_info['charge'],$service_charge,$number, $data_id, $shop_id, 5, '店铺余额微信提现，在线打款失败', $retval['msg']);
                }
                if($withdraw_info['type']==3) {
                    $this->addShopAccountRecords($withdraw_info['platform_money'],$withdraw_info['charge'],$service_charge,$number, $data_id, $shop_id, 5, '店铺余额支付宝提现，在线打款失败', $retval['msg']);
                }
                return -1;
            }
    }
    /*
     * 同意打款（手动打款）
     * */
    public function addAgreeShopAccountDatas($service_charge,$data_id,$shop_id,$number,$text){
        $shop_account = new VslShopAccountModel();
        $all_info = $shop_account->getInfo(['shop_id'=> $shop_id,'website_id'=>$this->website_id], '*');
        $freezing_balance = $all_info['shop_freezing_money'];
        $withdraw_balance = $all_info['shop_withdraw'];
        $shop_charge = $all_info['charge'];
        $shop_account_record = new VslShopAccountRecordsModel();
        $balance_withdraw = new VslShopWithdrawModel();
        $withdraw_info  = $balance_withdraw->getInfo(['id'=> $data_id], '*');
        $params['shop_id'] = 0;
        $params['takeoutmoney'] = abs($number);
        $params['uid'] = $withdraw_info['uid'];
        $params['website_id'] = $this->website_id;
        if($withdraw_info['type']==3){
            $retval['is_success']=1;
        }
        if($withdraw_info['type']==2){
            $retval['is_success']=1;
        }
        if($withdraw_info['type']==1 || $withdraw_info['type']==4){
            $retval['is_success']=1;
        }
        if($retval['is_success']==1){//打款成功
            runhook('Notify', 'withdrawalSuccessBySms', $params);
            // 修改会员提现状态
            $withdraw_data = array(
                'status' => 3,
                'payment_date'=>time(),
                'modify_date' => time(),
            );
            $balance_withdraw->save($withdraw_data,['id'=> $data_id]);
            //店铺账户改变
            $data_member = array(
                'shop_freezing_money'=>$freezing_balance-abs($service_charge)-abs($withdraw_info['charge'])-abs($withdraw_info['platform_money']),
                'shop_withdraw' => $withdraw_balance +abs($number),//已提现余额增加
                'shop_charge'=>$shop_charge+abs($withdraw_info['charge'])
            );
            $shop_account->save($data_member,['shop_id'=> $shop_id,'website_id'=>$this->website_id]);
            //添加店铺账户流水
            $data = array(
                'serial_no' => 'St'.getSerialNo(),
                'account_type' => 8,
                'money' => $number,
                'charge'=>$withdraw_info['charge'],
                'type_alis_id' => $data_id,
                'title'=>'店铺提现',
                'remark' => '提现成功，冻结余额减少',
                'create_time' => time(),
                'website_id'=>$this->website_id
            );
            //添加店铺账户抽取利润
            $datas = array(
                'serial_no' => 'St'.getSerialNo(),
                'account_type' => 8,
                'money' => $withdraw_info['platform_money'],
                'type_alis_id' => $data_id,
                'title'=>'店铺提现',
                'remark' => '店铺提现成功,可提现金额减少,平台抽取利润',
                'create_time' => time(),
                'website_id'=>$this->website_id
            );
            //平台账户改变
            $this->updateAccountWithdraw($number);
            //添加平台利润
            $this->addShopOrderAccountRecords($shop_id,$withdraw_info['platform_money'],$data_id);
            // 添加平台的整体资金流水
            if($withdraw_info['type']==3) {
                if(abs($withdraw_info['charge'])>0){
                    $this->addAccountRecords($shop_id, 0, $text, abs($withdraw_info['charge']), 25, $data_id, "店铺支付宝提现成功，提现手续费增加!");
                }
                $this->addAccountRecords($shop_id, 0, $text, abs($number), 20, $data_id, "店铺支付宝提现成功，平台账户提现金额增加!");
            }
            if($withdraw_info['type']==2) {
                if(abs($withdraw_info['charge'])>0){
                    $this->addAccountRecords($shop_id, 0, $text, abs($withdraw_info['charge']), 25, $data_id, "店铺微信提现成功，提现手续费增加!");
                }
                $this->addAccountRecords($shop_id, 0, $text, abs($number), 19, $data_id, "店铺微信提现成功，平台账户提现金额增加!");
            }
            if($withdraw_info['type']==1 || $withdraw_info['type']==4) {
                if(abs($withdraw_info['charge'])>0){
                    $this->addAccountRecords($shop_id, 0, $text, abs($withdraw_info['charge']), 25, $data_id, "店铺银行卡提现成功，提现手续费增加!");
                }
                $this->addAccountRecords($shop_id, 0, $text, abs($number), 40, $data_id, "店铺银行卡提现成功，平台账户提现金额增加!");
            }
            $shop_account_record->isUpdate(false)->save($datas);
            $res = $shop_account_record->save($data);
            return $res;
        }else{//打款失败
            if($withdraw_info['type']==1 || $withdraw_info['type']==4) {
                $this->addShopAccountRecords($withdraw_info['platform_money'],$withdraw_info['charge'],$service_charge,$number, $data_id, $shop_id, 5, '店铺余额银行卡提现，手动打款失败', $retval['msg']);
            }
            if($withdraw_info['type']==2) {
                $this->addShopAccountRecords($withdraw_info['platform_money'],$withdraw_info['charge'],$service_charge,$number, $data_id, $shop_id, 5, '店铺余额微信提现，手动打款失败', $retval['msg']);
            }
            if($withdraw_info['type']==3) {
                $this->addShopAccountRecords($withdraw_info['platform_money'],$withdraw_info['charge'],$service_charge,$number, $data_id, $shop_id, 5, '店铺余额支付宝提现，手动打款失败', $retval['msg']);
            }
            return -1;
        }
    }
    /*--------------------------------------------------------  提现过程  -------------------------------------------------*/
    /**
     * 更新店铺的平台提现金额
     * 
     * @param unknown $shop_id            
     * @param unknown $money            
     */
    private function updateShopAccountWithdraw($shop_id, $money)
    {
        $account_model = new VslShopAccountModel();
        $account_info = $account_model->get($shop_id);

        // 没有的话新建账户
        if (empty($account_info)) {
            $data = array(
                'shop_id' => $shop_id,
                'website_id' => $this->website_id
            );
            $account_model->save($data);
            $account_info = $account_model->get($shop_id);
        }
        $data = array(
            "shop_withdraw" => $account_info["shop_withdraw"] + $money
        );

        $retval = $account_model->save($data, [
            'shop_id' => $shop_id,
            'website_id' => $this->website_id
        ]);
        return $retval;
    }

    /**
     * 订单退款 更新平台抽取金额
     *
     * @param unknown $order_id
     * @param unknown $order_goods_id
     * @param unknown $shop_id
     */
    public function updateShopOrderGoodsReturnRecords($order_id, $order_goods_id, $shop_id)
    {
        $order_goods_promotion = new VslOrderGoodsPromotionDetailsModel();
        $order_goods_return_model = new VslShopOrderGoodsReturnModel();
        $order_goods_model = new VslOrderGoodsModel();
        $order_goods_count = $order_goods_return_model->getCount([
            "order_goods_id" => $order_goods_id
        ]);
        if ($order_goods_count > 0) {
            try {
                $order_goods_return_model->startTrans();
                // 平台的抽成比率
                $rate_obj = $order_goods_return_model->getInfo([
                    "order_goods_id" => $order_goods_id
                ], "rate");
                $rate = $rate_obj["rate"];
                // 得到订单项的基本信息
                $order_goods = $order_goods_model->get($order_goods_id);
                $promotion_money = $order_goods_promotion->where([
                    'order_id' => $order_id,
                    'sku_id' => $order_goods['sku_id']
                ])->sum('discount_money');
                if (empty($promotion_money)) {
                    $promotion_money = 0;
                }
                // 订单项的实际付款金额
                $order_goods_real_money = $order_goods['goods_money'] + $order_goods['adjust_money'] - $order_goods['refund_real_money'] - $promotion_money;
                // 订单项的抽取的总额
                $order_goods_return_money = $order_goods_real_money * $rate / 100;
                $goods_data = array(
                    "goods_pay_money" => $order_goods_real_money,
                    "return_money" => $order_goods_return_money
                );
                $order_goods_return_model->save($goods_data, [
                    "order_id" => $order_id,
                    "order_goods_id" => $order_goods_id
                ]);
                // 订单总支付金额
                $total_pay_money = $order_goods_return_model->getSum([
                    "order_id" => $order_id
                ], "goods_pay_money");
                // 订单总利润金额
                $total_return_money = $order_goods_return_model->getSum([
                    "order_id" => $order_id
                ], "return_money");
                $return_data = array(
                    "order_pay_money" => $total_pay_money,
                    "platform_money" => $total_return_money
                );
                $order_return_model = new VslShopOrderReturnModel();
                $order_return_model->save($return_data, [
                    "order_id" => $order_id
                ]);
                $order_goods_return_model->commit();
            } catch (\Exception $e) {
                $order_goods_return_model->rollback();
            }
        }
    }

    /**
     * 得到订单项的的对平台的提成比率
     *
     * @param unknown $shop_id
     */
    public function getShopAccountRate($shop_id)
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
     * 得到店铺的账户情况
     * 
     * @param unknown $shop_id            
     * @return \think\static
     */
    public function getShopAccount($shop_id)
    {
        // TODO Auto-generated method stub
        $shop_account = new VslShopAccountModel();
        $account_obj = $shop_account->getInfo(['shop_id'=>$shop_id],'*');
        if (empty($account_obj)) {
            // 默认添加
            $data = array(
                "shop_id" => $shop_id,
                'website_id' => $this->website_id
            );
            $shop_account->save($data);
            $account_obj = $shop_account->getInfo(['shop_id'=>$shop_id],'*');
        }
        // 店铺入账总额
        $shop_entry_money = $account_obj["shop_entry_money"];
        // 店铺订单退款中
        $order = new VslOrderGoodsModel();
        $refund1 = $order->getSum(['shop_id'=>$shop_id,'refund_status'=>1,'website_id'=>$this->website_id],'refund_require_money');
        $refund2 = $order->getSum(['shop_id'=>$shop_id,'refund_status'=>2,'website_id'=>$this->website_id],'refund_require_money');
        $refund3 = $order->getSum(['shop_id'=>$shop_id,'refund_status'=>3,'website_id'=>$this->website_id],'refund_require_money');
        $refund4 = $order->getSum(['shop_id'=>$shop_id,'refund_status'=>4,'website_id'=>$this->website_id],'refund_require_money');
        $account_obj['shop_refund_money'] = $refund1+$refund2+$refund3+$refund4;
        // 店铺提现中
        $withdraw = new VslShopWithdrawModel();
        $shop_withdraw_money1 = $withdraw->getSum(['shop_id'=>$shop_id,'status'=>1,'website_id'=>$this->website_id],'cash');
        $shop_withdraw_money2 = $withdraw->getSum(['shop_id'=>$shop_id,'status'=>2,'website_id'=>$this->website_id],'cash');
        $account_obj["shop_withdraw_present"] = $shop_withdraw_money1 + $shop_withdraw_money2;
        // 平台抽取利润总额
        $shop_platform_commission = $withdraw->getSum(['shop_id'=>$shop_id,'status'=>3,'website_id'=>$this->website_id],'platform_money');
        $account_obj["shop_platform_commission"] = $shop_platform_commission;
        // 店铺已提现总额
        $shop_withdraw1 = $withdraw->getSum(['shop_id'=>$shop_id,'status'=>3,'website_id'=>$this->website_id],'cash');
        $account_obj["shop_withdraw"] = abs($shop_withdraw1);
        // 店铺可用总额
        $account_obj["shop_balance"] = $shop_entry_money - $shop_withdraw -$shop_platform_commission-$account_obj['shop_refund_money']-$account_obj["shop_withdraw_present"];
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
    public function addAccountOrderRecords($shop_id, $money, $account_type, $type_alis_id, $remark,$uid=0)
    {
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
                'website_id'=>$this->website_id
            );
            $order_model->save($data);
            // 更新订单的总金额字段
            $this->updateAccountOrderMoney($money);
            // 添加平台的整体资金流水
//            $this->addAccountRecords($shop_id, $uid, "订单支付成功!", $money, 13, $type_alis_id, "订单在线支付!");
            $order_model->commit();
        } catch (\Exception $e) {
            Log::write("addAccountOrderRecords".$e->getMessage());
            $order_model->rollback();
        }
    }

    /**
     * 更新平台账户的订单总额
     * 
     * @param unknown $money            
     */
    private function updateAccountOrderMoney($money)
    {
        $account_model = new VslAccountModel();
        $account_obj = $account_model->getInfo([
            'website_id' => $this->website_id
        ]);
        if($account_obj){
            $data = array(
                "account_order_money" => $account_obj["account_order_money"] + $money
            );
            $account_model->save($data, [
                'website_id' => $this->website_id
            ]);
        }else{
            $data = array(
                'website_id' => $this->website_id,
                "account_order_money" => $money
            );
            $account_model->save($data);
        }

    }


    /**
     * 更新平台的抽取店铺利润的总额
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
        $account_obj = $account_model->getInfo([
            'website_id' => $this->website_id
        ]);
        $data = array(
            "account_withdraw" => $account_obj["account_withdraw"] + $money
        );
        $account_model->save($data, [
            'website_id' => $this->website_id
        ]);
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
                'serial_no' => getSerialNo(),
                'shop_id' => $shop_id,
                'money' => $money,
                'account_type' => $account_type,
                'type_alis_id' => $type_alis_id,
                'create_time' => time(),
                'remark' => $remark,
                "website_id" => $this->website_id
            );
            $withdraw_model->save($data);
            // 更新提现总额的字段
            $this->updateAccountUserWithdraw($money);
            // 添加平台的整体资金流水
//            $this->addAccountRecords($shop_id, 0, "会员提现成功!", $money, 1, $type_alis_id, "会员提现申请提现，平台审核通过!");
            $withdraw_model->commit();
        } catch (\Exception $e) {
            $withdraw_model->rollback();
        }
    }
    /**
     * 更新平台的 会员充值金额
     *
     * @param unknown $money
     */
    public function addAccountUserWithdraw($money)
    {
        $account_model = new VslAccountModel();
        $account_obj = $account_model->getInfo([
            'website_id' => $this->website_id
        ]);
        $data = array(
            'account_order_money'=> $account_obj["account_order_money"] +$money,
        );
        $account_model->save($data, [
            'website_id' => $this->website_id
        ]);
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
    public function addAccountRecords($shop_id, $user_id, $title, $money, $account_type, $type_alis_id, $remark)
    {
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
            "website_id" => $this->website_id
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
            "account_id" => $this->website_id
        ]);
        $balance = $plat_obj["account_order_money"] + $plat_obj["account_deposit"] + $plat_obj["account_assistant"] - $plat_obj["account_withdraw"] - $plat_obj["account_user_withdraw"];
        $plat_obj["balance"] = $balance;
        return $plat_obj;
    }

    /**
     * **************************************************平台账户--End****************************************************************
     */
    
    /**
     * 店铺提现针对平台的利润
     *
     * @param unknown $order_id
     * @param unknown $order_no
     * @param unknown $shop_id
     * @param unknown $real_pay
     * @return unknown
     */
    public function addShopOrderAccountRecords($shop_id, $real_pay,$data_id)
    {
        $shopWithdraw = new VslShopWithdrawModel();
        $Withdraw = $shopWithdraw->getInfo(['id'=>$data_id],'*');
        $uid = $Withdraw['uid'];
        //更新店铺账户中平台抽取店铺利润总额shop_platform_commission
        $shop = new VslShopAccountModel();
        $account = $shop->getInfo(['shop_id'=>$shop_id,'website_id'=>$this->website_id],'*');
        if ($real_pay) {
            $platform_shop = new VslAccountModel();
            $platform_account = $platform_shop->getInfo(['website_id'=>$this->website_id],'*');
            $shop_account = $account ['shop_platform_commission']+abs($real_pay);
            $shop->save(['shop_platform_commission'=>$shop_account],['website_id'=>$this->website_id,'shop_id'=>$shop_id]);
            if($platform_account){
                $real_platform = abs($real_pay)+$platform_account['account_return'];
                $platform_shop->save(['account_return'=>$real_platform],['website_id'=>$this->website_id]);
            }
            $this->addAccountRecords($shop_id, $uid, '店铺提现成功',$real_pay, 41, $data_id, "店铺提现成功，平台抽取利润");
        }
    }
}