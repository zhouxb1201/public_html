<?php
namespace data\service;

use data\service\BaseService;
use data\model\WeixinInstanceMsgModel;
use data\model\VslOrderModel;
use data\extend\WchatOauth;
use data\model\VslGoodsModel;
use data\model\VslOrderGoodsExpressModel;

class WeixinMessage extends BaseService
{
    /*
     * (non-PHPdoc)获取模板列表
     * @see \ata\api\IWeixinMessage::getWeixinMsgTemplate()
     */
    public function getWxMsgTemplate($website_id)
    {
        $WeixinInstanceMsgModel = new WeixinInstanceMsgModel();
        $list1 = $WeixinInstanceMsgModel->getInfo(['website_id'=>$website_id,'type'=>1]);
        if(!isset($list1)){
            $WeixinInstanceMsgModel->data(['is_use'=>0,'website_id'=>$website_id,'type'=>1],true)->isUpdate(false)->save();
        }
        $list2 = $WeixinInstanceMsgModel->getInfo(['website_id'=>$website_id,'type'=>2]);
        if(!isset($list2)){
            $WeixinInstanceMsgModel->data(['is_use'=>0,'website_id'=>$website_id,'type'=>2],true)->isUpdate(false)->save();
        }
        $list3 = $WeixinInstanceMsgModel->getInfo(['website_id'=>$website_id,'type'=>3]);
        if(!isset($list3)){
            $WeixinInstanceMsgModel->data(['is_use'=>0,'website_id'=>$website_id,'type'=>3],true)->isUpdate(false)->save();
        }
        $list4 = $WeixinInstanceMsgModel->getInfo(['website_id'=>$website_id,'type'=>4]);
        if(!isset($list4)){
            $WeixinInstanceMsgModel->data(['is_use'=>0,'website_id'=>$website_id,'type'=>4],true)->isUpdate(false)->save();
        }
        $list5 = $WeixinInstanceMsgModel->getInfo(['website_id'=>$website_id,'type'=>5]);
        if(!isset($list5)){
            $WeixinInstanceMsgModel->data(['is_use'=>0,'website_id'=>$website_id,'type'=>5],true)->isUpdate(false)->save();
        }
        $list_info['type1'] = $WeixinInstanceMsgModel->getInfo(['website_id'=>$website_id,'type'=>1],'*');
        $list_info['type2'] = $WeixinInstanceMsgModel->getInfo(['website_id'=>$website_id,'type'=>2],'*');
        $list_info['type3'] = $WeixinInstanceMsgModel->getInfo(['website_id'=>$website_id,'type'=>3],'*');
        $list_info['type4'] = $WeixinInstanceMsgModel->getInfo(['website_id'=>$website_id,'type'=>4],'*');
        $list_info['type5'] = $WeixinInstanceMsgModel->getInfo(['website_id'=>$website_id,'type'=>5],'*');
        return $list_info;
    }
    public function addWxMsgTemplate($website_id,$type,$template_id,$is_use,$inform)
    {
        $WeixinInstanceMsgModel = new WeixinInstanceMsgModel();
        $data=[
            'template_id'=>$template_id,
            'is_use'=>$is_use,
            'inform'=>$inform
        ];
        $res = $WeixinInstanceMsgModel->save($data,['website_id'=>$website_id,'type'=>$type]);
        return $res;

    }
}