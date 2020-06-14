<?php

namespace addons\giftvoucher\server;

use addons\giftvoucher\model\VslGiftVoucherModel;
use addons\giftvoucher\model\VslGiftVoucherRecordsModel;
use addons\gift\server\Gift as GiftServer;
use data\service\BaseService;
use data\model\AddonsConfigModel;
use data\service\AddonsConfig;

class GiftVoucher extends BaseService
{
    public $addons_config_module;

    function __construct()
    {
        parent::__construct();
        $this->addons_config_module = new AddonsConfigModel();
    }

    /**
     * 获取礼品券列表
     * @param int|string $page_index
     * @param int|string $page_size
     * @param array $condition
     * @param string $order
     */
    public function getGiftVoucherList($page_index, $page_size, $condition, $order = 'create_time desc')
    {
        $vsl_voucher = new VslGiftVoucherModel();
        $list = $vsl_voucher->getVoucherViewList($page_index, $page_size, $condition, $order);
        if ($list['data']) {
            $records = new VslGiftVoucherRecordsModel();
            foreach ($list['data'] as $k => $v) {
                $list['data'][$k]['received'] = $records->where(['gift_voucher_id' => $v['gift_voucher_id']])->count();
                $list['data'][$k]['surplus'] = $v['count'] - $list['data'][$k]['received'];
                $list['data'][$k]['surplus'] = ($list['data'][$k]['surplus'] > 0) ? $list['data'][$k]['surplus'] : 0;
            }
        }
        return $list;
    }

    /**
     * @param array $input
     * @return int
     */
    public function addGiftVoucher($input)
    {
        $vsl_voucher = new VslGiftVoucherModel();
        $vsl_voucher->startTrans();
        try {
            $vsl_voucher->save($input);
            $vsl_voucher->commit();
            return 1;
        } catch (\Exception $e) {
            $vsl_voucher->rollback();
            return $e->getMessage();
        }
    }

    /**
     * @param array $input
     * @return int
     */
    public function updateGiftVoucher($input, $where)
    {
        $vsl_voucher = new VslGiftVoucherModel();
        $vsl_voucher->startTrans();
        try {
            $vsl_voucher->save($input, $where);
            $vsl_voucher->commit();
            return 1;
        } catch (\Exception $e) {
            $vsl_voucher->rollback();
            return $e->getMessage();
        }
    }

    /**
     * 删除礼品券
     * @return int 1
     */
    public function deleteGiftVoucher($condition)
    {

        $vsl_voucher = new VslGiftVoucherModel();
        $vsl_voucher->startTrans();
        try {
            $info = $vsl_voucher->alias('vgv')
                ->join('vsl_gift_voucher_records vgvr', 'vgvr.gift_voucher_id = vgv.gift_voucher_id', 'left')
                ->field('vgvr.record_id')
                ->where($condition)->find();
            if (count($info) == 0) {
                $records = new VslGiftVoucherRecordsModel();
                $records::destroy(['gift_voucher_id' => $condition['vgvr.gift_voucher_id']]);
                $vsl_voucher::destroy(['gift_voucher_id' => $condition['vgvr.gift_voucher_id']]);
                $vsl_voucher->commit();
                return 1;
            }
            return -1;
        } catch (\Exception $e) {
            $vsl_voucher->rollback();
            return $e->getMessage();
        }
    }

    /**
     * 获取礼品券详情
     */
    public function getGiftVoucherDetail($condition)
    {
        $voucher = new VslGiftVoucherModel();
        $info = $voucher->getVoucherDetail($condition);
        $record = new VslGiftVoucherRecordsModel();
        $info['receive_count'] = $record->getVoucherHistoryCount(['vgvr.gift_voucher_id' => $condition['gift_voucher_id'], 'vgvr.state' => ['<>', -1]]);
        $info['used_count'] = $record->getVoucherHistoryCount(['vgvr.gift_voucher_id' => $condition['gift_voucher_id'], 'vgvr.state' => 2]);
        $info['frozen_count'] = $record->getVoucherHistoryCount(['vgvr.gift_voucher_id' => $condition['gift_voucher_id'], 'vgvr.state' => -1]);
        return $info;
    }

