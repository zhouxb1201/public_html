<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/25 0025
 * Time: 11:30
 */

namespace addons\coupontype\server;

use addons\coupontype\model\VslCouponGoodsModel;
use addons\coupontype\model\VslCouponModel;
use addons\coupontype\model\VslCouponTypeModel;
use addons\registermarketing\model\VslRegisterMarketingCouponTypeModel;
use data\model\AlbumPictureModel;
use data\model\VslGoodsModel;
use addons\shop\model\VslShopModel;
use data\service\BaseService;
use think\Db;
use data\model\AddonsConfigModel;
use data\service\AddonsConfig as AddonsConfigService;
use data\service\Member;
use data\model\VslGoodsViewModel;

class Coupon extends BaseService
{
    public $addons_config_module;

    function __construct()
    {
        parent::__construct();
        $this->addons_config_module = new AddonsConfigModel();
    }

    /**
     * 获取优惠券列表
     * @param int|string $page_index
     * @param int|string $page_size
     * @param array $condition
     * @param string $order
     * @param string $fields
     *
     * @return array $coupon_type_list
     */
    public function getCouponTypeList($page_index = 1, $page_size = 0, array $condition = [], $order = 'create_time desc', $fields = '*')
    {
        $coupon_type = new VslCouponTypeModel();
        $coupon_type_list = $coupon_type->pageQuery($page_index, $page_size, $condition, $order, $fields);
        if($coupon_type_list['data']){
            $coupon_model = new VslCouponModel();
            foreach ($coupon_type_list['data'] as $k => $v) {
                $coupon_type_list['data'][$k]['received'] = $coupon_model->where(['coupon_type_id'=>$v['coupon_type_id']])->count();
                $coupon_type_list['data'][$k]['surplus'] = $v['count'] - $coupon_type_list['data'][$k]['received'];
                $coupon_type_list['data'][$k]['surplus'] = ($coupon_type_list['data'][$k]['surplus']>0)?$coupon_type_list['data'][$k]['surplus']:0;
            }
        }
        return $coupon_type_list;
    }

    public function getCouponTypeDetail($coupon_type_id)
    {
        $coupon_type_model = new VslCouponTypeModel();
        $coupon_goods = new VslCouponGoodsModel();
        $data = $coupon_type_model::get($coupon_type_id, ['coupons']);
        $goods_list = $coupon_goods->getCouponTypeGoodsList($coupon_type_id);
        $coupon['received'] = 0;//已领取数目
        $coupon['used'] = 0; //已使用数目
        foreach ($goods_list as $k => $v) {
            $picture = new AlbumPictureModel();
            if (!empty($v['picture'])) {
                $pic_db_info = $picture->getInfo(['pic_id' => $v['picture']],'pic_cover,pic_cover_mid,pic_cover_micro');
                $pic_info['pic_cover'] = getApiSrc($pic_db_info['pic_cover']);
                $pic_info['pic_cover_micro'] = getApiSrc($pic_db_info['pic_cover_micro']);
            } else {
                $pic_info['pic_cover'] = '';
                $pic_info['pic_cover_micro'] = '';
            }
            $goods_list[$k]['picture_info'] = $pic_info;

            if(getAddons('shop', $this->website_id)){
                $shop = new VslShopModel();
                if (!empty($goods_info['shop_id']) && $goods_info['shop_id'] > 0) {
                    $shop_info = $shop->getInfo(['shop_id' => $goods_info['shop_id']]);
                } else {
                    $shop_info['shop_name'] = '自营店';
                }
            }else{
                $shop_info['shop_name'] = '自营店';
            }
            $goods_list[$k]['shop_name'] = $shop_info['shop_name'];
        }
        $coupon['received'] = $data->coupons()->where('state','<>',-1)->count();
        $coupon['used'] = $data->coupons()->where(['state' => 2])->count();
        $coupon['frozen'] = $data->coupons()->where(['state' => -1])->count();

        $data['goods_list'] = $goods_list;
        $goods_id_array = array();
        foreach ($data['goods_list'] as $k => $v) {
            $goods_id_array[] = $v['goods_id'];
        }
        $data['goods_id_array'] = $goods_id_array;
        $data['coupon'] = $coupon;

        return $data;
    }

    /**
     * @param array $input
     * @return int
     */
    public function addCouponType(array $input)
    {
        $coupon_type = new VslCouponTypeModel();
        $coupon_type->startTrans();
        try {
            $goods_list = $input['goods_list'];
            unset($input['goods_list']);
            $coupon_type_id = $coupon_type->save($input);
            // 添加类型商品表
            if ($input['range_type'] == 0 && !empty($goods_list)) {
                $goods_list_array = explode(',', $goods_list);
                foreach ($goods_list_array as $k => $v) {
                    $data_coupon_goods = array(
                        'coupon_type_id' => $coupon_type_id,
                        'goods_id' => $v,
                        'website_id' => $this->website_id
                    );
                    $coupon_goods = new VslCouponGoodsModel();
                    $retval = $coupon_goods->save($data_coupon_goods);
                }
            }
            $coupon_type->commit();
            return 1;
        } catch (\Exception $e) {
            $coupon_type->rollback();
            return $e->getMessage();
        }
    }

