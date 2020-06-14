<?php

namespace app\cli\controller;

use addons\channel\model\VslChannelLevelModel;
use data\model\VslGoodsDiscountModel;
use data\model\WebSiteModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class AddGoodsChannelAuth_sp2 extends Command
{

    protected function configure()
    {
        $this->setName('add_goods_channel_auth_sp2')->setDescription('添加商品的渠道商等级权限');
    }

    /**
     * 启动服务端服务
     * @return \lib\crontab\IssServer
     */
    public function execute(Input $input, Output $output)
    {
        try {
            //查出所有的website_id
            $website_model = new WebSiteModel();
            $website_ids = $website_model->getQuery(['shop_status' => 1], 'website_id', '');
            foreach ($website_ids as $k => $website_id) {
                //判断有没有渠道商应用，有才执行
                if (getAddons('channel', $website_id['website_id'], 0)) {
                    //查出此平台下的所有渠道商等级
                    $channel_level_model = new VslChannelLevelModel();
                    $channel_level_list = $channel_level_model->getQuery(['website_id' => $website_id['website_id']], 'channel_grade_id', '');
                    $channel_level_arr = [];
                    foreach ($channel_level_list as $k1 => $channel_grade_id) {
                        $channel_level_arr[] = $channel_grade_id['channel_grade_id'];
                    }

                    //查出goods_discount表的value,把渠道商权限追加进去
                    $goods_discount_model = new VslGoodsDiscountModel();
                    $goods_discount_list = $goods_discount_model->getQuery(['website_id' => $website_id['website_id'], 'shop_id' => 0], 'goods_id', '');
                    foreach ($goods_discount_list as $k2 => $v) {
                        $goods_discount_model = new VslGoodsDiscountModel();
                        $value = $goods_discount_model->Query(['goods_id' => $v['goods_id'], 'website_id' => $website_id['website_id'], 'shop_id' => 0], 'value')[0];
                        $value = json_decode($value);
                        $value = objToArr($value);
                        $value['discount_channel_obj']['channel_level_id'] = $channel_level_arr;
                        $value = json_encode($value);
                        $channel_auth = implode(',', $channel_level_arr);
                        $goods_discount_model->save(['value' => $value, 'channel_auth' => $channel_auth], ['goods_id' => $v['goods_id'], 'website_id' => $website_id['website_id'], 'shop_id' => 0]);
                    }
                }
            }
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            debugLog($msg, '==>添加商品的渠道商等级权限的异常信息<==');
        }
    }

}

?>