    /**
     * 获取使用记录
     */
    public function getGiftVoucherHistory($page_index, $page_size, $where, $fields, $order)
    {
        $record = new VslGiftVoucherRecordsModel();
        $list = $record->getVoucherHistory($page_index, $page_size, $where, $fields, $order);
        return $list;
    }

    public function saveConfig($is_giftvoucher)
    {
        $AddonsConfig = new AddonsConfig();
        $info = $AddonsConfig->getAddonsConfig("giftvoucher");
        if (!empty($info)) {
            $res = $this->addons_config_module->save(['is_use' => $is_giftvoucher, 'modify_time' => time()], [
                'website_id' => $this->website_id,
                'addons' => 'giftvoucher'
            ]);
        } else {
            $res = $AddonsConfig->addAddonsConfig('', '礼品券设置', $is_giftvoucher, 'giftvoucher');
        }
        return $res;
    }

    /**
     * 领取礼品券
     */
    public function getUserReceive($uid, $gift_voucher_id, $get_type, $state = 1)
    {
        $voucher = new VslGiftVoucherModel();
        $info = $voucher::get(['gift_voucher_id' => $gift_voucher_id]);
        if ($info) {
            $record = new VslGiftVoucherRecordsModel();
            $record->startTrans();
            try {
                $code = 'C' . rand(100000, 999999) . rand(10000, 99999);
                $url = __URL('clerk/verify/gift/' . $code);
                $data = array(
                    'shop_id' => $info['shop_id'],
                    'gift_voucher_id' => $gift_voucher_id,
                    'gift_voucher_code' => $code,
                    'uid' => $uid,
                    'fetch_time' => time(),
                    'state' => $state,
                    'get_type' => $get_type,
                    'website_id' => $info['website_id']
                );
                $result = $record->save($data);
                if ($result > 0) {
                    $qrcode = getQRcode($url, 'upload/' . $this->website_id . '/' . $this->instance_id . '/gift_voucher_qrcode', 'gift_voucher_qrcode_' . $result);
                    $record->save(['gift_voucher_codeImg' => $qrcode], ['record_id' => $result]);
                    //赠品
                    if ($state == 1) {
                        $gift_server = new GiftServer();
                        $input = [];
                        $input['uid'] = $uid;
                        $input['type'] = 3;
                        $input['num'] = 1;
                        $input['no'] = 0;
                        $input['promotion_gift_id'] = $info['promotion_gift_id'];
                        $res = $gift_server->addGiftRecord($input);
                        if ($res) {
                            $record->commit();
                            return $result;
                        }
                    } else {
                        $record->commit();
                        return $result;
                    }
                }
                return 0;
            } catch (\Exception $e) {
                $record->rollback();
                return $e->getMessage();
            }
        } else {
            return 0;
        }
    }

    /**
     * 领取礼品券/冻结改领取
     */
    public function getUserThaw($uid, $record_id)
    {
        $condition = [];
        $condition['uid'] = $uid;
        $condition['record_id'] = $record_id;
        $record = new VslGiftVoucherRecordsModel();
        $info = $record->getInfo($condition);
        if ($info && $info['state'] == -1) {
            $record->startTrans();
            try {
                $result = $record->where($condition)->update(['state' => '1']);
                if ($result) {
                    $vsl_voucher = new VslGiftVoucherModel();
                    $voucher_info = $vsl_voucher::get(['gift_voucher_id' => $info['gift_voucher_id']]);
                    $gift_server = new GiftServer();
                    $input = [];
                    $input['uid'] = $uid;
                    $input['type'] = 3;
                    $input['num'] = 1;
                    $input['no'] = 0;
                    $input['promotion_gift_id'] = $voucher_info['promotion_gift_id'];
                    $res = $gift_server->addGiftRecord($input);
                    if ($res) {
                        $record->commit();
                        return 1;
                    }
                }
            } catch (\Exception $e) {
                $record->rollback();
                return $e->getMessage();
            }
        } else {
            return 0;
        }

    }