    /**
     * @param array $input
     * @return int
     */
    public function updateCouponType(array $input)
    {
        $coupon_type = new VslCouponTypeModel();
        $coupon_type->startTrans();
        try {
            $goods_list = $input['goods_list'];
            unset($input['goods_list']);
            $coupon_type->save($input, [
                'coupon_type_id' => $input['coupon_type_id']
            ]);
            // 更新类型商品表
            $coupon_goods = new VslCouponGoodsModel();
            $coupon_goods->destroy([
                'coupon_type_id' => $input['coupon_type_id']
            ]);
            if ($input['range_type'] == 0 && !empty($goods_list)) {
                $goods_list_array = explode(',', $goods_list);
                foreach ($goods_list_array as $k => $v) {
                    $data_coupon_goods = array(
                        'coupon_type_id' => $input['coupon_type_id'],
                        'goods_id' => $v,
                        'website_id' => $this->website_id
                    );
                    $coupon_goods = new VslCouponGoodsModel();
                    $retval = $coupon_goods->save($data_coupon_goods);
                }
            }
            // 修改优惠券时，更新优惠券的使用状态
            $coupon = new VslCouponModel();
            $coupon_condition['state'] = array(
                'in', [0, 3]
            ); // 未领用或者已过期的优惠券
            $coupon_condition['coupon_type_id'] = $input['coupon_type_id'];
            $coupon->save([
                'start_receive_time' => $input['start_receive_time'],
                'end_receive_time' => $input['end_receive_time'],
                'end_time' => $input['end_time'],
                'start_time' => $input['start_time'],
                'state' => 0
            ], $coupon_condition);
            $coupon_type->commit();
            return 1;
        } catch (\Exception $e) {
            $coupon_type->rollback();
            return 0;
        }
    }

    /**
     * @param int $page_index
     * @param int $page_size
     * @param array $condition
     * @param array $where
     * @param string $fields
     * @param string $order
     *
     * @return array $result
     */
    public function getCouponHistory($page_index = 1, $page_size = 0, array $condition = [], array $where = [], $fields = '*', $order = '')
    {
        $coupon_model = new VslCouponModel();
        $coupon_model->alias('nc')
            ->join('vsl_order no', 'nc.use_order_id = no.order_id', 'LEFT')
            ->join('vsl_shop ns', 'nc.shop_id = ns.shop_id AND nc.website_id = ns.website_id', 'LEFT')
            ->join('sys_user su', 'nc.uid = su.uid', 'LEFT')
            ->join('vsl_coupon_type nct', 'nc.coupon_type_id = nct.coupon_type_id', 'LEFT')
            ->field($fields)
            ->where($where);
        if (!empty($condition)) {
            $coupon_model->where(function ($query) use ($condition) {
                $query->whereOr($condition);
            });
        }
        if (!empty($order)) {
            $coupon_model->order($order);
        }

        if (!empty($page_index) && !empty($page_size)) {
            $coupon_model->limit($page_size * ($page_index - 1) . ',' . $page_size);
        }

        $list = $coupon_model->select();
//        var_dump(Db::table('')->getLastSql());

        $coupon_model->alias('nc')
            ->join('vsl_order no', 'nc.use_order_id = no.order_id', 'LEFT')
            ->join('vsl_shop ns', 'nc.shop_id = ns.shop_id AND nc.website_id = ns.website_id', 'LEFT')
            ->join('sys_user su', 'nc.uid = su.uid', 'LEFT')
            ->join('vsl_coupon_type nct', 'nc.coupon_type_id = nct.coupon_type_id', 'LEFT')
            ->field($fields)
            ->where($where);
        if (!empty($condition)) {
            $coupon_model->where(function ($query) use ($condition) {
                $query->whereOr($condition);
            });
        }
        $count = $coupon_model->count();
        //var_dump(Db::table('')->getLastSql());
        $result = $coupon_model->setReturnList($list, $count, $page_size);
        return $result;
    }

    /**
     * 使用优惠券
     * @param int|string $coupon_id
     * @param int|string $order_id
     *
     * @return int $res
     */
    public function useCoupon($coupon_id, $order_id)
    {
        $couponModel = new VslCouponModel();
        $coupon = $couponModel::get($coupon_id, ['coupon_type']);
        $data = array(
            'use_order_id' => $order_id,
            'state' => 2,
            'use_time' => time(),
            'create_order_id' => 0,
            'money' => $coupon->coupon_type->money,
            'discount' => $coupon->coupon_type->discount,
            'start_receive_time' => $coupon->coupon_type->start_receive_time,
            'end_receive_time' => $coupon->coupon_type->end_receive_time,
            'start_time' => $coupon->coupon_type->start_time,
            'end_time' => $coupon->coupon_type->end_time
        );
        $res = $couponModel->save($data, ['coupon_id' => $coupon_id]);
        return $res;
    }

    /**
     * 订单返还会员优惠券
     * @param int|string $coupon_id
     *
     * @return int $result
     */
    public function UserReturnCoupon($coupon_id)
    {
        $coupon = new VslCouponModel();
        $data = array(
            'state' => 1,
            'website_id' => $this->website_id
        );
        $result = $coupon->save($data, ['coupon_id' => $coupon_id]);
        return $result;
    }

