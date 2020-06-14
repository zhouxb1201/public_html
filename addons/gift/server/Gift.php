<?php

namespace addons\gift\server;

use data\model\AddonsConfigModel;
use data\service\BaseService;
use addons\gift\model\VslPromotionGiftModel;
use data\service\AddonsConfig as AddonsConfigService;
use data\model\AlbumPictureModel;
use addons\gift\model\VslMemberGiftModel;
use addons\giftvoucher\model\VslGiftVoucherModel;
use think\Db;

class Gift extends BaseService {

    public $addons_config_module;

    function __construct() {
        parent::__construct();
        $this->addons_config_module = new AddonsConfigModel();
    }

    /**
     * @param array $input
     * @return int
     */
    public function addGift(array $input) {
        $giftModel = new VslPromotionGiftModel();
        $data = array(
            'website_id' => $this->website_id,
            'shop_id' => $this->instance_id,
            'gift_name' => $input['gift_name'],
            'price' => $input['price'],
            'description' => $input['description'],
            'stock' => $input['stock'],
            'picture' => $input['picture'],
            'img_id_array' => $input['img_id_array'],
            'create_time' => time()
        );
        $giftModel->save($data);
        $giftId = $giftModel->promotion_gift_id;
        return $giftId;
    }

    /**
     * @param array $input
     * @return int
     */
    public function updateGift(array $input) {
        $giftModel = new VslPromotionGiftModel();
        $giftId = $input['gift_id'];
        if (!$giftId) {
            return;
        }
        $data = array(
            'website_id' => $this->website_id,
            'shop_id' => $this->instance_id,
            'gift_name' => $input['gift_name'],
            'price' => $input['price'],
            'description' => $input['description'],
            'stock' => $input['stock'],
            'picture' => $input['picture'],
            'img_id_array' => $input['img_id_array'],
            'modify_time' => time()
        );
        $retval = $giftModel->save($data, ['promotion_gift_id' => $giftId]);
        return $retval;
    }

    /**
     * 获取赠品列表
     * @param int|string $page_index
     * @param int|string $page_size
     * @param array $condition
     * @param string $fields
     *
     * @return array $list
     */
    public function giftList($page_index = 1, $page_size = 0, array $condition = []) {
        $giftModel = new VslPromotionGiftModel();
        $list = $giftModel->getGiftViewList($page_index, $page_size, $condition, 'vpg.create_time desc');
        $giftvoucher = getAddons('giftvoucher', $this->website_id, $this->instance_id);
        $fullcut = getAddons('fullcut', $this->website_id, $this->instance_id);
        if($list['data']){
            foreach ($list['data'] as $k => $v) {
                $giftvoucher_count = 0;
                $fullcut_count = 0;
                if($giftvoucher){
                    $vsl_voucher = new VslGiftVoucherModel();
                    $giftvoucher_count = $vsl_voucher->getVoucherViewCount(['gv.promotion_gift_id'=>$v['promotion_gift_id']]);
                }
                if($fullcut){
                    $sql = "select * from vsl_promotion_mansong_rule where `gift_id` = ".$v['promotion_gift_id'];
                    $result = Db::query($sql);
                    $fullcut_count = count($result);
                }
                if($giftvoucher_count>0 || $fullcut_count>0){
                    $list['data'][$k]['prompt'] = '该赠品已参加了活动（满减送、礼品券），删除后可能会造成活动数据丢失，请谨慎操作。';
                }else{
                    $list['data'][$k]['prompt'] = '删除赠品？';
                }
            } 
        }
        return $list;
    }

