<?php
namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 商城金额统计
 * @author  www.vslai.com
 *
 */
class VslBankModel extends BaseModel {

    protected $table = 'vsl_bank';
    protected $rule = [
        'id'  =>  '',
    ];
    protected $msg = [
        'id'  =>  '',
    ];

    /**
     * 银行列表数据为空,添加数据
     */
    public function  setBankList()
    {
        $list = [
            ['bank_code'=>'01040000','bank_name'=>'中国银行','bank_iocn'=>'https://vslai-com-cn.oss-cn-hangzhou.aliyuncs.com/upload/26/2019/09/10/16/86685786b4426d5a5f3c9d9b4dba129b2.png','sort'=>'1','bank_short_name'=>'中国银行','deposit_once'=>'50000','deposit_day'=>'50000','credit_once'=>'50000','credit_day'=>'50000',],
            ['bank_code'=>'01050000','bank_name'=>'中国建设银行','bank_iocn'=>'https://vslai-com-cn.oss-cn-hangzhou.aliyuncs.com/upload/26/2019/09/10/16/57e6630e60c1f96a090a7f1fd6d7c43b2.png','sort'=>'2','bank_short_name'=>'建设银行','deposit_once'=>'50000','deposit_day'=>'50000','credit_once'=>'50000','credit_day'=>'50000',],
            ['bank_code'=>'01020000','bank_name'=>'中国工商银行','bank_iocn'=>'https://vslai-com-cn.oss-cn-hangzhou.aliyuncs.com/upload/26/2019/09/10/16/036370b64fd55ecb39f6ac8855e625272.png','sort'=>'3','bank_short_name'=>'工商银行','deposit_once'=>'50000','deposit_day'=>'50000','credit_once'=>'50000','credit_day'=>'50000',],
            ['bank_code'=>'01030000','bank_name'=>'中国农业银行','bank_iocn'=>'https://vslai-com-cn.oss-cn-hangzhou.aliyuncs.com/upload/26/2019/09/10/16/2dd7818908ca557563065f7f706bd1982.png','sort'=>'4','bank_short_name'=>'农业银行','deposit_once'=>'2000','deposit_day'=>'10000','credit_once'=>'5000','credit_day'=>'50000',],
            ['bank_code'=>'04030000','bank_name'=>'中国邮政储蓄银行','bank_iocn'=>'https://vslai-com-cn.oss-cn-hangzhou.aliyuncs.com/upload/26/2019/09/10/16/2416fe5df617c047559abc25ed78b16e2.png','sort'=>'5','bank_short_name'=>'邮政银行','deposit_once'=>'50000','deposit_day'=>'50000','credit_once'=>'50000','credit_day'=>'50000',],
            ['bank_code'=>'03080000','bank_name'=>'招商银行','bank_iocn'=>'https://vslai-com-cn.oss-cn-hangzhou.aliyuncs.com/upload/26/2019/09/10/16/63b355ec0df0d5da89a18e56c65a19cf2.png','sort'=>'6','bank_short_name'=>'招商银行','deposit_once'=>'5000','deposit_day'=>'5000','credit_once'=>'20000','credit_day'=>'50000',],
            ['bank_code'=>'03060000','bank_name'=>'广东发展银行','bank_iocn'=>'https://vslai-com-cn.oss-cn-hangzhou.aliyuncs.com/upload/26/2019/09/10/16/fc0120002ffaaa6578a91ac8d50387492.png','sort'=>'7','bank_short_name'=>'广发银行','deposit_once'=>'50000','deposit_day'=>'50000','credit_once'=>'50000','credit_day'=>'50000',],
            ['bank_code'=>'03010000','bank_name'=>'交通银行','bank_iocn'=>'https://vslai-com-cn.oss-cn-hangzhou.aliyuncs.com/upload/26/2019/09/10/16/55d579fe0da0c1f3e2600c69baa863cc2.png','sort'=>'8','bank_short_name'=>'交通银行','deposit_once'=>'10000','deposit_day'=>'10000','credit_once'=>'20000','credit_day'=>'50000',],
            ['bank_code'=>'03020000','bank_name'=>'中信银行','bank_iocn'=>'https://vslai-com-cn.oss-cn-hangzhou.aliyuncs.com/upload/26/2019/09/10/16/3bafd8e4051f3adb25a93ea8139f22652.png','sort'=>'9','bank_short_name'=>'中信银行','deposit_once'=>'50000','deposit_day'=>'50000','credit_once'=>'50000','credit_day'=>'50000',],
            ['bank_code'=>'03030000','bank_name'=>'中国光大银行','bank_iocn'=>'https://vslai-com-cn.oss-cn-hangzhou.aliyuncs.com/upload/26/2019/09/10/16/648940dbb45be594dbea00bd054f836a2.png','sort'=>'10','bank_short_name'=>'光大银行','deposit_once'=>'50000','deposit_day'=>'50000','credit_once'=>'20000','credit_day'=>'50000',],
            ['bank_code'=>'03070000','bank_name'=>'平安银行','bank_iocn'=>'https://vslai-com-cn.oss-cn-hangzhou.aliyuncs.com/upload/26/2019/09/10/16/4ba799f80d9585814965dd64db96a90b2.png','sort'=>'11','bank_short_name'=>'平安银行','deposit_once'=>'20000','deposit_day'=>'20000','credit_once'=>'50000','credit_day'=>'50000',],
            ['bank_code'=>'03050000','bank_name'=>'中国民生银行','bank_iocn'=>'https://vslai-com-cn.oss-cn-hangzhou.aliyuncs.com/upload/26/2019/09/10/16/0ef194a1d0d661413db243f7636f75a82.png','sort'=>'12','bank_short_name'=>'民生银行','deposit_once'=>'50000','deposit_day'=>'50000','credit_once'=>'50000','credit_day'=>'50000',],
            ['bank_code'=>'03040000','bank_name'=>'华夏银行','bank_iocn'=>'https://vslai-com-cn.oss-cn-hangzhou.aliyuncs.com/upload/26/2019/09/10/16/6e4be81f80a7ae9a89315340094477c42.png','sort'=>'13','bank_short_name'=>'华夏银行','deposit_once'=>'2000','deposit_day'=>'2000','credit_once'=>'50000','credit_day'=>'50000',],
            ['bank_code'=>'03090000','bank_name'=>'兴业银行','bank_iocn'=>'https://vslai-com-cn.oss-cn-hangzhou.aliyuncs.com/upload/26/2019/09/10/16/2867dd184c134ffaac427b9857baf0dd2.png','sort'=>'14','bank_short_name'=>'兴业银行','deposit_once'=>'50000','deposit_day'=>'50000','credit_once'=>'20000','credit_day'=>'50000',],
            ['bank_code'=>'04012900','bank_name'=>'上海银行','bank_iocn'=>'https://vslai-com-cn.oss-cn-hangzhou.aliyuncs.com/upload/26/2019/09/10/16/b254a10b1025f98533a6d35984f3cfec2.png','sort'=>'15','bank_short_name'=>'上海银行','deposit_once'=>'50000','deposit_day'=>'50000','credit_once'=>'50000','credit_day'=>'50000',],

        ];
        $res = $this->saveAll($list);
        return $res;
    }
}