    /**
     * 获取优惠券金额
     * @param int|string $coupon_id
     *
     * @return int
     */
    public function getCouponMoney($coupon_id)
    {
        $coupon = new VslCouponModel();
        $money = $coupon->getInfo(['coupon_id' => $coupon_id, 'website_id' => $this->website_id], 'money');
        if (!empty($money['money'])) {
            return $money['money'];
        } else {
            return 0;
        }
    }

    /**
     * 查询当前会员优惠券列表
     * @param int $state 1:未使用,2:已使用,3:已过期
     * @param int $shop_id
     * @param int $page_index
     * @param int $page_size
     *
     * @return array $coupon_list
     */
    public function getUserCouponList($state = '', $shop_id = '', $page_index = 1, $page_size = 0)
    {
        $condition['nc.uid'] = $this->uid;
        $condition['ct.website_id'] = $this->website_id;

        if ($state == 3) {
            $condition['ct.end_time'] = ['ELT', time()];
            $condition['nc.state'] = ['EQ', 1];
        } elseif($state == 2) {
            $condition['nc.state'] = $state;
        } else {
            $condition['nc.state'] = $state;
            $condition['ct.end_time'] = ['EGT', time()];
        }

        if (!empty($shop_id)) {
            $condition['ct.shop_id'] = $shop_id;
        }
        $coupon = new VslCouponModel();
        $coupon_list = $coupon->getCouponViewList($page_index, $page_size, $condition, 'nc.start_time desc');
        $list = [];
        $user = new Member();
        if (!empty($coupon_list['data'])) {
            foreach ($coupon_list['data'] as $k => $v) {
                if ($v['shop_range_type'] == 2) {
                    $list['data'][$k]['range'] = '全平台';
                } elseif ($v['shop_range_type'] == 1) {
                    $list['data'][$k]['range'] = '直营店';
                }
                if ($v['shop_id']) {
                    $list['data'][$k]['range'] = $v['shop_name'];
                }
                if ($v['state'] == 1) {
                    $list['data'][$k]['state_name'] = '未使用';
                } elseif ($v['state'] == 2) {
                    $list['data'][$k]['state_name'] = '已使用';
                } elseif ($v['state'] == 3) {
                    $list['data'][$k]['state_name'] = '已过期';
                }
                if ($v['coupon_genre']) {
                    $list['data'][$k]['genre'] = '无门槛券';
                }
                $list['data'][$k]['state'] = $state ?: $v['state'];
                $list['data'][$k]['coupon_code'] = $v['coupon_code'];
                $list['data'][$k]['start_time'] = date('Y-m-d H:i:s', $v['start_time']);
                $list['data'][$k]['end_time'] = date('Y-m-d H:i:s', $v['end_time']);
                $list['data'][$k]['shop_range_type'] = $v['shop_range_type'];
                $list['data'][$k]['range_type'] = $v['range_type'];
                $list['data'][$k]['coupon_genre'] = $v['coupon_genre'];
                $list['data'][$k]['discount'] = $v['discount'];
                $list['data'][$k]['money'] = $v['money'];
                $list['data'][$k]['coupon_name'] = $v['coupon_name'];
                $list['data'][$k]['at_least'] = $v['at_least'];
                $list['data'][$k]['shop_id'] = $v['shop_id'];
            }
            foreach ($list['data'] as $list2) {
                $list2["shop_id"] = $user->getShopNameByShopId($list2["shop_id"]);
                $list2["state"] = "未使用";
            }
        } else {
            $list['data'] = [];
        }
        $list['total_count'] = $coupon_list['total_count'];
        $list['page_count'] = $coupon_list['page_count'];
        return $list;
    }

    /**
     * 删除优惠券
     * @param int|string $coupon_type_id
     *
     * @return int 1
     */
    public function deleteCouponType($coupon_type_id)
    {
        $coupon_type = new VslCouponTypeModel();
        $coupon_type->startTrans();
        try {
            $coupon_type_info = $coupon_type::get($coupon_type_id, ['coupons']);
            if ($coupon_type_info->coupons()->count() == 0 || $coupon_type_info->end_time < time()) {
                $coupon_good_model = new VslCouponGoodsModel();
                $coupon_good_model::destroy(['coupon_type_id' => $coupon_type_id]);
                $relation_model = new VslRegisterMarketingCouponTypeModel();
                $relation_model::destroy(['coupon_type_id' => $coupon_type_id]);
                $coupon_model = new VslCouponModel();
                $coupon_model::destroy(['coupon_type_id' => $coupon_type_id]);
                $coupon_type::destroy(['coupon_type_id' => $coupon_type_id]);
                $coupon_type->commit();
                return 1;
            }
            return -1;
        } catch (\Exception $e) {
            $coupon_type->rollback();
            return $e->getMessage();
        }
    }

