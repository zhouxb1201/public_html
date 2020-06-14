<?php
namespace app\shop\controller;
use addons\taskcenter\model\VslGeneralPosterModel;
use data\service\Events;
use think\Controller;
use think\Cache;
use data\model\websiteModel;
use addons\shop\model\VslShopModel;
use \think\Session as Session;
use think\Log;
\think\Loader::addNamespace('data', 'data/');
/**
 * 执行定时任务
 *
 * @author  www.vslai.com
 *
 */
class Task extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 加载执行任务
     */
    public function load_task()
    {
        Log::write("定时任务------------------------------------" . date('Y-m-d H:i:s',time()));
        $this->autoTask();
        $this->minutesTask();
        $this->hoursTask();
        $this->twoHoursTask();
        $this->daysTask();
        $this->wholetimeTask();
        echo 'ok';
    }

    /**
     * 加载执行任务2
     */
    public function load_task_two()
    {
        Log::write("定时任务 two------------------------------------" . date('Y-m-d H:i:s',time()));
        $this->minutesTaskCalculate();
        $this->hourlyTask();//更改商品促销类型字段
        echo 'ok';
    }

    /**
     * 用户任务发放奖励
     */
    public function load_task_four()
    {
        Log::write("定时任务 four------------------------------------" . date('Y-m-d H:i:s',time()));
        $event = new Events();
        $website = new websiteModel();
        $website_id = $website->Query([],'website_id');
        foreach ($website_id as $k=>$v){
            $event->userTaskRewards($v);
        }
        echo 'ok';
    }

    /**
     * 加载执行任务5 单独独立出来用于计算分销分红
     */
    public function load_task_five()
    {
        // Log::write("定时任务 five------------------------------------" . date('Y-m-d H:i:s',time()));
        $event = new Events();
        $website = new websiteModel();
        $website_id = $website->Query([],'website_id');
        foreach ($website_id as $k=>$v){
            $event->orderCalculate($v);
        }
        echo 'ok';
    }
    /*
     * 每分钟检查主播的直播间是否还差10分钟开播。
     * **/
    public function checkLiveCountDown()
    {
        $website = new WebSiteModel();
        $event = new Events();
        $website_id = $website->Query([], 'website_id');
        foreach($website_id as $k=>$v){
            $cond['predict_end_time'] =[
                ['<=', time() + 10*60],
                ['>',time()]
            ];
            $cond['status'] = [
                ['eq', 1],
            ];
            $cond['website_id'] = $v;
            $cond['has_remind'] = 0;
            $event->__checkLiveCountDown($cond, $v);
        }
    }
    /*
     * 每分钟检查是否有直播间断开的
     * **/
    public function actDisconnectLiveStatus()
    {
        $website = new WebSiteModel();
        $event = new Events();
        $website_id = $website->Query([], 'website_id');
        foreach($website_id as $k=>$v){
            $event->__actDisconnectLiveStatus($v);
        }
    }
    /*
     * 已经禁播的主播判断时间是否已经到了解禁时间，自动解禁
     * **/
    public function unforbidAnchor()
    {
        $website = new WebSiteModel();//
        $event = new Events();
        $website_id = $website->Query([], 'website_id');
        foreach($website_id as $k=>$v){
            $event->__unforbidAnchor($v);
        }
    }

    public function unlockOrderCalcu()
    {
        $this->unlock('orders_complete_line');//redis分布式锁，防止并发。
        echo '解锁成功!';
    }
    /**
     * 加载执行任务3
     */
    public function load_task_three()
    {
        // Log::write("定时任务 five------------------------------------" . date('Y-m-d H:i:s',time()));
        $event = new Events();
        $website = new websiteModel();
        $website_id = $website->Query([],'website_id');
        foreach ($website_id as $k=>$v){
            $event->groupShoppingRecordClose($v);//未成团订单自动关闭
        }
        echo 'ok';
    }

    /**
     * 立即执行事件
     */
    public function autoTask(){
        $event = new Events();
        $event->mansongOperation();
        $event->discountOperation();
        $website = new websiteModel();
        $website_id = $website->Query([],'website_id');
        foreach ($website_id as $k=>$v) {
            $event->autoGrantGlobalBonus($v);
            $event->autoGrantAreaBonus($v);
            $event->autoGrantTeamBonus($v);
        }
    }

    /**
     * 每分钟执行事件
     */
    public function minutesTaskCalculate(){
        $time = time()-60;
        $cache = Cache::get("vsl_minutes_cal_task");
        if(!empty($cache) && $time<$cache)
        {
            return 1;
        }else{
            $event = new Events();
            $website = new websiteModel();
            $website_id = $website->Query([],'website_id');
            foreach ($website_id as $k=>$v){
                $event->festivalCare($v);
                $event->autoDownMicLevel($v);
            }
            Cache::set("vsl_minutes_cal_task", time());
            return 1;
        }
    }

    /**
     * 每分钟执行事件
     */
    public function minutesTask(){
        $time = time()-60;
        $cache = Cache::get("vsl_minutes_task");
        if(!empty($cache) && $time<$cache)
        {
            return 1;
        }else{
            $event = new Events();
            $website = new websiteModel();
            $website_id = $website->Query([],'website_id');
            foreach ($website_id as $k=>$v){
                $event->channelOrdersClose($v);//渠道商订单关闭
                $event->incrementOrdersClose($v);//增值应用订单关闭
                $event->ordersClose($v, 1);
                $event->ordersComplete($v);
                $event->autoDeilvery($v);
                $event->ordersCloseGroup($v);
//                $event->presell_order_close($v);
                $event->updateSeckillUncheckGoodsPromotionType($v);//将忘记审核的已进行秒杀商品去掉活动类型
            }
            Cache::set("vsl_minutes_task", time());
            return 1;
        }
    }
    /**
     * 每小时执行事件
     */
    public function hoursTask(){
        $time = time()-3600;
        $cache = Cache::get("vsl_hours_task");
        if(!empty($cache) && $time<$cache)
        {
            return 1;
        }else{
            $event = new Events();
            $website = new websiteModel();
            $website_id = $website->Query([],'website_id');
            foreach ($website_id as $k=>$v){
                $event->autoDeilvery($v);
                //秒杀结束修改商品promotion_type字段
                $event->updateSeckillGoodsPromotionType($v);
                //砍价结束修改商品promotion_type字段
                $event->updatebargainGoodsPromotionType($v);
                //限时折扣结束修改字段
                $event->updateDiscountGoodsPromotionType($v);
                //预售结束修改字段
                $event->updatePresellGoodsPromotionType($v);
            }
            Cache::set("vsl_hours_task", time());
            return 1;
        }
    }
    /**
     * 整点执行
     */
    public function hourlyTask()
    {
        $time = time()-3600;
        $cache = Cache::get("vsl_hourly_task");
        if(!empty($cache) && $time<$cache)
        {
            return 1;
        }else{

            $event = new Events();
            $website = new websiteModel();
            $website_id = $website->Query([],'website_id');
            foreach ($website_id as $k=>$v){
                //分销、渠道商升降级
                $event->autoDownDistributorLevel($v);
                $event->autoDownGlobalAgentLevel($v);
                $event->autoDownAreaAgentLevel($v);
                $event->autoDownTeamAgentLevel($v);
                $event->autoDownChannelAgentLevel($v);
            }
            Cache::set("vsl_hourly_task", strtotime(date('Y-m-d H:00:00')));
            return 1;
        }
    }

    /**
     * 每两小时执行一次
     */
    public function twoHoursTask()
    {
        $time = time() - 7200;
        $cache = Cache::get("vsl_tow_hours_task");
        if (!empty($cache) && $time < $cache ) {
            return 1;
        } else {
            $event = new Events();
            $website = new websiteModel();
            $website_id = $website->Query([], 'website_id');
            foreach ($website_id as $k => $v) {
                $event->refreshAuthAccessToken($v);
            }
            Cache::set("vsl_tow_hours_task", time());
            return 1;
        }
    }

    /**
     * 每天执行事件
     */
    public function daysTask(){
        $time = time();
        $start_cache_time = strtotime(date('Y-m-d 03:00:00'));
        $end_cache_time = strtotime(date('Y-m-d 04:00:00'));
        if(($time>=$start_cache_time) && ($time <= $end_cache_time))
        {
            $event = new Events();
            $website = new websiteModel();
            $website_id = $website->Query([],'website_id');
            foreach ($website_id as $k=>$v){
                $event->reSendRedPack($v);
                $event->sendMail($v);
            }
            return 1;
        }
    }

    /**
     * 每天整点执行事件
     */
    public function wholetimeTask(){
        $time = date('Y-m-d',time());
        $cache = Cache::get("vsl_wholetime_task");
        if(empty($cache) || $time!=date('Y-m-d',$cache))
        {
            $event = new Events();
            $website = new websiteModel();
            $website_id = $website->Query([],'website_id');
            foreach ($website_id as $k=>$v){
                $event->smashEgg($v);
                $event->wheelSurf($v);
                $event->scratchCard($v);
                $event->memberPrize($v);
                $event->payGift($v);
                $event->followGift($v);
            }
            Cache::set("vsl_wholetime_task", strtotime(date('Y-m-d',time())));
            return 1;
        }else{
            return 1;
        }
    }
}