    /**
     * 获取礼品券剩余数目
     *
     */
    public function getGiftVoucherType($gift_voucher_id, $uid = 0)
    {
        $voucher = new VslGiftVoucherModel();
        $info = $voucher::get(['gift_voucher_id' => $gift_voucher_id]);
        $record = new VslGiftVoucherRecordsModel();
        $where['vgv.gift_voucher_id'] = $gift_voucher_id;
        $recordCount = $record->getVoucherHistoryCount($where);//已领取总数目
        $surplus = $info['count'] - $recordCount;
        if ($info['count'] == 0) $surplus = 10000;//无限领
        //赠品是否开启
        if (!getAddons('gift', $info['website_id'], $info['shop_id'])) return 0;
        if ($surplus <= 0) return 0;
        if (empty($uid) || $info['max_fetch'] == 0) {
            return $surplus;
        } else {
            $where['vgvr.uid'] = $uid;
            $userRecordCount = $record->getVoucherHistoryCount($where);//用户已领取总数目
            $userSurplus = $info['max_fetch'] - $userRecordCount;
            if($userSurplus <= 0) return 0;
            return ($userSurplus > $surplus) ? $surplus : $userSurplus;
        }
    }

    /**
     * 判断礼品券是否可领取，可领取返回可领取数目
     *
     */
    public function isGiftVoucherReceive($condition, $uid = null)
    {
        $time = time();
        $voucher = new VslGiftVoucherModel();
        $info = $voucher::get($condition);
        $record = new VslGiftVoucherRecordsModel();
        $where['vgv.gift_voucher_id'] = $condition['gift_voucher_id'];
        $where['vgv.website_id'] = $condition['website_id'];
        $recordCount = $record->getVoucherHistoryCount($where);
        $where['vgvr.uid'] = $uid ?: $this->uid;
        $userRecordCount = $record->getVoucherHistoryCount($where);
        //赠品是否开启
        if (!getAddons('gift', $info['website_id'], $info['shop_id'])) return 0;
        if ($time < $info['start_receive_time']) {
            return -1;//未开始
        }
        if ($time > $info['end_receive_time']) {
            return -2;//已过期
        }
        if ($info['count'] == 0 && $info['max_fetch'] == 0) {
            return 10000;
        }
        if ($info['count'] == 0 && $info['max_fetch'] > 0) {
            $rest = $info['max_fetch'] - $userRecordCount;
            return $rest;//0已领取
        }
        $rest = $info['count'] - $recordCount;
        if ($info['count'] > 0 && $info['max_fetch'] == 0) {
            return $rest;
        }
        if ($info['count'] > 0 && $info['max_fetch'] > 0) {
            if ($info['max_fetch'] > $userRecordCount) {
                $rest2 = $info['max_fetch'] - $userRecordCount;
                if ($rest >= $rest2) {
                    return $rest2;
                } else {
                    $rest3 = $rest2 - $rest;
                    return $rest3;
                }
            } else {
                return 0;
            }
        }
    }

    /**
     * 使用礼品券
     */
    public function getUserUse($gift_voucher_code, $instance_id, $store_id, $assistant_id)
    {
        $result = 0;
        $condition['gift_voucher_code'] = $gift_voucher_code;
        $condition['website_id'] = $this->website_id;
        $record = new VslGiftVoucherRecordsModel();
        $info = $record::get($condition);
        if ($info && $info['shop_id'] == $instance_id) {
            $result = $this->isGiftVoucherUse($gift_voucher_code);
            if ($result > 0) {
                $data = array(
                    'state' => 2,
                    'use_time' => time(),
                    'store_id' => $store_id,
                    'assistant_id' => $assistant_id,
                );
                $result = $record->save($data, $condition);
            }
        }
        return $result;
    }

    /**
     * 判读礼品券是否可使用，未到时间使用时返回-1，已过期使用时返回-2，已使用时返回-3，可使用返回1
     *
     */
    public function isGiftVoucherUse($gift_voucher_code)
    {
        $time = time();
        $record = new VslGiftVoucherRecordsModel();
        $info = $this->getUserGiftvoucherInfo(0, $gift_voucher_code);
        if ($time < $info['start_time']) {
            return -1;
        }
        if ($time >= $info['end_time']) {
            return -2;
        }
        if ($info['state'] == 2) {
            return -3;
        }
        if ($info['state'] == 1) {
            return 1;
        }
    }