    /**
     * 获取会员下面的优惠券列表
     * @param int|string $shop_id
     * @param int|string $uid
     *
     * @return  array
     */
    public function getMemberCouponTypeList($shop_id, $uid)
    {
        // 查询可以发放的优惠券类型
        $coupon_type_model = new VslCouponTypeModel();
        $condition = array(
            'start_receive_time' => array(
                'ELT',
                time()
            ),
            'end_receive_time' => array(
                'EGT',
                time()
            ),
            'start_time' => array(
                'ELT',
                time()
            ),
            'end_time' => array(
                'EGT',
                time()
            ),
            'is_fetch' => 1,
            'shop_id' => $shop_id,
            'website_id' => $this->website_id
        );
        $coupon_type_list = $coupon_type_model->getQuery($condition, '*', '');
        if (!empty($uid)) {
            $list = array();
            if (!empty($coupon_type_list)) {
                foreach ($coupon_type_list as $k => $v) {
                    if ($v['max_fetch'] == 0) {
                        // 不限领
                        $list[] = $v;
                    } else {
                        $coupon = new VslCouponModel();
                        $count = $coupon->getCount([
                            'uid' => $uid,
                            'coupon_type_id' => $v['coupon_type_id'],
                            'website_id' => $this->website_id
                        ]);
                        if ($count < $v['max_fetch']) {
                            $list[] = $v;
                        }
                    }
                }
            }
            return $list;
        } else {
            return $coupon_type_list;
        }
    }

    public function getShopCouponList(array $condition = [])
    {
        //获取用户全部可使用的优惠券
        $coupon_model = new VslCouponTypeModel();
        return $coupon_model::all($condition);
    }

