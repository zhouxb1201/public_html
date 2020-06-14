<?php

namespace app\wapapi\controller;

use addons\bargain\service\Bargain;
use addons\presell\service\Presell;
use addons\seckill\server\Seckill;
use data\service\Config;
use addons\miniprogram\service\MiniProgram as miniProgramService;

class Custom extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $type = request()->post('type');
        $id = request()->post('id');
        $shop_id = request()->post('shop_id');
        $is_mini = request()->post('is_mini');//小程序调用

        if (empty($type) || ($type == 6 && empty($id))) {
            return json(AjaxReturn(LACK_OF_PARAMETER));
        }

        $config_server = $is_mini ? new miniProgramService() : new Config();
        if ($type != 6) {
            //非自定义模板
            $condition['website_id'] = $this->website_id;
            $condition['shop_id'] = $this->instance_id;
            $condition['type'] = $type;
            $condition['in_use'] = 1;
            if ($type == 2 || $type == 3){
                $condition['shop_id'] = $shop_id;
            }

            $custom_info = $is_mini ?  $config_server->customTemplateInfo($condition) : $config_server->getCustomTemplateInfo($condition);
            if (empty($custom_info)) {
                // 获取系统默认的装修数据
                $system_condition['is_system_default'] = 1;
                $system_condition['is_default'] = 1;
                $system_condition['type'] = $type;
                $system_custom_info = $is_mini ? $config_server->customTemplateInfo($system_condition): $config_server->getCustomTemplateInfo($system_condition);
                $data['template_data'] = json_decode(htmlspecialchars_decode($system_custom_info['template_data']));
                if (isWIthTarBarAndCopyright($type)) {
                    //显示底部和版权的页面类型
                    unset($system_condition['in_use']);
                    $system_condition['type'] = ['IN', [7, 8]];//底部和版权
                    $custom_list = $is_mini
                        ? $config_server->customTemplateList(1, 0, $system_condition)
                        : $config_server->getCustomTemplateList(1, 0, $system_condition);

                    foreach ($custom_list['data'] as $k => $custom) {
                        if ($custom['type'] == 7) {
                            // 小程序重新组装
                            if ($is_mini) {
                                $list = [];
                                $footIcon = \think\Config::get('mp_foot.icon');
                                $mp_data = json_decode(htmlspecialchars_decode($custom['template_data']), true );
                                foreach ($mp_data['data'] as $key => $v) {
                                    $list[] = [
                                        'pagePath' => $v['path'],
                                        'text' => $v['text'],
                                        'iconPath' => $footIcon .basename($v['normal']),
                                        'selectedIconPath' => $footIcon .basename($v['active'])
                                    ];
                                    $pathList[] = $v['path'];
                                }
                                $data['tab_bar']['list'] = $list;

                            } else {
                                $data['tab_bar'] = json_decode(htmlspecialchars_decode($custom['template_data']));
                            }
                            continue;
                        }
                        if ($custom['type'] == 8) {
                            $data['copyright'] = json_decode(htmlspecialchars_decode($custom['template_data']));
                            continue;
                        }
                        if ($custom['type'] == 10) {
                            $data['wechat_set'] = json_decode(htmlspecialchars_decode($custom['template_data']));
                            continue;
                        }
                    }
                }
                return json(['code' => 1, 'message' => '成功获取', 'data' => $data]);
            }
            $data['template_data'] = json_decode(htmlspecialchars_decode($custom_info['template_data']));
            // 处理前端价格显示
            if ($data['template_data']->items) {
                foreach ($data['template_data']->items as $val) {
                    if ($val->id == 'goods' && $val->data) {
                        // 循环处理商品price
                        foreach ($val->data as $k => $v) {
                            if($this->is_seckill){
                                //判断是否是秒杀商品
                                $seckill_server = new Seckill();
                                //判断如果是秒杀商品，则取最低秒杀价
                                $goods_id = $v->goods_id;
                                $seckill_condition['nsg.goods_id'] = $goods_id;
                                $is_seckill = $seckill_server->isSkuStartSeckill($seckill_condition);
                                if($is_seckill){
                                    $v->price = $is_seckill['seckill_price'];
                                }
                            }
                            if($this->is_presell){
                                $presell_server = new Presell();
                                //判断如果是预售商品
                                $goods_id = $v->goods_id;
                                $is_presell = $presell_server->getPresellInfoByGoodsIdIng($goods_id);
                                if($is_presell){
                                    $v->price = $is_presell[0]['all_money'];
                                }
                            }
                            if($this->is_bargain){
                                $bargain_server = new Bargain();
                                //判断如果是预售商品
                                $goods_id = $v->goods_id;
                                $condition_bargain['website_id'] = $this->website_id;
                                $condition_bargain['goods_id'] = $goods_id;
                                $condition_bargain['end_bargain_time'] = ['>', time()];//未结束的
                                $condition_bargain['start_bargain_time'] = ['<', time()];//未结束的
                                $is_bargain = $bargain_server->isBargainByGoodsId($condition_bargain, 0);
                                if($is_bargain){
                                    $v->price = $is_bargain['start_money'];
                                }
                            }
                        }

                    }
                }
            }

            // 微信公众号
            if (empty($is_mini)) {
                $count_wechat = $config_server->getCustomTemplateCount(['shop_id' => $this->instance_id, 'website_id' => $this->website_id, 'type' => 10]);
                if(!$count_wechat){
                    $default_set = $config_server->getCustomTemplateInfo(['is_system_default' => 1, 'type' => 10]);
                    $wechat_set_data['shop_id'] = $this->instance_id;
                    $wechat_set_data['website_id'] = $this->website_id;
                    $wechat_set_data['type'] = 10; //公众号信息
                    $wechat_set_data['template_name'] = '公众号信息'; //公众号信息
                    $wechat_set_data['create_time'] = time();
                    $wechat_set_data['template_data'] = $default_set ? $default_set['template_data'] : '';
                    $config_server->saveCustomTemplate($wechat_set_data, 0);
                }
                $wechat_set = $config_server->getCustomTemplateInfo(['website_id' => $this->website_id,'shop_id' => $this->instance_id, 'type' => 10]);
                $data['wechat_set'] =json_decode(htmlspecialchars_decode($wechat_set['template_data']));
            }

            if (isWIthTarBarAndCopyright($type)) {
                //显示底部和版权的页面类型
                unset($condition['in_use']);
                $condition['type'] = ['IN', [7, 8]];//底部和版权
                $custom_list = $is_mini
                    ? $config_server->customTemplateList(1, 0, $condition)
                    : $config_server->getCustomTemplateList(1, 0, $condition);
                foreach ($custom_list['data'] as $k => $custom) {
                    if ($custom['type'] == 7) {//底部
                        // 小程序重新组装
                        if ($is_mini) {
                            $list = [];
                            $footIcon = \think\Config::get('mp_foot.icon');
                            $mp_data = json_decode(htmlspecialchars_decode($custom['template_data']), true );
                            foreach ($mp_data['data'] as $key => $v) {
                                $list[] = [
                                    'pagePath' => $v['path'],
                                    'text' => $v['text'],
                                    'iconPath' => $footIcon .basename($v['normal']),
                                    'selectedIconPath' => $footIcon .basename($v['active'])
                                ];
                                $pathList[] = $v['path'];
                            }
                            $data['tab_bar']['list'] = $list;

                        } else {
                            $data['tab_bar'] = json_decode(htmlspecialchars_decode($custom['template_data']));
                        }
                        continue;
                    }
                    if ($custom['type'] == 8) {
                        $data['copyright'] = json_decode(htmlspecialchars_decode($custom['template_data']));
                        continue;
                    }
                }
            }
            return json(['code' => 1, 'message' => '成功获取', 'data' => $data]);
        }
        if ($type == 6) {
            //自定义页面
            $condition['website_id'] = $this->website_id;
            $condition['id'] = $id;
            $custom_info = $is_mini ? $config_server->customTemplateInfo($condition) : $config_server->getCustomTemplateInfo($condition);
            if (empty($custom_info)) {
                return json(['code' => -1, 'message' => '模板不存在']);
            }
            $data['template_data'] = json_decode(htmlspecialchars_decode($custom_info['template_data']));
            return json(['code' => 1, 'message' => '成功获取', 'data' => $data]);
        }
    }
}