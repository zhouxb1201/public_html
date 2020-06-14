<?php
namespace data\model;

use data\model\BaseModel as BaseModel;
/**
 * 前台会员视图表
 * @author  www.vslai.com
 *
 */
class VslMemberViewModel extends BaseModel {
    protected $table = 'vsl_member';
    /**
     * 获取列表返回数据格式
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @return unknown
     */
    public function getViewList($page_index, $page_size, $condition, $order){
    
        $queryList = $this->getViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    /**
     * 获取列表
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @return \data\model\multitype:number
     */
    public function getViewQuery($page_index, $page_size, $condition, $order)
    {
        //设置查询视图
        $viewObj = $this->alias('nm')
        ->join('vsl_member_level nml','nm.member_level = nml.level_id','left')
        ->join('sys_user su','nm.uid= su.uid','left')
        ->join('sys_user sus','nm.referee_id= sus.uid','left')
        ->join('sys_country_code scc','su.country_code= scc.country_code','left')
        ->join('sys_country_code sccs','sus.country_code= sccs.country_code','left')
        ->field('nm.uid, nm.member_level,nm.default_referee_id,nm.isdistributor,nml.level_name,nm.growth_num, nm.group_id,su.real_name,nm.reg_time, nm.memo, nml.level_name, nml.goods_discount, su.uid,su.user_headimg, su.instance_id, su.user_name, su.user_status, su.user_headimg, su.is_system, su.is_member, su.user_tel, su.user_tel_bind, su.user_qq, su.qq_openid, su.qq_info, su.user_email, su.user_email_bind, su.wx_openid, su.wx_sub_time, su.wx_notsub_time, su.wx_is_sub, su.wx_info, su.other_info, su.reg_time, su.current_login_ip, su.current_login_time, su.current_login_type, su.last_login_time, su.last_login_ip, su.last_login_type, su.login_num, su.real_name, su.sex, su.birthday, su.location, su.nick_name, su.wx_unionid, su.qrcode_template_id,nm.referee_id, sus.user_name as referee_user_name, sus.nick_name as referee_nick_name, sus.user_tel as referee_user_tel, sus.user_headimg as referee_user_headimg, nm.extend_code,scc.country,su.country_code,scc.country_code_long,sccs.country as refree_country,sccs.country_code as refree_country_code,sccs.country_code_long as refree_country_code_long');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    /**
     * 获取列表数量
     * @param unknown $condition
     * @return \data\model\unknown
     */
    public function getViewCount($condition)
    {
        $viewObj = $this->alias('nm')
        ->join('vsl_member_level nml','nm.member_level = nml.level_id','left')
        ->join('sys_user su','nm.uid= su.uid','left')
        ->field('nm.uid');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }/**
      * 获取微店店主列表，数量
      * @param unknown $condition
      * @return \data\model\unknown
     */
    public function getShopKeeperViewList($page_index, $page_size, $condition, $order){
        $queryList = $this->getShopKeeperViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getShopKeeperViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    public function getShopKeeperViewQuery($page_index, $page_size, $condition, $order)
    {
        //设置查询视图
        $viewObj = $this->alias('nm')
            ->join('vsl_microshop_level nml','nm.microshop_level_id = nml.id','left')
            ->join('vsl_member_level ml','nm.member_level = ml.level_id','left')
            ->join('sys_user us','nm.uid = us.uid','left')
            ->field('nm.*, nml.level_name,ml.level_name as member_level_name,us.user_headimg,us.user_tel,us.nick_name,us.user_name,us.real_name');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    public function getShopKeeperViewCount($condition)
    {
        $viewObj = $this->alias('nm')
            ->join('vsl_microshop_level nml','nm.microshop_level_id = nml.id','left')
            ->join('vsl_member_level ml','nm.member_level = ml.level_id','left')
            ->join('sys_user us','nm.uid = us.uid','left')
            ->field('nm.uid');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }
    /**
     * 获取分销商列表，数量
     * @param unknown $condition
     * @return \data\model\unknown
     */
    public function getDistributorViewList($page_index, $page_size, $condition, $order){
        $queryList = $this->getDistributorViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getDistributorViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    public function getCustomerViewList($page_index, $page_size, $condition, $order){
        $queryList = $this->getCustomerViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getCustomerViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    public function getCustomerViewQuery($page_index, $page_size, $condition, $order)
    {
        //设置查询视图
        $viewObj = $this->alias('nm')
            ->join('vsl_member_level ml','nm.member_level = ml.level_id','left')
            ->join('sys_user us','nm.uid = us.uid','left')
            ->field('ml.level_name as member_level_name,us.user_headimg,us.nick_name,us.user_name,us.uid');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    public function getCustomerViewCount($condition)
    {
        $viewObj = $this->alias('nm')
            ->join('vsl_member_level ml','nm.member_level = ml.level_id','left')
            ->join('sys_user us','nm.uid = us.uid','left')
            ->field('nm.uid');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }
    public function getDistributorViewQuery($page_index, $page_size, $condition, $order)
    {
        //设置查询视图
        $viewObj = $this->alias('nm')
            ->join('vsl_distributor_level nml','nm.distributor_level_id = nml.id','left')
            ->join('vsl_member_level ml','nm.member_level = ml.level_id','left')
            ->join('sys_user us','nm.uid = us.uid','left')
            ->field('nm.*, nml.level_name,ml.level_name as member_level_name,us.user_headimg,us.nick_name,us.user_name,us.real_name');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    public function getDistributorViewCount($condition)
    {
        $viewObj = $this->alias('nm')
            ->join('vsl_distributor_level nml','nm.distributor_level_id = nml.id','left')
            ->join('vsl_member_level ml','nm.member_level = ml.level_id','left')
            ->join('sys_user us','nm.uid = us.uid','left')
            ->field('nm.uid');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }
    /**
     * 获取全球分红代理商列表，数量
     * @param unknown $condition
     * @return \data\model\unknown
     */
    public function getAgentViewList($page_index, $page_size, $condition, $order){
        $queryList = $this->getAgentViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getAgentViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    public function getAgentViewQuery($page_index, $page_size, $condition, $order)
    {
        //设置查询视图
        $viewObj = $this->alias('nm')
            ->join('vsl_agent_level nml','nm.global_agent_level_id = nml.id','left')
            ->join('sys_user us','nm.uid = us.uid','left')
            ->field('nm.*, nml.level_name,us.user_headimg,us.real_name,us.user_name,us.nick_name,us.user_tel');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    public function getAgentViewCount($condition)
    {
        $viewObj = $this->alias('nm')
            ->join('vsl_agent_level nml','nm.global_agent_level_id = nml.id','left')
            ->join('sys_user us','nm.uid = us.uid','left')
            ->field('nm.uid');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }
    /**
     * 获取区域分红代理商列表，数量
     * @param unknown $condition
     * @return \data\model\unknown
     */
    public function getAreaAgentViewList($page_index, $page_size, $condition, $order){
        $queryList = $this->getAreaAgentViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getAreaAgentViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    public function getAreaAgentViewQuery($page_index, $page_size, $condition, $order)
    {
        //设置查询视图
        $viewObj = $this->alias('nm')
            ->join('vsl_agent_level nml','nm.area_agent_level_id = nml.id','left')
            ->join('sys_user us','nm.uid = us.uid','left')
            ->field('nm.*, nml.level_name,us.user_headimg,us.real_name,us.user_name,us.nick_name');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    public function getAreaAgentViewCount($condition)
    {
        $viewObj = $this->alias('nm')
            ->join('vsl_agent_level nml','nm.area_agent_level_id = nml.id','left')
            ->join('sys_user us','nm.uid = us.uid','left')
            ->field('nm.uid');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }
    public function getTeamAgentViewList($page_index, $page_size, $condition, $order){
        $queryList = $this->getTeamAgentViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getTeamAgentViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    public function getTeamAgentViewQuery($page_index, $page_size, $condition, $order)
    {
        //设置查询视图
        $viewObj = $this->alias('nm')
            ->join('vsl_agent_level nml','nm.team_agent_level_id = nml.id','left')
            ->join('sys_user us','nm.uid = us.uid','left')
            ->field('nm.*, nml.level_name,us.user_headimg,us.real_name,us.user_name,us.nick_name');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    public function getTeamAgentViewCount($condition)
    {
        $viewObj = $this->alias('nm')
            ->join('vsl_agent_level nml','nm.team_agent_level_id = nml.id','left')
            ->join('sys_user us','nm.uid = us.uid','left')
            ->field('nm.uid');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }
}
