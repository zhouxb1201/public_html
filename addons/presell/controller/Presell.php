<?php
namespace addons\presell\controller;

use data\model\VslGoodsModel;
use data\model\VslOrderModel;
use data\model\VslPresellModel;
use data\model\VslPresellGoodsModel;
use addons\presell\service\Presell as PresellService;
use addons\presell\Presell as basePresell;
use data\service\Goods;
class Presell extends basePresell
{
    public function __construct()
    {
        parent::__construct();
    }

    public function presell_list($page_index,$page_size,$condition)
    {
        $presell = new VslPresellModel();
        $data = $presell->pageQuery($page_index, $page_size,$condition,'id desc','*');
        return $data;
    }


    //添加预售
    public function add_presell($base_data,$sku_data='')
    {
        $presell = new VslPresellModel();
        $presell->startTrans();
        try{
            $id = $presell->save($base_data);
            if(!empty($sku_data)){
                foreach ($sku_data as $k=>$v){
                    $presell_goods = new VslPresellGoodsModel();
                    $data['goods_id'] = $base_data['goods_id'];
                    $data['sku_id'] = $k;
                    $data['max_buy'] = $v['max_buy'];
                    $data['first_money'] = $v['first_money'];
                    $data['all_money'] = $v['all_money'];
                    $data['presell_num'] = $v['presell_num'];
                    $data['vr_num'] = $v['vr_num'];
                    $data['presell_id'] = $id;
                    $data['start_time'] = $base_data['start_time'];
                    $data['end_time'] = $base_data['end_time'];
                    $presell_goods->save($data);
                }
            }
            $goods = new VslGoodsModel();
            //设置活动类型
            $goods->save(['promotion_type'=>'3'],['goods_id'=>$base_data['goods_id'], 'website_id' => $this->website_id]);
            $presell->commit();
            return 1;
        }catch(\Exception $e){
            $presell->rollback();
            return $e->getMessage();
        }
    }



    //编辑
    public function update_presell($base_data,$sku_data='',$condition)
    {
        $presell = new VslPresellModel();
        $presell->startTrans();
        try{
            if(!empty($sku_data)){
                foreach ($sku_data as $k=>$v){
                    $presell_goods = new VslPresellGoodsModel();
                    $data['goods_id'] = $base_data['goods_id'];
                    $data['sku_id'] = $k;
                    $data['max_buy'] = $v['max_buy'];
                    $data['first_money'] = $v['first_money'];
                    $data['all_money'] = $v['all_money'];
                    $data['presell_num'] = $v['presell_num'];
                    $data['vr_num'] = $v['vr_num'];
                    $where['presell_goods_id'] = $v['presell_goods_id'];
                    $presell_goods->save($data,$where);
                }
            }
            $presell->save($base_data,$condition);
            $goods = new VslGoodsModel();
            //设置活动类型
            $goods->save(['promotion_type'=>'3'],['goods_id'=>$base_data['goods_id'], 'website_id' => $this->website_id]);
            $presell->commit();
            return 1;

        }catch(\Exception $e){
            return ['code' => -1, 'message' => $e->getMessage()];
        }
    }


    public function getSkuList(){

        $goods_id = request()->post('goods_id',0);
        $presell_service = new PresellService();

        return $skuList;
    }


    //删除预售
    public function del_presell(){

        $id = request()->post('id');
        $presell = new VslPresellModel();
        $presell_goods = new VslPresellGoodsModel();
        $condition['id'] = $id;
        //恢复商品活动类型
        $presell_goods_id = $presell->getInfo(['id'=>$id],'');
        $result = $presell->delData($condition);
        $result2 = $presell_goods->delData(['presell_id'=>$id]);
        $this->update_goods_promotion($presell_goods_id['goods_id']);
        return AjaxReturn($result);

    }

    //关闭预售
    public function close_presell()
    {
        $id = request()->post('id');
        $presell = new VslPresellModel();
        $order_mdl = new VslOrderModel();
        $condition_order['presell_id'] = $id;
        $condition_order['order_status'] = ['in', ['-1', '0', '1', '2', '3']];
        //判断当前这档活动是否还有未完成的订单，如果有则不能删除
        $is_delete_presell = $order_mdl->alias('o')->where($condition_order)->select();
        if($is_delete_presell){
            return ['code'=>-1, 'message'=>'当前活动包含未完成的订单，暂不能删除'];
        }
        $data['status'] = 3;
        $condition['id'] = $id;
        $result = $presell->save($data,$condition);
        //恢复商品活动类型
        $presell_goods_id = $presell->getInfo(['id'=>$id],'');
        $this->update_goods_promotion($presell_goods_id['goods_id']);
        return AjaxReturn($result);

    }


    //保存设置
    public function save_presell_config()
    {
        try {
            $post_data = request()->post();
            $is_presell = $post_data['is_presell'];
            $addons_config_model = new AddonsConfigModel();
            $group_shopping_info = $addons_config_model::get(['website_id' => $this->website_id, 'addons' => 'presell']);
            if (!empty($group_shopping_info)) {
                $res = $addons_config_model->save(
                    [
                        'is_use' => $is_presell,
                        'modify_time' => time(),
                        'value' => json_encode($post_data, JSON_UNESCAPED_UNICODE)
                    ],
                    [
                        'website_id' => $this->website_id,
                        'addons' => parent::$addons_name
                    ]
                );
            } else {
                $data['is_use'] = $is_presell;
                $data['value'] = json_encode($post_data, JSON_UNESCAPED_UNICODE);
                $data['desc'] = '预售设置';
                $data['create_time'] = time();
                $data['addons'] = parent::$addons_name;
                $data['website_id'] = $this->website_id;
                $res = $addons_config_model->save($data);
            }
            setAddons('presell', $this->website_id, $this->instance_id);
            setAddons('presell', $this->website_id, $this->instance_id, true);
            return ['code' => $res, 'message' => '修改成功'];
        } catch (\Exception $e) {
            return ['code' => -1, 'message' => $e->getMessage()];
        }
    }

    //恢复商品promotion
    public function update_goods_promotion($goods_id){
        $goods_mdl = new VslGoodsModel();
        $goods_mdl->save(['promotion_type'=>0],['goods_id'=>$goods_id, 'promotion_type'=>3, 'website_id' => $this->website_id]);
    }

    //获取商品的规格信息
    public function get_sku_list(){

        $id = $_REQUEST['goods_id'];
        $presell_id = $_REQUEST['presell_id'];
        $presell = new PresellService();
        $list = $presell->get_sku_info($id,$presell_id);

        ob_clean();
        echo json_encode($list);exit;

    }


    /**
     * 预购商品选择
     */
    public function presellGoodsList()
    {
        if (request()->post('page_index')) {
            $index = request()->post('page_index', 1);
            $search_text = request()->post('search_text');
            if ($search_text) {
                $condition['ng.goods_name'] = ['LIKE', '%' . $search_text . '%'];
            }
            $condition['ng.website_id'] = $this->website_id;
            $condition['ng.state'] = 1;
            $condition['ng.shop_id'] = $this->instance_id;
            $condition['ng.goods_type'] = ['<>',4];
            $goods = new Goods();
            $list = $goods->getModalGoodsList($index, $condition, '');
            return $list;
        }
        $this->fetch('template/' . $this->module . '/groupShoppingGoodsDialog');
    }
}