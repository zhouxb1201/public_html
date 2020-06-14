<?php
namespace app\cli\controller;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\wapapi\controller\Order;
use app\shop\controller\Order AS pcOrder;
use app\wapapi\controller\BaseController;

class SeckillOrderCreate extends Command {

    protected function configure(){
        $this->setName('seckill_order_create')->setDescription('秒杀订单后台创建');
    }
    /**
     * 启动服务端服务
     * @return \lib\crontab\IssServer
     */
    public function execute(Input $input,Output $output){
        while(1){
            $order_controller = new Order();
            $pc_order_controller = new pcOrder();
            $base_controller = new baseController();
            $redis = $base_controller->connectRedis();
            $redis_key = getcwd();
            $redis_order_data = $redis->lpop('vslai_seckill_order_'.$redis_key);
            $redis_pc_order_data = $redis->lpop('vslai_seckill_pc_order_'.$redis_key);
            //门店订单
            $redis_store_order_data = $redis->lpop('vslai_seckill_order_for_store_'.$redis_key);
            //如果没有队列订单则跳出循环
             if(!$redis_order_data && !$redis_pc_order_data && !$redis_store_order_data){
                 sleep(2);
                 continue;
             }
            if($redis_order_data){
                debugLog('==>executeseckillOrderCreate<==');
                $res = $order_controller->seckillOrderCreate($redis_order_data);
                if(!$res['status']){
                    //压回去队列
                    $redis->rpush('vslai_seckill_order_'.$redis_key, $redis_order_data);
                    /*//存入一个异步字符，用于通知客户端是否创建订单成功 第一种方案：等待该订单执行完毕
                    $redis->set($res['out_trade_no'],'fail');*/
                    continue;
                }
            }
            if($redis_pc_order_data){
                $res2 = $pc_order_controller->seckillOrderCreateByPc($redis_pc_order_data);
                if(!$res2['status']){
                    //压回去队列
                    $redis->rpush('vslai_seckill_pc_order_'.$redis_key, $redis_order_data);
                    continue;
                }
            }
            //门店秒杀订单
            $store_data = json_decode($redis_store_order_data, true);
            if($redis_store_order_data){
                $res3 = $order_controller->subStoreOrderCreate($store_data['order_data'], $store_data['return_data'], $store_data['post_data'], $store_data['calculate_result'], $store_data['payment_info']);
                if($res3['code'] == -1){
                    //压回去队列
                    $redis->rpush('vslai_seckill_order_for_store_'.$redis_key, $redis_store_order_data);
                    continue;
                }
            }
            /*else{
                //存入一个异步字符，用于通知客户端是否创建订单成功
                $redis->set($res['out_trade_no'],'success');
            }*/
        }
    }
}
?>