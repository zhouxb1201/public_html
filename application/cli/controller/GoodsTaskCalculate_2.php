<?php

namespace app\cli\controller;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use addons\goodhelper\model\VslGoodsHelpModel;
use addons\goodhelper\server\GoodHelper;

class GoodsTaskCalculate_2 extends Command {

    protected function configure() {
        $this->setName('goods_task_calculate_2')->setDescription('商品队列导入2');
    }

    /**
     * 启动服务端服务
     * @return \lib\crontab\IssServer
     */
    public function execute(Input $input, Output $output) {
        //商品导入代码
        while (1) {
            try {
                //status 0未执行，1成功 2失败 3等待中 4处理中 5部分完成
                $goodsHelp = new VslGoodsHelpModel();
                $firstData = $goodsHelp->getQuery(['status' => 3], '*', 'create_time asc');

                if (!$firstData) {
                    sleep(2);
                    continue;
                }
                foreach ($firstData as $val) {
                    $goodsHelpServer = new GoodHelper();
                    $condition = ['help_id' => $val['help_id']];
                    $goodsHelpServer->updateGoodsHelpInfo(['status' => 4], $condition);
                    //执行解压，上传
                    $res = $goodsHelpServer->addGoodsByXls($val['file_name'], $val['zip_name'], $val['add_type'], $val['type'], $val['shop_id'], $val['website_id']);
                    if ($res['code'] == 0) {//失败
                        $goodsHelpServer->updateGoodsHelpInfo(['status' => 2, 'error_excel_path' => $res['data'], 'error_info' => $res['message']] , $condition);
                    }else if ($res['code'] == 2) {//部分成功
                        $goodsHelpServer->updateGoodsHelpInfo(['status' => 5, 'error_excel_path' => $res['data'], 'error_info' => $res['message']], $condition);
                    }else if ($res['code'] == 1) {//成功
                        $goodsHelpServer->updateGoodsHelpInfo(['status' => 1], $condition);
//                        $goodsHelp->delData(['help_id' => $val['help_id']]);//成功的删除
                    }

//                    if ($res['code'] != 1) {
//                        $goodsHelp->save(['error_info' => $res['message'], 'status' => 2], ['help_id' => $val['help_id']]);
//                        continue;
//                    }
//                    $goodsHelp->delData(['help_id' => $val['help_id']]);
                }
            } catch (\Exception $e) {
                $msg = 'GoodsTaskCalculate_1:'.$e->getMessage();
            }
        }
    }
}

?>