    public function getMemberCouponListNew(array $cart_sku_info)
    {
        if (!getAddons('coupontype', $this->website_id)) {
            return [];
        }
        //获取用户全部可使用的优惠券
        $coupon_model = new VslCouponModel();
        $condition['vsl_coupon_model.uid'] = $this->uid;
        $condition['coupon_type.website_id'] = $this->website_id;
        $condition['coupon_type.start_time'] = ['ELT', time()];
        $condition['coupon_type.end_time'] = ['GT', time()];
        $condition['vsl_coupon_model.state'] = 1;

        $member_coupon_list = $coupon_model::all($condition, ['coupon_type.goods']);

        $coupon_lists = [];
        foreach ($cart_sku_info as $shop_id => $sku_info) {
            foreach ($member_coupon_list as $coupon) {
                if ($coupon->coupon_type->shop_range_type == 1 && $coupon->coupon_type->shop_id != $shop_id) {
                    //只有直营店或者本店可用,但是shop_id不匹配
                    continue;
                }
                
                if ($coupon->coupon_type->range_type == 1) {//全部商品使用范围
                    $total_price = 0.00;
                    foreach ($sku_info as $sku) {
                        $total_price += $sku['discount_price'] * $sku['num'];
                        if (isset($sku['full_cut_percent_amount'])) {
                            $total_price -= $sku['full_cut_percent_amount'];
                        }
                    }
                    if ($coupon->coupon_type->at_least <= $total_price && $total_price > 0) {
                        $coupon['goods_limit'] = [];
                        $coupon_lists[$shop_id]['coupon_info'][$coupon['coupon_id']] = $coupon;

                        if ($coupon->coupon_type->coupon_genre == 1 || $coupon->coupon_type->coupon_genre == 2) {
                            
                            $i = 0;
                            $percent = 0;
                            $money = 0;
                            $length = count($sku_info);
                            foreach ($sku_info as $sku_id => $sku) {
                                $i++;
                                $coupon_lists[$shop_id]['sku_percent'][$coupon['coupon_id']][$sku_id]['sku_id'] = $sku_id;
                                $coupon_lists[$shop_id]['sku_percent'][$coupon['coupon_id']][$sku_id]['coupon_percent'] = round(($sku['discount_price'] * $sku['num'] - $sku['full_cut_percent_amount']) / $total_price, 2);
                                $coupon_lists[$shop_id]['sku_percent'][$coupon['coupon_id']][$sku_id]['coupon_percent_amount'] = round($coupon->coupon_type->money * $coupon_lists[$shop_id]['sku_percent'][$coupon['coupon_id']][$sku_id]['coupon_percent'], 2);
                                //优惠券优惠金额按价格占比分摊给每个商品,最后一个商品取剩下的优惠金额,避免误差
                                if($i != $length){
                                    $percent += round(($sku['discount_price'] * $sku['num'] - $sku['full_cut_percent_amount']) / $total_price, 2);
                                    $money +=  round($coupon->coupon_type->money * $coupon_lists[$shop_id]['sku_percent'][$coupon['coupon_id']][$sku_id]['coupon_percent'], 2);
                                }else{
                                    $coupon_lists[$shop_id]['sku_percent'][$coupon['coupon_id']][$sku_id]['coupon_percent'] = 1 - $percent;
                                    $coupon_lists[$shop_id]['sku_percent'][$coupon['coupon_id']][$sku_id]['coupon_percent_amount'] = round($coupon->coupon_type->money - $money,2);
                                }
                            }
                        } elseif ($coupon->coupon_type->coupon_genre == 3) {
                          	$i = 0;
                          	$percent = 0;
                                $money = 0;
                                $allCount = round($total_price * (10-$coupon->coupon_type->discount)/10, 2);
                                
                          	$length = count($sku_info);
                                foreach ($sku_info as $sku_id => $sku) {
                                $i++;
                                $coupon_lists[$shop_id]['sku_percent'][$coupon['coupon_id']][$sku_id]['sku_id'] = $sku_id;
                                $coupon_lists[$shop_id]['sku_percent'][$coupon['coupon_id']][$sku_id]['coupon_percent'] = round(($sku['discount_price'] * $sku['num'] - $sku['full_cut_percent_amount']) / $total_price, 2);
                                $coupon_lists[$shop_id]['sku_percent'][$coupon['coupon_id']][$sku_id]['coupon_percent_amount'] = round((10-$coupon->coupon_type->discount) * $total_price * $coupon_lists[$shop_id]['sku_percent'][$coupon['coupon_id']][$sku_id]['coupon_percent'] / 10, 2);
                                if($i != $length){
                                    
                                    $percent += round(($sku['discount_price'] * $sku['num'] - $sku['full_cut_percent_amount']) / $total_price, 2);
                                    $money += round((10-$coupon->coupon_type->discount) * $total_price * $coupon_lists[$shop_id]['sku_percent'][$coupon['coupon_id']][$sku_id]['coupon_percent'] / 10, 2);
                                }else{
                                    
                                    $coupon_lists[$shop_id]['sku_percent'][$coupon['coupon_id']][$sku_id]['coupon_percent'] = 1- $percent;
                                    $coupon_lists[$shop_id]['sku_percent'][$coupon['coupon_id']][$sku_id]['coupon_percent_amount'] = round($allCount - $money,2);
                                }
                            }
                        }
                    }
                } elseif ($coupon->coupon_type->range_type == 0) {//部分指定商品可用
                    $goods_list = [];
                    foreach ($coupon->coupon_type->goods as $coupon_goods) {
                        $goods_list[] = $coupon_goods['goods_id'];
                    }
                    $total_price = 0.00;
                    $all_goods_in_promotion = false;
                    $count_coupon_sku = [];
                    foreach ($sku_info as $sku) {
                        if (in_array($sku['goods_id'], $goods_list)) {
                            $all_goods_in_promotion = true;
                            $total_price += $sku['discount_price'] * $sku['num'];
                            $count_coupon_sku[] = $sku['sku_id'];
                            if (isset($sku['full_cut_percent_amount'])) {
                                
                                $total_price -= $sku['full_cut_percent_amount'];
                            }
                        }
                    }
                    
                    if ($coupon->coupon_type->at_least <= $total_price && $all_goods_in_promotion && $total_price > 0) {
                        $coupon['goods_limit'] = $goods_list;
                        $coupon_lists[$shop_id]['coupon_info'][$coupon['coupon_id']] = $coupon;

                        //计算每个sku的优惠金额比例
                        if ($coupon->coupon_type->coupon_genre == 1 || $coupon->coupon_type->coupon_genre == 2) {
                                $i = 0;
                          	$percent = 0;
                          	$money = 0;
                          	$length = count($count_coupon_sku);
                                foreach ($sku_info as $sku_id => $sku) {
                                if (in_array($sku['goods_id'], $goods_list)) {
                                   $i++;
                                    $coupon_lists[$shop_id]['sku_percent'][$coupon['coupon_id']][$sku_id]['sku_id'] = $sku_id;
                                    $coupon_lists[$shop_id]['sku_percent'][$coupon['coupon_id']][$sku_id]['coupon_percent'] = round(($sku['discount_price'] * $sku['num'] - $sku['full_cut_percent_amount']) / $total_price, 2);
                                    $coupon_lists[$shop_id]['sku_percent'][$coupon['coupon_id']][$sku_id]['coupon_percent_amount'] = round($coupon->coupon_type->money * $coupon_lists[$shop_id]['sku_percent'][$coupon['coupon_id']][$sku_id]['coupon_percent'], 2);
                                    if($i != $length){
                                        $percent += round(($sku['discount_price'] * $sku['num'] - $sku['full_cut_percent_amount']) / $total_price, 2);
                                        $money += round($coupon->coupon_type->money * $coupon_lists[$shop_id]['sku_percent'][$coupon['coupon_id']][$sku_id]['coupon_percent'], 2);
                                    }else{
                                        $coupon_lists[$shop_id]['sku_percent'][$coupon['coupon_id']][$sku_id]['coupon_percent'] = 1 - $percent;
                                        $coupon_lists[$shop_id]['sku_percent'][$coupon['coupon_id']][$sku_id]['coupon_percent_amount'] = round($coupon->coupon_type->money - $money,2);
                                    }
                                }
                            }
                        } elseif ($coupon->coupon_type->coupon_genre == 3) {
                                $i = 0;
                          	$percent = 0;
                          	$money = 0;
                                $allCount = round($total_price * (10-$coupon->coupon_type->discount)/10, 2);
                          	$length = count($count_coupon_sku);
                                foreach ($sku_info as $sku_id => $sku) {
                                if (in_array($sku['goods_id'], $goods_list)) {
                                   $i++;
                                    $coupon_lists[$shop_id]['sku_percent'][$coupon['coupon_id']][$sku_id]['sku_id'] = $sku_id;
                                    $coupon_lists[$shop_id]['sku_percent'][$coupon['coupon_id']][$sku_id]['coupon_percent'] = round(($sku['discount_price'] * $sku['num'] - $sku['full_cut_percent_amount']) / $total_price, 2);
                                    $coupon_lists[$shop_id]['sku_percent'][$coupon['coupon_id']][$sku_id]['coupon_percent_amount'] = round((10-$coupon->coupon_type->discount) * $total_price * $coupon_lists[$shop_id]['sku_percent'][$coupon['coupon_id']][$sku_id]['coupon_percent'] / 10, 2);
                                    if($i != $length){
                                        $percent += round(($sku['discount_price'] * $sku['num'] - $sku['full_cut_percent_amount']) / $total_price, 2);
                                        $money += round((10-$coupon->coupon_type->discount) * $total_price * $coupon_lists[$shop_id]['sku_percent'][$coupon['coupon_id']][$sku_id]['coupon_percent'] / 10, 2);
                                    }else{
                                        $coupon_lists[$shop_id]['sku_percent'][$coupon['coupon_id']][$sku_id]['coupon_percent'] = 1- $percent;
                                        $coupon_lists[$shop_id]['sku_percent'][$coupon['coupon_id']][$sku_id]['coupon_percent_amount'] = round($allCount - $money,2);
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
        }
        return $coupon_lists;
    }

    //查询每个店铺可用的优惠券列表
    public function get_shop_coupon_list($uid, $shop_id)
    {
        $time = time();
        $sql = "select a.*,b.* from `vsl_coupon` as a left join `vsl_coupon_type` as b on a.`coupon_type_id` = b.`coupon_type_id` where a.`uid` = $uid and a.`shop_id` = $shop_id and a.`state` = 0 and a.`start_time`<$time and a.`end_time`>$time";
        return Db::query($sql);
    }

    /**
     * 获取优惠券剩余数目
     *
     * @param int $coupon_type_id
     * @param int $uid
     *
     * @return int $rest
     */
    public function getRestCouponType($coupon_type_id, $uid = 0)
    {
        $coupon_type_model = new VslCouponTypeModel();
        $coupon_type_info = $coupon_type_model::get($coupon_type_id, ['coupons']);
        $rest = $coupon_type_info['count'] - count($coupon_type_info->coupons);
        if($coupon_type_info['count']==0)$rest = 10000;//无限领
        if ($rest <= 0)return 0;
        //没有uid 返回该优惠券的剩余数目;有uid 返回该uid用户可领取该优惠券的数目
        if (empty($uid) || $coupon_type_info['max_fetch'] == 0) {
            return $rest;
        } else {
            $u_rest = $coupon_type_info['max_fetch'] - $coupon_type_info->coupons()->where('uid', $uid)->count();
            if($u_rest <= 0) return 0;
            return ($u_rest > $rest) ? $rest : $u_rest;
        }
    }

    /**
     * 判读优惠券是否科领取，不可领取时返回0，可领取返回可领取数目
     *
     * @param int $coupon_type_id
     * @param int $uid
     * @param int $time
     *
     * @return int $rest
     */
    public function isCouponTypeReceivable($coupon_type_id, $uid, $time = 0)
    {
        $coupon_type_model = new VslCouponTypeModel();
        if (empty($time)) {
            $time = time();
        }
        $coupon_type_info = $coupon_type_model::get($coupon_type_id, ['coupons']);
        if($time < $coupon_type_info['start_receive_time']){
            return -1;//未开始
        }
        if($time > $coupon_type_info['end_receive_time']){
            return -2;//已过期
        }
        
        // 已经被领取、使用了全部数目
        $rest = $coupon_type_info['count'] - count($coupon_type_info->coupons);
        if($coupon_type_info['count']==0)$rest=10000;
        if ($rest <= 0) {
            return 0;
        }
        //没有uid 返回该优惠券的剩余数目;有uid 返回该uid用户可领取该优惠券的数目
        if (empty($uid) || $coupon_type_info['max_fetch'] == 0) {
            return $rest;
        } else {
            $u_rest = $coupon_type_info['max_fetch'] - $coupon_type_info->coupons()->where('uid', $uid)->count();
            return ($u_rest > $rest) ? $rest : $u_rest;
        }
    }

    /**
     * 判断领取的优惠券是否能使用
     * @param array $data
     *
     * @return false|true
     */
    public function isCouponUsable(array $data = [])
    {
//        $data = [
//            'shop' => ['shop_id' => 0],
//            'goods' => [
//                3 => [
//                    'goods_id' => 3,
//                    'price' => 9.9,
//                    'num' => 2
//                ],
//                12 => [
//                    'goods_id' => 12,
//                    'price' => 9.9,
//                    'num' => 2
//                ]
//            ],
//            'coupon' => ['coupon_id' => 664]
//        ];
        $coupon_model = new VslCouponModel();
        $coupon_info = $coupon_model::get($data['coupon']['coupon_id'], ['coupon_type']);

        //可能还需要uid,website_id,shop_id的判断,先不判断
        if (empty($coupon_info) || empty($coupon_info->coupon_type)) {
            //不存在
            return false;
        }
        if ($coupon_info->coupon_type->start_time > time() || $coupon_info->coupon_type->end_time < time()) {
            //过期
            return false;
        }

        //需要满一定金额才能使用
        if ($coupon_info->coupon_type->coupon_genre == 2 || $coupon_info->coupon_type->coupon_genre == 3) {

            //全场可用商品判断
            if ($coupon_info->coupon_type->range_type == 1) {
                $total_price = 0;
                foreach ($data['goods'] as $data_good) {
                    $total_price += $data_good['price'] * $data_good['num'];
                }
                if ($total_price < $coupon_info->coupon_type->at_least) {
                    //所有商品价格加起来不满足最低金额
                    return false;
                }
            } elseif ($coupon_info->coupon_type->range_type == 0) {
                if (empty($coupon_info->coupon_type->goods)) {
                    //设置部分商品可用却没选择商品
                    return false;
                }
                $total_price = 0;
                foreach ($coupon_info->coupon_type->goods as $good) {
                    if (!empty($data['goods'][$good->goods_id])) {
                        $total_price += $data['goods'][$good->goods_id]['price'] * $data['goods'][$good->goods_id]['num'];
                    }
                }
                if ($total_price < $coupon_info->coupon_type->at_least) {
                    //所有满足指定商品价格加起来不满足最低金额
                    return false;
                }
            } else {
                //不存在类型
                return false;
            }
        } elseif ($coupon_info->coupon_type->coupon_genre == 1) {
            // 无门槛 指定部分商品可用
            if ($coupon_info->coupon_type->range_type == 0) {
                $not_goods = true;
                foreach ($coupon_info->coupon_type->goods as $good) {
                    if (!empty($data['goods'][$good->goods_id])) {
                        $not_goods = false;
                        break;
                    }
                }
                if ($not_goods) {
                    // 不存在指定商品
                    return false;
                }
            }
        } else {
            //不存在类型
            return false;
        }

        return true;
    }

    /**
     * 用户获取优惠券
     * @param int|string $uid
     * @param int|string $coupon_type_id
     * @param int $get_type
     *
     * @return  int $result
     */
    public function userAchieveCoupon($uid, $coupon_type_id, $get_type)
    {
        $coupon = new VslCouponModel();
        $coupon_type = new VslCouponTypeModel();
        $coupon_type_detail = $coupon_type::get($coupon_type_id);
        //查询该优惠券总共领取了多少张
        $coupon_total_fetch = $coupon->where(['coupon_type_id'=>$coupon_type_id])->count();
        //查询该优惠券该用户领取了多少张
        $coupon_user_count = $coupon->where(['uid'=>$uid, 'coupon_type_id'=>$coupon_type_id])->count();
        //该优惠券每个用户最多可以领取多少张
        $max_fetch = $coupon_type_detail['max_fetch'];
        //该优惠券数量总额
        $counpon_total = $coupon_type_detail['count'];
        if ($coupon_type_detail) {
            if($coupon_total_fetch >= $counpon_total && $counpon_total!=0){
                return NO_COUPON;
            }
            if($coupon_user_count >= $max_fetch && $max_fetch!=0){
                return NO_COUPON;
            }
            $data = array(
                'uid' => $uid,
                'state' => 1,
                'get_type' => $get_type,
                'money' => $coupon_type_detail['money'],
                'fetch_time' => time(),
                'coupon_type_id' => $coupon_type_id,
                'coupon_code' => time() . rand(111, 999),
                'shop_id' => $coupon_type_detail->shop_id,
                'website_id' => $coupon_type_detail->website_id,
                'start_receive_time' => $coupon_type_detail->start_receive_time,
                'end_receive_time' => $coupon_type_detail->end_receive_time,
                'start_time' => $coupon_type_detail->start_time,
                'end_time' => $coupon_type_detail->end_time
            );
            $result = $coupon->save($data);
        } else {
            $result = NO_COUPON;
        }
        return $result;
    }
    
    /**
     * 领取优惠券
     */
    public function getUserReceive($uid,$coupon_type_id,$get_type,$state=1)
    {
        $condition['coupon_type_id'] = $coupon_type_id;
        $coupon_type = new VslCouponTypeModel();
        $info = $coupon_type::get($condition);
        if($info){
            $coupon = new VslCouponModel();
            $data = array(
                'uid' => $uid,
                'state' => $state,
                'get_type' => $get_type,
                'money' => $info['money'],
                'fetch_time' => time(),
                'coupon_type_id' => $coupon_type_id,
                'coupon_code' => time() . rand(111, 999),
                'shop_id' => $info->shop_id,
                'website_id' => $info->website_id,
                'start_receive_time' => $info->start_receive_time,
                'end_receive_time' => $info->end_receive_time,
                'start_time' => $info->start_time,
                'end_time' => $info->end_time
            );
            $result = $coupon->save($data);
        }else{
            $result = 0;
        }
        return $result;
    }
    
    /**
     * 领取优惠券/冻结改领取
     */
    public function getUserThaw($uid,$coupon_id)
    {
        $condition = [];
        $condition['uid'] = $uid;
        $condition['coupon_id'] = $coupon_id;
        $coupon = new VslCouponModel();
        $info = $coupon->getInfo($condition);
        if($info && $info['state']==-1){
            $result = $coupon->where($condition)->update(['state' => '1']);
        }else{
            $result = 0;
        }
        return $result;
    }

    /**
     * 获取商品优惠劵
     *
     * @param array $goods_id_array
     * @param int|string $uid
     *
     * @return array $coupon_list
     */
    public function getGoodsCoupon(array $goods_id_array, $uid)
    {
        $coupon_goods = new VslCouponGoodsModel();
        $coupon_type = new VslCouponTypeModel();
        $goods_model = new VslGoodsModel();

        $goods_info = $goods_model->getQuery(['goods_id' => ['IN', $goods_id_array]], 'shop_id', '');
        $shop_id_array = [];
        foreach ($goods_info as $k => $goods) {
            if (!in_array($goods['shop_id'], $shop_id_array)) {
                array_push($shop_id_array, $goods['shop_id']);
            }
        }

        // 获取全商品优惠劵
        $conditions = [
            'start_receive_time' => ['ELT', time()],
            'end_receive_time' => ['EGT', time()],
            'start_time' => ['ELT', time()],
            'end_time' => ['EGT', time()],
            'range_type' => 1,
            'is_fetch' => 1,
            'website_id' => $this->website_id,
        ];
        $whereOr['shop_id'] = ['IN', $shop_id_array, 'OR'];
//      商品所在店鋪优惠券 OR 全平台可用优惠券
//      AND (`shop_id` IN (0, 26) OR (`shop_id` = 0 AND `shop_range_type` = 2))
        $coupon_list = $coupon_type::all(function ($query) use ($conditions, $whereOr) {
            $whereOrAnd['shop_id'] = 0;
            $whereOrAnd['shop_range_type'] = 2;
            $query->where($conditions)->where(function ($q1) use ($whereOr, $whereOrAnd) {
                $q1->where($whereOr)->whereOr(function ($q2) use ($whereOrAnd) {
                    $q2->where($whereOrAnd);
                });
            });
        }, ['coupons']);
//        var_dump(Db::table('')->getLastSql());exit;
        if (!empty($coupon_list)) {
            foreach ($coupon_list as $k => $v) {
                //全部领取完了
                if ($v->count <= $v->coupons()->where(['state' => ['NEQ', 0]])->count() && $v->count!=0) {
                    unset($coupon_list[$k]);
                    continue;
                }
                //个人达到最高领取数目
                if ($v->max_fetch != 0 && ($v->max_fetch <= $v->coupons()->where('uid', $uid)->count()) && $v->max_fetch!=0) {
                    unset($coupon_list[$k]);
                    continue;
                }
                $coupon_list[$k]->receive_quantity = $v->coupons()->where(['state' => ['NEQ', 0]])->count();
            }
        }
        unset($conditions);

        // 通过商品id获取到优惠劵类型
        $coupon_goods_type_id_list = $coupon_goods->getQuery([
            'goods_id' => ['in', $goods_id_array]
        ], 'coupon_type_id', '');

        if ($coupon_goods_type_id_list) {
            $id = [];
            foreach ($coupon_goods_type_id_list as $k => $v) {
                $id[] = $v['coupon_type_id'];
            }
            $conditions = array(
                'coupon_type_id' => ['IN', $id],
                'start_receive_time' => ['ELT', time()],
                'end_receive_time' => ['EGT', time()],
                'start_time' => ['ELT', time()],
                'end_time' => ['EGT', time()],
                'range_type' => 0,
                'is_fetch' => 1,
                'website_id' => $this->website_id,
            );
            $coupon_list_again = $coupon_type::all($conditions, ['coupons']);

            if (!empty($coupon_list_again)) {
                foreach ($coupon_list_again as $k => $v) {
                    //全部领取完了
                    if ($v->count <= $v->coupons()->where(['state' => ['NEQ', 0]])->count() && $v->count!=0) {
                        unset($coupon_list_again[$k]);
                        continue;
                    }
                    //个人达到最高领取数目
                    if ($v->max_fetch != 0 && ($v->max_fetch <= $v->coupons()->where('uid', $uid)->count())) {
                        unset($coupon_list_again[$k]);
                        continue;
                    }
                    $coupon_list_again[$k]->receive_quantity = $v->coupons()->where(['state' => ['NEQ', 0]])->count();
                }
            }
            $coupon_list = array_merge($coupon_list, $coupon_list_again);
        }

        return $coupon_list;
    }

    public function saveCouponConfig($is_coupon)
    {
        $ConfigService = new AddonsConfigService();
        $coupon_info = $ConfigService->getAddonsConfig("coupontype");
        if (!empty($coupon_info)) {
            $res = $this->addons_config_module->save(['is_use' => $is_coupon, 'modify_time' => time()], [
                'website_id' => $this->website_id,
                'addons' => 'coupontype'
            ]);
        } else {
            $res = $ConfigService->addAddonsConfig('', '优惠券设置', $is_coupon, 'coupontype');
        }
        return $res;
    }
    
    /**
     * 获取优惠券商品列表
     */
    public function couponGoodsList($page_index = 1, $page_size = 0, $condition = [], $field = '*', $order = 'create_time desc',$group= [])
    {
        $coupon_type_model = new VslCouponTypeModel();
        $data = $coupon_type_model::get($condition['coupon_type_id']);
        if($data['shop_range_type']==1){
            $condition['vs.shop_id'] = $data['shop_id'];
        }
        if($data['range_type']==0){
            $coupon_goods = new VslCouponGoodsModel();
            $goods_list = $coupon_goods->getCouponGoodsList($page_index,$page_size,$condition,$field,$order,$group);
        }else{
            unset($condition['coupon_type_id']);
            $goods_server = new VslGoodsViewModel();
            $goods_list = $goods_server->wapGoods($page_index,$page_size,$condition,$field,$order,$group);
        }
        return $goods_list;
    }
}