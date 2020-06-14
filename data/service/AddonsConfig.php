<?php

namespace data\service;

/**
 * 系统配置业务层
 */

use data\model\AddonsConfigModel as AddonsConfigModel;
use data\service\BaseService as BaseService;

class AddonsConfig extends BaseService
{

    private $config_module;

    function __construct()
    {
        parent::__construct();
        $this->config_module = new AddonsConfigModel();
    }
    /**
     * (non-PHPdoc)
     *
     */
    public function setAddonsConfig($params)
    {
        if ($this->checkConfigKeyIsset($params['addons'])) {
            $res = $this->updateAddonsConfig($params['value'], $params['desc'], $params['is_use'], $params['addons']);
        } else {
            $res = $this->addAddonsConfig($params['value'], $params['desc'], $params['is_use'], $params['addons']);
        }
        return $res;
    }
    /**
     * (non-PHPdoc)
     *
     * @see \ata\api\IConfig::getConfig()
     */
    public function getAddonsConfig($addons,$websiteid=0)
    {
        if ($this->website_id) {
            $website_id = $this->website_id;
        } else {
            $website_id = $websiteid;
        }
        $config = new AddonsConfigModel();
        $info = $config->getInfo([
            'website_id' => $website_id,
            'addons' => $addons
        ]);
        return $info;
    }

    /**
     * 添加设置
     *
     * @param unknown $value
     * @param unknown $desc
     * @param unknown $is_use
     */
    public function addAddonsConfig($value, $desc, $is_use, $addons = '')
    {
        if ($this->website_id) {
            $websiteid = $this->website_id;
        } else {
            $websiteid = 0;
        }
        $config = new AddonsConfigModel();
        if (is_array($value)) {
            $value = json_encode($value);
        }
        $data = array(
            'website_id' => $websiteid,
            'value' => $value,
            'desc' => $desc,
            'is_use' => $is_use,
            'create_time' => time(),
            'addons' => $addons
        );
        $res = $config->save($data);
        return $res;
    }

    /**
     * 修改配置
     *
     * @param unknown $instance_id
     * @param unknown $key
     * @param unknown $value
     * @param unknown $desc
     * @param unknown $is_use
     */
    public function updateAddonsConfig($value, $desc, $is_use,$addons)
    {
        if ($this->website_id) {
            $websiteid = $this->website_id;
        } else {
            $websiteid = 0;
        }
        $config = new AddonsConfigModel();
        if (is_array($value)) {
            $value = json_encode($value);
        }
        $data = array(
            'value' => $value,
            'desc' => $desc,
            'is_use' => $is_use,
            'modify_time' => time()
        );
        $res = $config->save($data, [
            'website_id' => $websiteid,
            'addons' => $addons
        ]);
        return $res;
    }

    /**
     * 判断当前设置是否存在
     * 存在返回 true 不存在返回 false
     *
     * @param unknown $instance_id
     * @param unknown $key
     */
    public function checkConfigKeyIsset($addons)
    {
        if ($this->website_id) {
            $websiteid = $this->website_id;
        } else {
            $websiteid = 0;
        }
        $config = new AddonsConfigModel();
        $num = $config->where([
            'website_id' => $websiteid,
            'addons' => $addons
        ])->count();
        return $num > 0 ? true : false;
    }
}