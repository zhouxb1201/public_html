<?php

namespace addons\customform\controller;

use addons\customform\Customform as baseCoupon;
use addons\customform\model\VslCustomModel;
use addons\customform\server\Custom as CustomFormServer;
use addons\customform\server\Custom;
use data\model\AddonsConfigModel;
use data\model\ConfigModel;
use data\service\Address;
use data\service\Member;

/**
 * Class Customform
 * @package addons\customform\controller
 */
class Customform extends baseCoupon
{
   public function __construct()
   {
       parent::__construct();
   }

    public function customFormList()
    {
        $page_index = request()->post("page_index", '');
        $page_size = request()->post("page_size", 'PAGESIZE');
        $search_text = request()->post('search_text', '');
        $condition = array(
            'website_id' => $this->website_id,
            'name' => array(
                'like',
                '%' . $search_text . '%'
            )
        );
        $couponServer = new CustomFormServer();
        $list = $couponServer->getCustomFormList($page_index,$page_size,$condition,'update_time desc');


        return $list;

    }

    /**
     * 添加模板
     * @return \multitype|void
     */
    public function addCustomForm()
    {
        $all['tagid'] = $_POST['seckill_name'];
        $all['name'] = $_POST['custom_name'];
        $all['value'] = $_POST['custom_form'];
        
        $custom = new Custom();
        $res = $custom->addCustomForm($all);

        if($res){
            $this->addUserLog('添加模板', $res);
        }

        return AjaxReturn($res);

    }
    

    public function getCustomFormInfo()
    {
        
    }

    /**
     * 更新模板
     */
    public function updateCustomForm()
    {
        $custom_id = $_POST['custom_id'];
        
        $all['tagid'] = $_POST['seckill_name'];
        $all['name'] = $_POST['custom_name'];
        $all['value'] = $_POST['custom_form'];

        $custom = new Custom();
        $res = $custom->updateCustomForm($custom_id,$all);

        if($res){
            $this->addUserLog('更新模板', $res);
        }
        return AjaxReturn($res);

    }
    /**
     * 获取模板
     */
    public function getCustomForm()
    {
        $custom_id = $_POST['custom_id'];
        $custom = new Custom();
        $res = $custom->getCustomFormDetail($custom_id);
        $res['value'] =  json_decode(htmlspecialchars_decode($res['value']));
        return $res['value'];
    }
    /**
     * 删除模板
     * @return \multitype
     */
    public function deleteCustomForm()
    {
        $custom_form_id = request()->post('custom_form_id', '');

        if(empty($custom_form_id)){
            $this->error('没有模板信息');
        }

        $custom = new Custom();
        $res = $custom->deleteCustomForm($custom_form_id);

        if($res){
            $this->addUserLog('删除模板',$custom_form_id);
        }

        return AjaxReturn($res);
    }

    /**
     * 添加模板标签
     * @return \multitype|void
     */
    public function addCustomFormTag()
    {
        $tag_name = request()->post('data','');
        $tag_id = request()->post('id','');

        if (empty($tag_name)){
            return AjaxReturn(-1);
        }

        $custom = new Custom();
        $res = $custom->addCustomFormTag($tag_name,$tag_id);

        if($res){
            $this->addUserLog('添加标签', $tag_name);
        }
        return AjaxReturn($res);
    }

    /**
     * 删除模板标签
     * @return \multitype
     */
    public function deleteCustomFormTag()
    {
       $id = request()->post('id');

       $custom = new Custom();
       $res = $custom->deleteCustomFormTag($id);

       if($res){
           $this->addUserLog('删除标签',$id);
       }
       return AjaxReturn($res);
    }

    /**
     * 模板设置
     * @return \multitype|void
     */
    public function customFormSetting()
    {
        $all = $_POST;
        $custom = new Custom();
        $res = $custom->saveCustomFormSetting($all);
        setAddons('customform', $this->website_id, $this->instance_id);
        if($res){
            $this->addUserLog('添加模板设置',$res);
        }

        return AjaxReturn($res);
    }