    /**
     * 查询当前会员礼品券列表
     * @param int $state 1:未使用,2:已使用,3:已过期
     * @param int $page_index
     * @param int $page_size
     */
    public function getUserGiftVoucher($state, $page_index, $page_size)
    {
        $where['vgvr.uid'] = $this->uid;
        $where['vgvr.website_id'] = $this->website_id;
        if ($state == 3) {
            $condition['state'] = ['neq', 2];
            $where['vgv.end_time'] = ['elt', time()];
        } else {
            $where['vgvr.state'] = $state;
            $where['vgv.end_time'] = ['egt', time()];
        }
        $fields = 'vgvr.*,vgv.start_time,vgv.end_time,su.user_tel,su.nick_name,vs.shop_name,vpg.gift_name, sap.pic_cover_mid,sap.pic_cover_big,vpg.price,vgv.giftvoucher_name';
        $record = new VslGiftVoucherRecordsModel();
        $voucher_list = $record->getVoucherHistory($page_index, $page_size, $where, $fields, 'vgvr.use_time desc');
        $list = [];
        if (!empty($voucher_list['data'])) {
            foreach ($voucher_list['data'] as $k => $v) {
                if ($v['state'] == 1) {
                    $list['data'][$k]['state_name'] = '未使用';
                } elseif ($v['state'] == 2) {
                    $list['data'][$k]['state_name'] = '已使用';
                } elseif ($v['end_time'] <= time()) {
                    $list['data'][$k]['state_name'] = '已过期';
                }
                $list['data'][$k]['state'] = $state ? $state : $v['state'];
                $list['data'][$k]['start_time'] = $v['start_time'];
                $list['data'][$k]['end_time'] = $v['end_time'];
                $list['data'][$k]['record_id'] = $v['record_id'];
                $list['data'][$k]['shop_id'] = $v['shop_id'];
                $list['data'][$k]['pic_cover_mid'] = __IMG($v['pic_cover_mid']);
                $list['data'][$k]['pic_cover_big'] = __IMG($v['pic_cover_big']);
                $list['data'][$k]['gift_name'] = $v['gift_name'];
                $list['data'][$k]['giftvoucher_name'] = $v['giftvoucher_name'];
            }
        } else {
            $list['data'] = [];
        }
        $list['total_count'] = $voucher_list['total_count'];
        $list['page_count'] = $voucher_list['page_count'];
        return $list;
    }

    /**
     * 查询当前会员礼品券详情
     */
    public function getUserGiftvoucherInfo($record_id, $gift_voucher_code)
    {
        if ($record_id > 0) {
            $condition['record_id'] = $record_id;
            $condition['uid'] = $this->uid;
        } else {
            $condition['gift_voucher_code'] = $gift_voucher_code;
        }
        $condition['website_id'] = $this->website_id;
        $record = new VslGiftVoucherRecordsModel();
        $info = $record->getVoucherHistoryDetail($condition);
        $detail = [];
        if (!empty($info)) {
            $detail['record_id'] = $info['record_id'];
            $detail['shop_id'] = $info['shop_id'];
            $detail['gift_voucher_code'] = $info['gift_voucher_code'];
            $detail['state'] = $info['state'];
            if ($info['state'] == 1) $detail['state_name'] = '未使用';
            if ($info['state'] == 2) $detail['state_name'] = '已使用';
            if ($info['info']['end_time'] <= time()) {
                $detail['state'] = 3;
                $detail['state_name'] = '已过期';
            }
            $detail['desc'] = $info['info']['desc'];
            $detail['start_time'] = $info['info']['start_time'];
            $detail['end_time'] = $info['info']['end_time'];
            $detail['gift_name'] = $info['info']['gift_name'];
            $detail['giftvoucher_name'] = $info['info']['giftvoucher_name'];
            $detail['pic_cover_mid'] = __IMG($info['info']['pic_cover_mid']);
            $detail['pic_cover_big'] = __IMG($info['info']['pic_cover_big']);
            $detail['gift_voucher_codeImg'] = __IMG($info['gift_voucher_codeImg']);
        }
        return $detail;
    }
}