<?php
namespace data\service;

/**
 * 店铺服务层
 */
use data\service\BaseService as BaseService;
use data\model\WebSiteModel;
use data\model\MerchantVersionModel;
use data\model\AuthGroupModel;
use data\model\MerchantVersionLogModel;


class Merchant extends BaseService
{

    function __construct()
    {
        parent::__construct();
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IMerchant::getVersionList()
     */
        public function getVersionList($page_index = 1, $page_size = 0, $where = '', $order = '')
    {
        $instance_type = new MerchantVersionModel();
        $list = $instance_type->pageQuery($page_index, $page_size, $where, $order, '*');
        return $list;
    }
    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IMerchant::getVersion()
     */
    public function getVersion($condition = [])
    {
        $instance_type = new MerchantVersionModel();
        $list = $instance_type->getQuery($condition,'type_name,merchant_versionid','');
        return $list;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IMerchant::addMerchantVersion()
     */
    public function addMerchantVersion($type_name, $type_module_array,$shop_type_module_array, $type_desc, $type_sort)
    {
        $instance_type = new MerchantVersionModel();
        $data = array(
            'type_name' => $type_name,
            'type_module_array' => $type_module_array,
            'shop_type_module_array' => $shop_type_module_array,
            'type_desc' => $type_desc,
            'type_sort' => $type_sort,
            'create_time' => time(),
            'modify_time' => time()
        );
        $instance_type->save($data);
        return $instance_type->merchant_versionid;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IMerchant::updateMerchantVersion()
     */
    public function updateMerchantVersion($instance_typeid, $type_name, $type_module_array,$shop_type_module_array, $type_desc, $type_sort)
    {
        try {
            $instance_type = new MerchantVersionModel();
            $instance_type->startTrans();
            $data = array(
                'merchant_versionid' => $instance_typeid,
                'type_name' => $type_name,
                'type_module_array' => $type_module_array,
                'shop_type_module_array' => $shop_type_module_array,
                'type_desc' => $type_desc,
                'type_sort' => $type_sort,
                'modify_time' => time()
            );
            $instance_type->save($data, [
                'merchant_versionid' => $instance_typeid
            ]);
            
            $instance = new WebSiteModel();
            $instance_list = $instance->getQuery(['merchant_versionid' => $instance_typeid], 'website_id', '');
            
            $instance_arr = '';
            foreach ($instance_list as $item) {
                $instance_arr .= $item['website_id'] . ',';
            }
            
            $instance_arr = rtrim($instance_arr, ",");
            $auth_group = new AuthGroupModel();
            $retval = $auth_group->save([
                'module_id_array' => $type_module_array,
                'shop_module_id_array' => $shop_type_module_array
            ], [
                'website_id' => array(
                    "IN",
                    $instance_arr
                ),
                'is_system' => 1,
                'instance_id'=>0
            ]);
            $instance_type->commit();
            return $retval;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $instance_type->rollback();
            $retval = $e->getMessage();
            return 0;
        }
    }
    
    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IMerchant::getMerchantVersionDetail()
     */
    public function getMerchantVersionDetail($instance_typeid)
    {
        $instance_type = new MerchantVersionModel();
        $shop_type_info = $instance_type->get($instance_typeid);
        return $shop_type_info;
    }
    
    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IMerchant::getMerchantVersionDetail()
     */
    public function deleteVersion($version_id)
    {
        $count = $this->getVersionIsUse($version_id);
        if($count > 0)
        {
            return VERSION_ISUSE;
        }else{
            $merchant_version = new MerchantVersionModel();
            $res = $merchant_version->where('merchant_versionid',$version_id)->delete();
            return $res;
        }
    }
    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IMerchant::getMerchantVersionDetail()
     */
    public function getVersionIsUse($instance_typeid)
    {
        $website = new WebSiteModel();
        $count = $website->getCount(['merchant_versionid' => $instance_typeid]);
        return $count;
    }

    /**
     * 设置默认版本
     * 2018年5月23日 11:16:34
     *
     * {@inheritdoc}
     */
    public function setDefaultVersion($id)
    {
        if (!$id) {
            return UPDATA_FAIL;
        }
        $merchantVersionModel = new MerchantVersionModel();
        $data['modify_time'] = time();
        $data['is_default'] = 0;
        $result = $merchantVersionModel->save($data,['merchant_versionid'=>['>',0]]);
        if (!$result) {
            return UPDATA_FAIL;
        }
        $condition['merchant_versionid'] = $id;
        $res = $merchantVersionModel->save(['modify_time' => time(), 'is_default' => 1], $condition);
        return $res;
    }
  
    /*
     * (non-PHPdoc)
     * 获取版本变更记录
     */
    public function getChangeLog($page_index = 1, $page_size = 0, $condition = '', $order = '')
    {
        // TODO Auto-generated method stub
        $merchant_log = new MerchantVersionLogModel();
        $list = $merchant_log->pageQuery($page_index, $page_size, $condition, $order, '*');
        return $list;
    }
    
    /**
     * (non-PHPdoc)
     *
     * 添加版本变更记录
     */
    public function addVersionChangeLog($log_data = array())
    {
        $merchantVersionLog = new MerchantVersionLogModel();
        $data = array(
            'merchant_versionid' => $log_data['merchant_versionid'],
            'uid' => $log_data['uid'],
            'type' => $log_data['type'],
            'pay_type' => $log_data['pay_type'],
            'pay_money' => $log_data['pay_money'],
            'ip' => \think\Request::instance()->ip(),
            'due_time' => $log_data['due_time'],
            'create_time' => time(),
            'website_id' => $log_data['website_id'],
        );
        $merchantVersionLog->save($data);
        return $merchantVersionLog->id;
    }
    
}
