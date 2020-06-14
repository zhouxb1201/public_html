<?php
namespace app\platform\controller;
use data\service\Addons as AddonsService;
use data\service\Order;
/**
 * 扩展模块控制器
 *
 * @author  www.vslai.com
 *        
 */
class Addonslist extends BaseController
{

    protected $addons;

    public function __construct()
    {
        $this->addons = new AddonsService();
        parent::__construct();
    }

    /**
     * 插件管理
     */
    public function addonsList()
    {
        if (request()->isAjax()) {
            $search_text = request()->post("search_text");
            $list = $this->addons->getModuleList($search_text);
            return $list;
        }
        return view($this->style . "Addons/addonsList");
    }
    /**
     * 增值服务
     */
    public function increment()
    {
        $addons_id = request()->get("addons_id");
        $type = request()->get("type");
        $list = $this->addons->getModuleInfo($addons_id);
        $info['cycle_price'] = $list['cycle_price']?json_decode(str_replace ("&quot;", "\"", $list['cycle_price'] ),true):'';
        $this->assign('module_info',$list);
        $this->assign('type',$type);
        $this->assign('cycle_price', $info['cycle_price']);
        return view($this->style . "Addons/increment");
    }
    /**
     * 立即订购
     */
    public function orderNow()
    {
        $time = request()->get("time");
        $addons_id = request()->get("addons_id");
        $addons_info= $this->addons->getModuleInfo($addons_id);
        $order_id = $this->addons->createOrder($addons_id,$time);
        $info['cycle_price'] = $addons_info['cycle_price']?json_decode(str_replace ("&quot;", "\"", $addons_info['cycle_price'] ),true):'';
        $this->assign('time',$time);
        $this->assign('order_id',$order_id);
        $this->assign('module_info',$addons_info);
        foreach ($info['cycle_price'] as $k=>$value){
            if($time==$value['cycle']){
                $pay_money = $value['price'];
                $this->assign('pay_money',$pay_money);
                $this->assign('price',$value['market_price']);
            }
        }
        return view($this->style . "Addons/orderNow");
    }
    /**
     * 订购列表
     */
    public function incrementOrderList()
    {
        if(request()->isPost()){
            $page_index = request()->post('page_index', 1);
            $order_status = request()->post('order_status','-1');
            $page_size = request()->post('page_size', PAGESIZE);
            $start_create_date = request()->post('start_create_date') == "" ? 0 : getTimeTurnTimeStamp(request()->post('start_create_date'));
            $end_create_date = request()->post('end_create_date') == "" ? 0 : getTimeTurnTimeStamp(request()->post('end_create_date'));
            $condition = [];
            if ($start_create_date) {
                $condition['create_time'][] = ['>=', $start_create_date];
            }
            if ($end_create_date) {
                $condition['create_time'][] = ['<=', $end_create_date];
            }
            if ($order_status>=0) {
                $condition['order_status'] = $order_status;
            }
            $condition['website_id'] = $this->website_id;
            $order_service = new Order();
            $list = $order_service->getIncrementOrderList($page_index, $page_size, $condition, 'create_time desc');
            return $list;
        }
        return view($this->style . "Addons/incrementOrderList");
    }
    /**
     * 订单详情
     */
    public function incrementOrderDetail()
    {
        $order_id = request()->get('order_id', '');
        $order_service = new Order();
        $list = $order_service->getIncrementOrderDetail($order_id);
        $this->assign('order_info',$list);
        return view($this->style . "Addons/orderDetail");
    }
    /**
     * 取消订单
     */
    public function cancelOrder()
    {
        $order_id = request()->post('order_id', '');
        $order_service = new Order();
        $res = $order_service->cancelOrder($order_id);
        return AjaxReturn($res);
    }
}