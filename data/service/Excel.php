<?php

namespace data\service;

use data\model\VslExcelTemplateModel;

/**
 *导出excel自定义模板
 */
class Excel extends BaseService {
    
    /**
     * 列表
     */
    public function getExcelList($condition){
        $excel = new VslExcelTemplateModel();
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $list = $excel->getList($condition);
        return $list;
    }
    /**
     * @param array $input
     * @return int
     */
    public function addExcel($input)
    {
        $excel = new VslExcelTemplateModel();
        $excel->startTrans();
        try {
            $result = $excel->save($input);
            $excel->commit();
            return $result;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $excel->rollback();
            return $e->getMessage();
        }
    }
    
    /**
     * @param array $input
     * @return int
     */
    public function updateExcel($input ,$where)
    {
        $excel = new VslExcelTemplateModel();
        $excel->startTrans();
        try {
            $excel->save($input,$where);
            $excel->commit();
            return  $where['template_id'];
        } catch (\Exception $e) {
            recordErrorLog($e);
            $excel->rollback();
            return $e->getMessage();
        }
    }
    
    /**
     * @return int
     */
    public function deleteExcel($condition)
    {
        $excel = new VslExcelTemplateModel();
        $excel->startTrans();
        try {
            $info = $excel->where($condition)->find();
            if (count($info) > 0) {
                $excel::destroy(['template_id' => $condition['template_id']]);
                $excel->commit();
                return 1;
            }
            return -1;
        } catch (\Exception $e) {
            recordErrorLog($e);
            $excel->rollback();
            return $e->getMessage();
        }
    }
}
