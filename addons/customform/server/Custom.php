<?php
namespace addons\customform\server;

use addons\channel\model\VslChannelModel;
use addons\customform\model\VslCustomModel;
use addons\customform\model\VslCustomSettingModel;
use addons\customform\model\VslCustomTagModel;
use data\model\AddonsConfigModel;
use data\model\UserModel;
use data\model\VslMemberModel;
use data\model\VslOrderModel;
use data\service\AddonsConfig;
use data\service\BaseService;

class Custom extends BaseService
{
    public $addons_config_module;

    function __construct()
    {
        parent::__construct();
        $this->addons_config_module = new AddonsConfigModel();
    }
    
    /**
     * 获取模板列表
     * @param int|string $page_index
     * @param int|string $page_size
     * @param array $condition
     * @param string $order
     * @param string $fields
     *
     * @return array $coupon_type_list
     */
    public function getCustomFormList($page_index = 1, $page_size = 0, $condition = '', $order = '')
    {
        $custom_from = new VslCustomModel();
        $custom_from_list = $custom_from->getList($page_index,$page_size,$condition,$order);

        $custom_setting = new AddonsConfig();
        $set_data = $custom_setting->getAddonsConfig('customform',$condition['website_id']);

        $data = [
            'custom_forms' => $custom_from_list,
            'set_data' => json_decode($set_data['value'])
        ];
        return $data;
    }

    /**
     * 模板详情
     * @param $customr_id
     * @return mixed
     */
    public function getCustomFormDetail($customr_id)
    {
        $custom_form = new VslCustomModel();

        $res = $custom_form->with('custom_tag')->find($customr_id);

        return $res;
    }

    /**
     * 模板添加
     * @param $data
     * @return int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addCustomForm($data)
    {
         $custom_form = new VslCustomModel();
         $custom_data = $custom_form->where('name',$data['name'])->find();
         if ($custom_data) {
             return -1;
         }

         $custom_form->tagid = (int)$data['tagid'];
         $custom_form->name = $data['name'];
         $custom_form->value = $data['value'];
         $custom_form->create_time = time();
         $custom_form->website_id = $this->website_id;
         $custom_form->save();

         return 1;
    }

    /**
     * 模板更新
     * @param $custom_id
     * @param $data
     * @return int
     */
    public function updateCustomForm($custom_id,$data)
    {
        $custom_form = new VslCustomModel();
        $custom_data = $custom_form->find($custom_id);
        $custom_name = $custom_form->where('name',$data['name'])->where('id','<>',$custom_id)->find();
        if ($custom_name) {
            return -1;
        }
       
        $custom_data->tagid = (int)$data['tagid'];
        $custom_data->name = $data['name'];
        $custom_data->value = $data['value'];
        $custom_data->update_time = time();
        $custom_data->website_id = $this->website_id;
        $custom_data->save();

        return 1;
    }

    /**
     * 模板删除
     * @param $custom_form_id
     * @return int
     */
    public function deleteCustomForm($custom_form_id)
    {
        $custom_form = new VslCustomModel();

        $custom_form->destroy($custom_form_id);

        return 1;
    }

    /**
     * 添加模板标签
     * @param $tag_name
     * @param $tag_id
     * @return int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addCustomFormTag($tag_name,$tag_id)
    {
        $custom_tag = new VslCustomTagModel();
        $tag = $custom_tag->getInfo(['tag_name'=> $tag_name, 'website_id' => $this->website_id]);
        if($tag_id){
            $tag = $custom_tag->getInfo(['tag_name'=> $tag_name,'id' => ['<>',$tag_id], 'website_id' => $this->website_id]);
        }
        if ($tag) {
            return -10012;
        }

        if($tag_id){
            $custom_tag->save(['tag_name' => $tag_name, 'update_time' => time(),'website_id' => $this->website_id],['id' => $tag_id]);
        } else {
            $custom_tag->save(['tag_name' => $tag_name, 'create_time' => time(),'website_id' => $this->website_id]);
        }
        return 1;
    }

    /**
     * 删除模板标签
     * @param $id
     * @return int
     */
    public function deleteCustomFormTag($id)
    {
        $custom_tag = new VslCustomTagModel();
        $custom_tag->destroy($id);

        return 1;
    }