    /**
     * 获取省列表
     */
    public function getProvince()
    {
        $address = new Address();
        $province_list = $address->getProvinceList();
        return $province_list;
    }

    /**
     * 获取城市列表
     *
     * @return Ambigous <multitype:\think\static , \think\false, \think\Collection, \think\db\false, PDOStatement, string, \PDOStatement, \think\db\mixed, boolean, unknown, \think\mixed, multitype:, array>
     */
    public function getCity()
    {
        $address = new Address();
        $province_id = isset($_POST['province_id']) ? $_POST['province_id'] : 0;
        $city_list = $address->getCityList($province_id);
        return $city_list;
    }

    /**
     * 获取区域地址
     */
    public function getDistrict()
    {
        $address = new Address();
        $city_id = isset($_POST['city_id']) ? $_POST['city_id'] : 0;
        $district_list = $address->getDistrictList($city_id);
        return $district_list;
    }
    public function getCustomData()
    {
        $custom = new Custom();
        $usage = isset($_POST['usage']) ? $_POST['usage'] : '';
        $search_text = isset($_POST['search_text']) ? $_POST['search_text'] : '';
        $page_index = isset($_POST['page_index']) ? $_POST['page_index'] : 1;
        $custom_list = $custom->getCustomData($page_index,PAGESIZE,$usage,$search_text);
        return $custom_list;
    }
    /**
     * 数据excel导出
     */
    public function customDataExcel()
    {
        $custom = new Custom();
        $custom_id = $_GET['custom_id'];
        $custom_form_info = json_decode($custom->getCustomFormDetail($custom_id)['value'],true);
        $columns=array();
        foreach($custom_form_info as $k=>$list){
            $columns[]=array($k,''.$list['name'].'') ;
        }
        if($custom_form_info){
            array_unshift($columns,'用户编号','用户名');
            $columns[0] = ['A0','用户编号'];
            $columns[1] = [1,'用户名'];
            foreach ($columns as $key=>$value){
                if($key>=2 && $value){
                    $columns[$key][0] = $columns[$key][0]+2;
                }
            }
        }
        $xlsName = "表单数据";
        $xlsCell = $columns;
        $search_text = isset($_GET['search_text']) ? $_GET['search_text'] : '';
        $usage = isset($_GET['usage']) ? $_GET['usage'] : '';
        $page_index = isset($_GET['page_index']) ? $_GET['page_index'] : 1;
        if($search_text=='undefined'){
            $search_text = '';
        }
        $list = $custom->getCustomDatas($page_index,0,$usage,$search_text);
        $datas = [];
        foreach ($list['data'] as $k => $v) {
            if(!isset($v['user_name'])){
                $list['data'][$k]["user_name"] = $list['data'][$k]["nick_name"];
            }
            foreach ($list['data'][$k]['custom_data'] as $k1=>$v1){
                    if($v1['tag']=="date" && $v1["value"]){
                        $datas[$k][$k1] = date("Y-m-d H:i:s",$v1["value"]);
                    }else  if($v1['tag']=="date_range" && $v1["value"]){
                        $dates = explode(',',$v1["value"]);
                        $date1 = date("Y-m-d H:i:s",$dates[0]);
                        $date2 = date("Y-m-d H:i:s",$dates[1]);
                        $datas[$k][$k1] = $date1.'~'.$date2;
                    }else{
                        $datas[$k][$k1]= $v1["value"];
                    }
            }
            array_unshift($datas[$k],$list['data'][$k]["uid"],$list['data'][$k]["user_name"]);
            $datas[$k]['A0']=$list['data'][$k]["uid"];
        }
        $this->addUserLog('表单数据excel导出', 1);
        dataExcel($xlsName, $xlsCell, $datas);
    }
}