    /**
     * 获取赠品详情
     * @param int $gift_id
     * @return array $info
     */
    public function giftDetail($gift_id) {
        $giftModel = new VslPromotionGiftModel();
        $info = $giftModel->get($gift_id);
        // 查询图片表
        $goods_img = new AlbumPictureModel();
        $order = "instr('," . $info['img_id_array'] . ",',CONCAT(',',pic_id,','))"; // 根据 in里边的id 排序
        $goods_img_list = $goods_img->getQuery([
            'pic_id' => [
                "in",
                $info['img_id_array']
            ]
        ], 'pic_cover_big,pic_cover_mid,pic_cover_small,pic_id,pic_cover', $order);
        if (trim($info['img_id_array']) != "") {
            $img_temp_array = array();
            $img_array = explode(",", $info['img_id_array']);
            foreach ($img_array as $ki => $vi) {
                if (!empty($goods_img_list)) {
                    foreach ($goods_img_list as $t => $m) {
                        if ($m["pic_id"] == $vi) {
                            $img_temp_array[] = $m;
                        }
                    }
                }
            }
        }
        $goods_picture = $goods_img->get($info['picture']);
        $info["img_temp_array"] = $img_temp_array;
        $info['img_list'] = $goods_img_list;
        $info['picture_detail'] = $goods_picture;
        $vslMemberGiftModel = new VslMemberGiftModel();
        $info['sended'] = $vslMemberGiftModel->getSum(['promotion_gift_id' => $gift_id, 'website_id' => $this->website_id, 'shop_id' => $this->instance_id],'num');
        return $info;
    }
    
    /*
     * 删除赠品
     */
    public function deleteGift($gift_id) {
        if (!$gift_id) {
            return -1006;
        }
        $giftModel = new VslPromotionGiftModel();
        $gift = $giftModel->getInfo(['promotion_gift_id' => $gift_id, 'website_id' => $this->website_id]);
        if (!$gift) {
            return;
        }
        $retval = $giftModel->destroy($gift_id);
        return $retval;
    }
    
    /*
     * 赠品设置
     */
    public function saveGiftConfig($is_gift)
    {
        $ConfigService = new AddonsConfigService();
        $giftInfo = $ConfigService->getAddonsConfig("gift");
        if (!empty($giftInfo)) {
            $res = $this->addons_config_module->save(['is_use' => $is_gift, 'modify_time' => time()], [
                'website_id' => $this->website_id,
                'addons' => 'gift'
            ]);
        } else {
            $res = $ConfigService->addAddonsConfig('', '赠品设置', $is_gift, 'gift');
        }
        return $res;
    }
    
    /*
     * 赠品记录
     */
    public function giftRecord($page_index = 1, $page_size = 0, array $condition = []){
        $giftModel = new VslMemberGiftModel();
        $list = $giftModel->getMemberGiftViewList($page_index, $page_size, $condition, 'vmg.create_time desc');
        return $list;
    }
    
    /**
     * 增加赠品记录
     * @param array $input
     * @return int
     */
    public function addGiftRecord(array $input) {
        $memberGiftModel = new VslMemberGiftModel();
        if(!$input['promotion_gift_id']){
            return;
        }
        $gift_name = $this->getGiftNameByGiftId($input['promotion_gift_id']);
        $data = array(
            'website_id' => $this->website_id,
            'shop_id' => $this->instance_id,
            'uid' => $input['uid'],
            'type' => $input['type'],
            'num' => $input['num'],
            'no' => $input['no'],
            'promotion_gift_id' => $input['promotion_gift_id'],
            'gift_name' => $gift_name,
            'create_time' => time()
        );
        $result = $memberGiftModel->save($data);
        if($result){
            $giftModel = new VslPromotionGiftModel();
            $gift = $giftModel->getInfo(['promotion_gift_id' => $input['promotion_gift_id']],'stock,promotion_gift_id');
            if($gift){
                $nowStock = $gift['stock'] - $input['num'];
                $giftModel->save(['stock' => $nowStock > 0 ? $nowStock : 0],['promotion_gift_id' => $gift['promotion_gift_id']]);
            }
        }
        
        $giftId = $memberGiftModel->gift_id;
        return $giftId;
    }
    
    /*
     * 根据赠品id获取赠品名称
     */
    public function getGiftNameByGiftId($gift_id = 0){
        if(!$gift_id){
            return;
        }
        $giftModel = new VslPromotionGiftModel();
        $gift = $giftModel->getInfo(['promotion_gift_id' => $gift_id,'website_id' => $this->website_id,'shop_id' => $this->instance_id],'gift_name');
        if(!$gift || !$gift['gift_name']){
            return;
        }
        return $gift['gift_name'];
    }
}
