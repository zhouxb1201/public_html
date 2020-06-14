<?php

namespace app\cli\controller;

use addons\shop\model\VslShopModel;
use data\model\VslGoodsModel;
use data\model\VslGoodsSkuModel;
use data\model\VslStoreGoodsModel;
use data\model\VslStoreGoodsSkuModel;
use data\model\WebSiteModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class PlatformGoodsToStoreGoods extends Command
{

    protected function configure()
    {
        $this->setName('platform_goods_to_store_goods')->setDescription('平台有勾选核销门店的商品同步到门店');
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
            $shop_model = new VslShopModel();
            $website_ids = $website_model->getQuery(['shop_status' => 1], 'website_id', '');
            foreach ($website_ids as $k => $website_id) {
                //查找website_id下的所有shop_id
                $shop_ids = $shop_model->getQuery(['shop_state' => 1, 'website_id' => $website_id['website_id']], 'shop_id', '');
                foreach ($shop_ids as $k1 => $shop_id) {
                    //判断有没有o2o应用
                    if (getAddons('store', $website_id['website_id'], $shop_id['shop_id'])) {
                        $goods_model = new VslGoodsModel();
                        //查找此店铺下所有的商品
                        $goods_condition = [
                            'website_id' => $website_id['website_id'],
                            'shop_id' => $shop_id['shop_id'],
                            'state' => 1
                        ];
                        $goods_list = $goods_model->getQuery($goods_condition, '*', '');
                        foreach ($goods_list as $k2 => $goods_info) {
                            if ($goods_info['store_list']) {
                                $store_list = explode(',', $goods_info['store_list']);
                                foreach ($store_list as $k3 => $store_id) {
                                    if ($store_id > 0) {
                                        //从门店商品表判断这个门店有没有这个商品信息
                                        $store_goods_model = new VslStoreGoodsModel();
                                        $store_goods_condition = [
                                            'website_id' => $website_id['website_id'],
                                            'shop_id' => $shop_id['shop_id'],
                                            'store_id' => $store_id,
                                            'goods_id' => $goods_info['goods_id']
                                        ];
                                        $store_goods_info = $store_goods_model->getInfo($store_goods_condition, '');
                                        if (empty($store_goods_info)) {
                                            //同步商品信息到门店商品表
                                            $store_goods_data[] = [
                                                'goods_id' => $goods_info['goods_id'],
                                                'website_id' => $website_id['website_id'],
                                                'goods_name' => $goods_info['goods_name'],
                                                'shop_id' => $shop_id['shop_id'],
                                                'category_id' => $goods_info['category_id'],
                                                'category_id_1' => $goods_info['category_id_1'],
                                                'category_id_2' => $goods_info['category_id_2'],
                                                'category_id_3' => $goods_info['category_id_3'],
                                                'picture' => $goods_info['picture'],
                                                'stock' => $goods_info['stock'],
                                                'market_price' => $goods_info['market_price'],
                                                'price' => $goods_info['price'],
                                                'img_id_array' => $goods_info['img_id_array'],
                                                'state' => 0,
                                                'sales' => 0,
                                                'store_id' => $store_id,
                                                'create_time' => time()
                                            ];
                                            //同步sku信息到门店商品sku表
                                            $goods_sku_model = new VslGoodsSkuModel();
                                            $sku_list = $goods_sku_model->getQuery(['goods_id' => $goods_info['goods_id']], '*', '');
                                            foreach ($sku_list as $k4 => $sku_info) {
                                                $store_sku_data[] = [
                                                    'goods_id' => $sku_info['goods_id'],
                                                    'website_id' => $website_id['website_id'],
                                                    'shop_id' => $shop_id['shop_id'],
                                                    'sku_id' => $sku_info['sku_id'],
                                                    'sku_name' => $sku_info['sku_name'],
                                                    'attr_value_items' => $sku_info['attr_value_items'],
                                                    'price' => $sku_info['price'],
                                                    'market_price' => $sku_info['market_price'],
                                                    'stock' => $sku_info['stock'],
                                                    'store_id' => $store_id,
                                                    'create_time' => time(),
                                                ];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            //批量插入
            if ($store_goods_data && $store_sku_data) {
                $res = $store_goods_model->saveAll($store_goods_data, true);
                $store_goods_sku_model = new VslStoreGoodsSkuModel();
                $res1 = $store_goods_sku_model->saveAll($store_sku_data, true);
            }
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            debugLog($msg,'==>平台有勾选核销门店的商品同步到门店的异常信息<==');
        }
    }

}

?>