    /**
     * 保存模板设置
     * @param $all
     * @return false|int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function saveCustomFormSetting($all)
    {
        $add_config = new AddonsConfig();
        $info = $this->addons_config_module->getInfo(['addons'=>'customform','website_id'=>$this->website_id],'value')['value'];
        $res = json_decode($info,true);
        $all['member_status']=0;
        $all['order_status']=0;
        $all['distributor_status']=0;
        $all['shareholder_status']=0;
        $all['captain_status']=0;
        $all['channel_status']=0;
        $all['area_status']=0;
        if($res){
            if($res['member_id']  && $res['member_id']!=$all['member_id']){
                $all['member_status'] = $res['member_status']+1;
            }else{
                $all['member_status'] = $res['member_status'];
            }
            if($res['order_id'] && $res['order_id']!=$all['order_id']){
                $all['order_status'] = $res['order_status']+1;
            }else{
                $all['order_status'] = $res['order_status'];
            }

            if($res['distributor_id'] && $res['distributor_id']!=$all['distributor_id']){
                $all['distributor_status']=$res['distributor_status']+1;
            }else{
                $all['distributor_status'] = $res['distributor_status'];
            }

            if($res['shareholder_id'] && $res['shareholder_id']!=$all['shareholder_id']){
                $all['shareholder_status']=$res['shareholder_status']+1;
            }else{
                $all['shareholder_status'] = $res['shareholder_status'];
            }

            if($res['region_id'] && $res['region_id']!=$all['region_id']){
                $all['area_status']=$res['area_status']+1;
            }else{
                $all['area_status'] = $res['area_status'];
            }
            if($res['captain_id'] && $res['captain_id']!=$all['captain_id']){
                $all['captain_status']=$res['captain_status']+1;
            }else{
                $all['captain_status'] = $res['captain_status'];
            }
            if($res['channel_id'] && $res['channel_id']!=$all['channel_id']){
                $all['channel_status']=$res['channel_status']+1;
            }else{
                $all['channel_status'] = $res['channel_status'];
            }
        }
        $custom = new VslCustomModel();
        $custom->save(['usage'=>''],['website_id'=>$this->website_id]);
        if($all['order_id']){
            $usage = $custom->getInfo(['website_id'=>$this->website_id,'id'=>$all['order_id']],'usage')['usage'];
            if($usage && !in_array(1,explode(',',$usage))){
                $usage_real = $usage.'1,';
            }else if(!in_array(1,explode(',',$usage))){
                $usage_real = '1,';
            }
            $custom->save(['usage'=>$usage_real],['website_id'=>$this->website_id,'id'=>$all['order_id']]);
        }
        if($all['member_id']){
            $usage = $custom->getInfo(['website_id'=>$this->website_id,'id'=>$all['member_id']],'usage')['usage'];
            if($usage && !in_array(2,explode(',',$usage))){
                $usage_real = $usage.'2,';
            }else if(!in_array(2,explode(',',$usage))){
                $usage_real = '2,';
            }
            $custom->save(['usage'=>$usage_real],['website_id'=>$this->website_id,'id'=>$all['member_id']]);
        }
        $usage_real = '';
        if($all['distributor_id']){
            $usage = $custom->getInfo(['website_id'=>$this->website_id,'id'=>$all['distributor_id']],'usage')['usage'];
            if($usage && !in_array(3,explode(',',$usage))){
                $usage_real .= $usage.'3,';
            }else if(!in_array(3,explode(',',$usage))){
                $usage_real .= '3,';
            }
            $custom->save(['usage'=>$usage_real],['website_id'=>$this->website_id,'id'=>$all['distributor_id']]);
        }
        if($all['shareholder_id']){
            $usage = $custom->getInfo(['website_id'=>$this->website_id,'id'=>$all['shareholder_id']],'usage')['usage'];
            if($usage && !in_array(4,explode(',',$usage))){
                $usage_real .= $usage.'4,';
            }else if(!in_array(4,explode(',',$usage))){
                $usage_real .= '4,';
            }
            $custom->save(['usage'=>$usage_real],['website_id'=>$this->website_id,'id'=>$all['shareholder_id']]);
        }
        if($all['region_id']){
            $usage = $custom->getInfo(['website_id'=>$this->website_id,'id'=>$all['region_id']],'usage')['usage'];
            if($usage && !in_array(5,explode(',',$usage))){
                $usage_real .= $usage.'5,';
            }else if(!in_array(5,explode(',',$usage))){
                $usage_real .= '5,';
            }
            $custom->save(['usage'=>$usage_real],['website_id'=>$this->website_id,'id'=>$all['region_id']]);
        }
        if($all['captain_id']){
            $usage = $custom->getInfo(['website_id'=>$this->website_id,'id'=>$all['captain_id']],'usage')['usage'];
            if($usage && !in_array(6,explode(',',$usage))){
                $usage_real .= $usage.'6,';
            }else if(!in_array(6,explode(',',$usage))){
                $usage_real .= '6,';
            }
            $custom->save(['usage'=>$usage_real],['website_id'=>$this->website_id,'id'=>$all['captain_id']]);
        }
        if($all['channel_id']){
            $usage = $custom->getInfo(['website_id'=>$this->website_id,'id'=>$all['channel_id']],'usage')['usage'];
            if($usage && !in_array(7,explode(',',$usage))){
                $usage_real = $usage.'7,';
            }else if(!in_array(7,explode(',',$usage))){
                $usage_real = '7,';
            }
            $custom->save(['usage'=>$usage_real],['website_id'=>$this->website_id,'id'=>$all['channel_id']]);
        }
        $customids = $custom->Query(['website_id'=>$this->website_id],'id');
        foreach ($customids as $v){
             $custom = new VslCustomModel();
             $usages = $custom->getInfo(['id'=>$v],'usage')['usage'];
             if($usages){
                 $value = explode(',',$usages);
                 $val = array_unique($value);
                 $real_val = implode(',',$val);
                 $custom->save(['usage'=>$real_val],['id'=>$v]);
             }
        }
        $coupon_info = $this->addons_config_module->where('addons',"customform")->where('website_id',$this->website_id)->find();
        if (!empty($coupon_info)) {
            $res = $coupon_info->save([
                'addons' => 'customform',
                'modify_time' => time(),
                'is_use'=>1,
                'value' => json_encode($all)
            ],['website_id' => $this->website_id,'addons' => 'customform']);
        } else {
            $res = $add_config->addAddonsConfig($all, '模板设置', 1, 'customform');
        }
        return $res;

    }
    public function getCustomData($page_index,$page_size,$usage,$search_text=''){
        if($usage==1){
            $order = new VslOrderModel();
            if($search_text){
                $list = $order->getViewList($page_index,$page_size,['nm.website_id' => $this->website_id,'nm.custom_order'=>['like','%'.$search_text.'%']],'nm.create_time desc');
            }else{
                $list = $order->getViewList($page_index,$page_size,['nm.website_id' => $this->website_id,'nm.custom_order'=>['neq','']],'nm.create_time desc');
            }
            if($list['data']){
                $list['data'] = objToArr($list['data']);
                foreach ($list['data'] as $k => $v) {
                    $list['data'][$k]["custom_order"] = json_decode(htmlspecialchars_decode($list['data'][$k]["custom_order"]),true);
                    $list['data'][$k]["custom_data"] = $list['data'][$k]["custom_order"];
                    if($list['data'][$k]["custom_data"]){
                        foreach ($list['data'][$k]["custom_data"] as $k1 => $v1) {
                            if($v1['tag']=='img' && $v1['value']){
                                $list['data'][$k]["custom_data"][$k1]['value'] = explode(',',$v1['value']);
                            }
                            if($v1['tag']=="date" && $list['data'][$k]["custom_data"][$k1]['value']){
                                $list['data'][$k]["custom_data"][$k1]['value'] = date("Y-m-d H:i:s",$list['data'][$k]["custom_data"][$k1]['value']);
                            }
                            if($v1['tag']=="area" && $list['data'][$k]["custom_data"][$k1]['value']){
                                $list['data'][$k]["custom_data"][$k1]['value'] = substr($list['data'][$k]["custom_data"][$k1]['value'],0,strpos($list['data'][$k]["custom_data"][$k1]['value'],","));
                            }
                            if($v1['tag']=="date_range" && $list['data'][$k]["custom_data"][$k1]['value']){
                                $dates = explode(',',$list['data'][$k]["custom_data"][$k1]['value']);
                                $date1 = date("Y-m-d H:i:s",$dates[0]);
                                $date2 = date("Y-m-d H:i:s",$dates[1]);
                                $list['data'][$k]["custom_data"][$k1]['value'] = $date1.'~'.$date2;
                            }
                        }
                    }

                }
            }
        }
        if($usage==2){
            $user = new UserModel();
            if($search_text){
                $list = $user->pageQuery($page_index,$page_size,['website_id' => $this->website_id,'is_member'=>1,'custom_person'=>['like','%'.$search_text.'%']],'reg_time desc','custom_person,uid,nick_name,user_name,user_headimg,user_tel');
            }else{
                $list = $user->pageQuery($page_index,$page_size,['website_id' => $this->website_id,'is_member'=>1,'custom_person'=>['neq','']],'reg_time desc','custom_person,uid,nick_name,user_name,user_headimg,user_tel');
            }
            if($list['data']){
                $list['data'] = objToArr($list['data']);
                foreach ($list['data'] as $k => $v) {
                    $list['data'][$k]["custom_person"] = json_decode(htmlspecialchars_decode($list['data'][$k]["custom_person"]),true);
                    $list['data'][$k]["custom_data"] = $list['data'][$k]["custom_person"]['form_data'];
                    if($list['data'][$k]["custom_data"]){
                        foreach ($list['data'][$k]["custom_data"] as $k1 => $v1) {
                            if($v1['tag']=='img' && $v1['value']){
                                $list['data'][$k]["custom_data"][$k1]['value'] = explode(',',$v1['value']);
                            }
                            if($v1['tag']=="date" && $list['data'][$k]["custom_data"][$k1]['value']){
                                $list['data'][$k]["custom_data"][$k1]['value'] = date("Y-m-d H:i:s",$list['data'][$k]["custom_data"][$k1]['value']);
                            }
                            if($v1['tag']=="area" && $list['data'][$k]["custom_data"][$k1]['value']){
                                $list['data'][$k]["custom_data"][$k1]['value'] = substr($list['data'][$k]["custom_data"][$k1]['value'],0,strpos($list['data'][$k]["custom_data"][$k1]['value'],","));
                            }
                            if($v1['tag']=="date_range" && $list['data'][$k]["custom_data"][$k1]['value']){
                                $dates = explode(',',$list['data'][$k]["custom_data"][$k1]['value']);
                                $date1 = date("Y-m-d H:i:s",$dates[0]);
                                $date2 = date("Y-m-d H:i:s",$dates[1]);
                                $list['data'][$k]["custom_data"][$k1]['value'] = $date1.'~'.$date2;
                            }
                        }
                    }

                }
            }
        }
        if($usage==5){
            $member = new VslMemberModel();
            if($search_text){
                $list = $member->getViewList($page_index,$page_size,['nm.website_id' => $this->website_id,'nm.custom_area'=>['like','%'.$search_text.'%']],'nm.reg_time desc');
            }else{
                $list = $member->getViewList($page_index,$page_size,['nm.website_id' => $this->website_id,'nm.custom_area'=>['neq','']],'nm.reg_time desc');
            }
            if($list['data']){
                $list['data'] = objToArr($list['data']);
                foreach ($list['data'] as $k => $v) {
                    $list['data'][$k]["custom_area"] = json_decode(htmlspecialchars_decode($list['data'][$k]["custom_area"]),true);
                    $list['data'][$k]["custom_data"] = $list['data'][$k]["custom_area"];
                    if($list['data'][$k]["custom_data"]){
                        foreach ($list['data'][$k]["custom_data"] as $k1 => $v1) {
                            if($v1['tag']=='img' && $v1['value']){
                                $list['data'][$k]["custom_data"][$k1]['value'] = explode(',',$v1['value']);
                            }
                            if($v1['tag']=="date" && $list['data'][$k]["custom_data"][$k1]['value']){
                                $list['data'][$k]["custom_data"][$k1]['value'] = date("Y-m-d H:i:s",$list['data'][$k]["custom_data"][$k1]['value']);
                            }
                            if($v1['tag']=="area" && $list['data'][$k]["custom_data"][$k1]['value']){
                                $list['data'][$k]["custom_data"][$k1]['value'] = substr($list['data'][$k]["custom_data"][$k1]['value'],0,strpos($list['data'][$k]["custom_data"][$k1]['value'],","));
                            }
                            if($v1['tag']=="date_range" && $list['data'][$k]["custom_data"][$k1]['value']){
                                $dates = explode(',',$list['data'][$k]["custom_data"][$k1]['value']);
                                $date1 = date("Y-m-d H:i:s",$dates[0]);
                                $date2 = date("Y-m-d H:i:s",$dates[1]);
                                $list['data'][$k]["custom_data"][$k1]['value'] = $date1.'~'.$date2;
                            }
                        }
                    }
                }
            }
        }
        if($usage==4){
            $member = new VslMemberModel();
            if($search_text){
                $list = $member->getViewList($page_index,$page_size,['nm.website_id' => $this->website_id,'nm.custom_global'=>['like','%'.$search_text.'%']],'nm.reg_time desc');
            }else{
                $list = $member->getViewList($page_index,$page_size,['nm.website_id' => $this->website_id,'nm.custom_global'=>['neq','']],'nm.reg_time desc');
            }
            if($list['data']){
                $list['data'] = objToArr($list['data']);
                foreach ($list['data'] as $k => $v) {
                    $list['data'][$k]["custom_global"] = json_decode(htmlspecialchars_decode($list['data'][$k]["custom_global"]),true);
                    $list['data'][$k]["custom_data"] = $list['data'][$k]["custom_global"];
                    if($list['data'][$k]["custom_data"]){
                        foreach ($list['data'][$k]["custom_data"] as $k1 => $v1) {
                            if($v1['tag']=='img' && $v1['value']){
                                $list['data'][$k]["custom_data"][$k1]['value'] = explode(',',$v1['value']);
                            }
                            if($v1['tag']=="date" && $list['data'][$k]["custom_data"][$k1]['value']){
                                $list['data'][$k]["custom_data"][$k1]['value'] = date("Y-m-d H:i:s",$list['data'][$k]["custom_data"][$k1]['value']);
                            }
                            if($v1['tag']=="area" && $list['data'][$k]["custom_data"][$k1]['value']){
                                $list['data'][$k]["custom_data"][$k1]['value'] = substr($list['data'][$k]["custom_data"][$k1]['value'],0,strpos($list['data'][$k]["custom_data"][$k1]['value'],","));
                            }
                            if($v1['tag']=="date_range" && $list['data'][$k]["custom_data"][$k1]['value']){
                                $dates = explode(',',$list['data'][$k]["custom_data"][$k1]['value']);
                                $date1 = date("Y-m-d H:i:s",$dates[0]);
                                $date2 = date("Y-m-d H:i:s",$dates[1]);
                                $list['data'][$k]["custom_data"][$k1]['value'] = $date1.'~'.$date2;
                            }
                        }
                    }
                }
            }
        }
        if($usage==6){
            $member = new VslMemberModel();
            if($search_text){
                $list = $member->getViewList($page_index,$page_size,['nm.website_id' => $this->website_id,'nm.custom_team'=>['like','%'.$search_text.'%']],'nm.reg_time desc');
            }else{
                $list = $member->getViewList($page_index,$page_size,['nm.website_id' => $this->website_id,'nm.custom_team'=>['neq','']],'nm.reg_time desc');
            }
            if($list['data']){
                $list['data'] = objToArr($list['data']);
                foreach ($list['data'] as $k => $v) {
                    $list['data'][$k]["custom_team"] = json_decode(htmlspecialchars_decode($list['data'][$k]["custom_team"]),true);
                    $list['data'][$k]["custom_data"] = $list['data'][$k]["custom_team"];
                    if($list['data'][$k]["custom_data"]){
                        foreach ($list['data'][$k]["custom_data"] as $k1 => $v1) {
                            if($v1['tag']=='img' && $v1['value']){
                                $list['data'][$k]["custom_data"][$k1]['value'] = explode(',',$v1['value']);
                            }
                            if($v1['tag']=="date" && $list['data'][$k]["custom_data"][$k1]['value']){
                                $list['data'][$k]["custom_data"][$k1]['value'] = date("Y-m-d H:i:s",$list['data'][$k]["custom_data"][$k1]['value']);
                            }
                            if($v1['tag']=="area" && $list['data'][$k]["custom_data"][$k1]['value']){
                                $list['data'][$k]["custom_data"][$k1]['value'] = substr($list['data'][$k]["custom_data"][$k1]['value'],0,strpos($list['data'][$k]["custom_data"][$k1]['value'],","));
                            }
                            if($v1['tag']=="date_range" && $list['data'][$k]["custom_data"][$k1]['value']){
                                $dates = explode(',',$list['data'][$k]["custom_data"][$k1]['value']);
                                $date1 = date("Y-m-d H:i:s",$dates[0]);
                                $date2 = date("Y-m-d H:i:s",$dates[1]);
                                $list['data'][$k]["custom_data"][$k1]['value'] = $date1.'~'.$date2;
                            }
                        }
                    }

                }
            }
        }
        if($usage==3){
            $member = new VslMemberModel();
            if($search_text){
                $list =  $member->getViewList($page_index,$page_size,['nm.website_id' => $this->website_id,'nm.distributor_apply'=>['like','%'.$search_text.'%']],'nm.reg_time desc');
            }else{
                $list =  $member->getViewList($page_index,$page_size,['nm.website_id' => $this->website_id,'nm.distributor_apply'=>['neq','']],'nm.reg_time desc');
            }
            if($list['data']){
                $list['data'] = objToArr($list['data']);
                foreach ($list['data'] as $k => $v) {
                    $list['data'][$k]["distributor_apply"] = json_decode(htmlspecialchars_decode($list['data'][$k]["distributor_apply"]),true);
                    $list['data'][$k]["custom_data"] = $list['data'][$k]["distributor_apply"];
                    if($list['data'][$k]["custom_data"]){
                        foreach ($list['data'][$k]["custom_data"] as $k1 => $v1) {
                            if($v1['tag']=='img' && $v1['value']){
                                $list['data'][$k]["custom_data"][$k1]['value'] = explode(',',$v1['value']);
                            }
                            if($v1['tag']=="date" && $list['data'][$k]["custom_data"][$k1]['value']){
                                $list['data'][$k]["custom_data"][$k1]['value'] = date("Y-m-d H:i:s",$list['data'][$k]["custom_data"][$k1]['value']);
                            }
                            if($v1['tag']=="area" && $list['data'][$k]["custom_data"][$k1]['value']){
                                $list['data'][$k]["custom_data"][$k1]['value'] = substr($list['data'][$k]["custom_data"][$k1]['value'],0,strpos($list['data'][$k]["custom_data"][$k1]['value'],","));
                            }
                            if($v1['tag']=="date_range" && $list['data'][$k]["custom_data"][$k1]['value']){
                                $dates = explode(',',$list['data'][$k]["custom_data"][$k1]['value']);
                                $date1 = date("Y-m-d H:i:s",$dates[0]);
                                $date2 = date("Y-m-d H:i:s",$dates[1]);
                                $list['data'][$k]["custom_data"][$k1]['value'] = $date1.'~'.$date2;
                            }
                        }
                    }
                }
            }
        }
        if($usage==7) {
            $channel = new VslChannelModel();
            if($search_text){
                $list = $channel->getViewList($page_index, $page_size, ['nm.website_id' => $this->website_id,'nm.channel_custom'=>['like','%'.$search_text.'%']], 'nm.create_time desc');
            }else{
                $list = $channel->getViewList($page_index, $page_size, ['nm.website_id' => $this->website_id,'nm.channel_custom'=>['neq','']], 'nm.create_time desc');
            }
            if($list['data']){
                $list['data'] = objToArr($list['data']);
                foreach ($list['data'] as $k => $v) {
                    $list['data'][$k]["channel_custom"] = json_decode(htmlspecialchars_decode($list['data'][$k]["channel_custom"]),true);
                    $list['data'][$k]["custom_data"] = $list['data'][$k]["channel_custom"];
                    if($list['data'][$k]["custom_data"]){
                        foreach ($list['data'][$k]["custom_data"] as $k1 => $v1) {
                            if($v1['tag']=='img' && $v1['value']){
                                $list['data'][$k]["custom_data"][$k1]['value'] = explode(',',$v1['value']);
                            }
                            if($v1['tag']=="date" && $list['data'][$k]["custom_data"][$k1]['value']){
                                $list['data'][$k]["custom_data"][$k1]['value'] = date("Y-m-d H:i:s",$list['data'][$k]["custom_data"][$k1]['value']);
                            }
                            if($v1['tag']=="area" && $list['data'][$k]["custom_data"][$k1]['value']){
                                $list['data'][$k]["custom_data"][$k1]['value'] = substr($list['data'][$k]["custom_data"][$k1]['value'],0,strpos($list['data'][$k]["custom_data"][$k1]['value'],","));
                            }
                            if($v1['tag']=="date_range" && $list['data'][$k]["custom_data"][$k1]['value']){
                                $dates = explode(',',$list['data'][$k]["custom_data"][$k1]['value']);
                                $date1 = date("Y-m-d H:i:s",$dates[0]);
                                $date2 = date("Y-m-d H:i:s",$dates[1]);
                                $list['data'][$k]["custom_data"][$k1]['value'] = $date1.'~'.$date2;
                            }
                        }
                    }
                }
            }
        }
        return $list;
    }
    public function getCustomDatas($page_index,$page_size,$usage,$search_text=''){
        if($usage==1){
            $order = new VslOrderModel();
            if($search_text){
                $list = $order->getViewList($page_index,$page_size,['nm.website_id' => $this->website_id,'nm.custom_order'=>['like','%'.$search_text.'%']],'nm.create_time desc');
            }else{
                $list = $order->getViewList($page_index,$page_size,['nm.website_id' => $this->website_id,'nm.custom_order'=>['neq','']],'nm.create_time desc');
            }
            if($list['data']){
                foreach ($list['data'] as $k => $v) {
                    $list['data'][$k]["custom_order"] = json_decode(htmlspecialchars_decode($list['data'][$k]["custom_order"]),true);
                    $list['data'][$k]["custom_data"] = $list['data'][$k]["custom_order"];
                    unset($list['data'][$k]["custom_order"]);
                }
            }
        }
        if($usage==2){
            $user = new UserModel();
            if($search_text){
                $list = $user->pageQuery($page_index,$page_size,['website_id' => $this->website_id,'custom_person'=>['like','%'.$search_text.'%']],'reg_time desc','custom_person,uid,nick_name,user_name,user_headimg,user_tel');
            }else{
                $list = $user->pageQuery($page_index,$page_size,['website_id' => $this->website_id,'custom_person'=>['neq','']],'reg_time desc','custom_person,uid,nick_name,user_name,user_headimg,user_tel');
            }
            if($list['data']){
                foreach ($list['data'] as $k => $v) {
                    $list['data'][$k]["custom_person"] = json_decode(htmlspecialchars_decode($list['data'][$k]["custom_person"]),true);
                    $list['data'][$k]["custom_data"] = $list['data'][$k]["custom_person"]['form_data'];
                    unset($list['data'][$k]["custom_person"]);
                }
            }
        }
        if($usage==5){
            $member = new VslMemberModel();
            if($search_text){
                $list = $member->getViewList($page_index,$page_size,['nm.website_id' => $this->website_id,'nm.custom_area'=>['like','%'.$search_text.'%']],'nm.reg_time desc');
            }else{
                $list = $member->getViewList($page_index,$page_size,['nm.website_id' => $this->website_id,'nm.custom_area'=>['neq','']],'nm.reg_time desc');
            }
            if($list['data']){
                foreach ($list['data'] as $k => $v) {
                    $list['data'][$k]["custom_area"] = json_decode(htmlspecialchars_decode($list['data'][$k]["custom_area"]),true);
                    $list['data'][$k]["custom_data"] = $list['data'][$k]["custom_area"];
                    unset($list['data'][$k]["custom_area"]);
                }
            }
        }
        if($usage==4){
            $member = new VslMemberModel();
            if($search_text){
                $list = $member->getViewList($page_index,$page_size,['nm.website_id' => $this->website_id,'nm.custom_global'=>['like','%'.$search_text.'%']],'nm.reg_time desc');
            }else{
                $list = $member->getViewList($page_index,$page_size,['nm.website_id' => $this->website_id,'nm.custom_global'=>['neq','']],'nm.reg_time desc');
            }
            if($list['data']){
                foreach ($list['data'] as $k => $v) {
                    $list['data'][$k]["custom_global"] = json_decode(htmlspecialchars_decode($list['data'][$k]["custom_global"]),true);
                    $list['data'][$k]["custom_data"] = $list['data'][$k]["custom_global"];
                    unset($list['data'][$k]["custom_global"]);
                }
            }
        }
        if($usage==6){
            $member = new VslMemberModel();
            if($search_text){
                $list = $member->getViewList($page_index,$page_size,['nm.website_id' => $this->website_id,'nm.custom_team'=>['like','%'.$search_text.'%']],'nm.reg_time desc');
            }else{
                $list = $member->getViewList($page_index,$page_size,['nm.website_id' => $this->website_id,'nm.custom_team'=>['neq','']],'nm.reg_time desc');
            }
            if($list['data']){
                foreach ($list['data'] as $k => $v) {
                    $list['data'][$k]["custom_team"] = json_decode(htmlspecialchars_decode($list['data'][$k]["custom_team"]),true);
                    $list['data'][$k]["custom_data"] = $list['data'][$k]["custom_team"];
                    unset($list['data'][$k]["custom_team"]);
                }
            }
        }
        if($usage==3){
            $member = new VslMemberModel();
            if($search_text){
                $list =  $member->getViewList($page_index,$page_size,['nm.website_id' => $this->website_id,'nm.distributor_apply'=>['like','%'.$search_text.'%']],'nm.reg_time desc');
            }else{
                $list =  $member->getViewList($page_index,$page_size,['nm.website_id' => $this->website_id,'nm.distributor_apply'=>['neq','']],'nm.reg_time desc');
            }
            if($list['data']){
                foreach ($list['data'] as $k => $v) {
                    $list['data'][$k]["distributor_apply"] = json_decode(htmlspecialchars_decode($list['data'][$k]["distributor_apply"]),true);
                    $list['data'][$k]["custom_data"] = $list['data'][$k]["distributor_apply"];
                    unset($list['data'][$k]["distributor_apply"]);
                }

            }
        }
        if($usage==7) {
            $channel = new VslChannelModel();
            if($search_text){
                $list = $channel->getViewList($page_index, $page_size, ['nm.website_id' => $this->website_id,'nm.channel_custom'=>['like','%'.$search_text.'%']], 'nm.create_time desc');
            }else{
                $list = $channel->getViewList($page_index, $page_size, ['nm.website_id' => $this->website_id,'nm.channel_custom'=>['neq','']], 'nm.create_time desc');
            }
            if($list['data']){
                foreach ($list['data'] as $k => $v) {
                    $list['data'][$k]["channel_custom"] = json_decode(htmlspecialchars_decode($list['data'][$k]["channel_custom"]),true);
                    $list['data'][$k]["custom_data"] = $list['data'][$k]["channel_custom"];
                    unset($list['data'][$k]["channel_custom"]);
                }
            }
        }
        return $list;
    }
}