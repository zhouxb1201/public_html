<?php
namespace app\platform\controller;

use addons\goodhelper\model\VslGoodsHelpModel;
use addons\goodhelper\server\GoodHelper as GoodHelperServer;
use addons\goodhelper\server\GoodHelper;
use addons\invoice\model\VslInvoiceFileModel;
use addons\invoice\server\Invoice as InvoiceServer;
use data\model\ConfigModel;
use data\model\VslMemberModel;
use data\model\WebSiteModel;
use data\service\AddonsConfig;
use data\service\Album as Album;
use data\service\Goods as Goods;
use data\model\AlbumPictureModel as AlbumPictureModel;
use think\Exception;
use \think\Session as Session;
use data\model\AdminUserModel as AdminUserModel;
use data\model\AuthGroupModel as AuthGroupModel;
use data\service\User;
use think\db;
use addons\helpcenter\server\Helpcenter as helpServer;
use data\model\VslIncreMentOrderModel;
use data\model\ModuleModel;
use data\model\SysAddonsModel;
use data\service\Upload\AliOss;
use addons\bonus\model\VslAgentLevelModel;
use data\service\WebSite;
/**
 * 系统模块控制器
 *
 * @author  www.vslai.com
 *        
 */
class System extends BaseController
{
    private  $members=[];
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 更新缓存
     */
    public function deleteCache()
    {
        $model = \think\Request::instance()->module();
        $admin = new AdminUserModel();
        $admin_info = $admin->getInfo('uid=' . Session::get($model.'uid'), 'is_admin,group_id_array');
        $auth_group = new AuthGroupModel();
        $auth = $auth_group->get($admin_info['group_id_array']);
        $system_auth = $auth_group->getInfo(['is_system'=>1,'instance_id'=>0,'website_id'=>$this->website_id],'order_id,group_id');
        if($system_auth['order_id']){
            $bonus_id = [];
            $unbonus_id = [];
            $module = new ModuleModel();
            $addonsmodel = new SysAddonsModel();
            $module_infoId = $module->getInfo(['method'=>'bonusRecordList','module'=>'platform'],'module_id')['module_id'];
            $area_moduleId = $addonsmodel->getInfo(['name'=>'areabonus'],'module_id')['module_id'];
            $global_moduleId = $addonsmodel->getInfo(['name'=>'globalbonus'],'module_id')['module_id'];
            $team_moduleId = $addonsmodel->getInfo(['name'=>'teambonus'],'module_id')['module_id'];
            $order_ids = explode(',',$system_auth['order_id']);
            $order = new VslIncreMentOrderModel();
            $module_id_arrays = ',';
            $shop_module_id_arrays=',';
            $default_module_id_array = explode(',',$auth['module_id_array']);
            $default_shop_module_id_array = explode(',',$auth['shop_module_id_array']);
            foreach ($order_ids as $value){
                $addons_id = $order->getInfo(['order_id'=>$value],'*');
                $module_id = $module->Query(['addons_sign'=>$addons_id['addons_id'],'module'=>'platform'],'module_id');
                $shop_module_id = $module->Query(['addons_sign'=>$addons_id['addons_id'],'module'=>'admin'],'module_id');
                if(in_array($global_moduleId,$module_id) || in_array($area_moduleId,$module_id) || in_array($team_moduleId,$module_id)){
                    $bonus_id[] = $module_infoId;
                }
                if($addons_id['expire_time']>time()){
                    $module_id_array = implode(',',$module_id);
                    $shop_module_id_array = implode(',',$shop_module_id);
                    $module_id_arrays .= ','.$module_id_array;
                    $shop_module_id_arrays.= $shop_module_id_array;
                }else{
                    if(in_array($global_moduleId,$module_id) || in_array($area_moduleId,$module_id) || in_array($team_moduleId,$module_id)){
                        $unbonus_id[] = $module_infoId;
                    }
                    $default_module_id_array = array_diff($default_module_id_array,$module_id);
                    $default_shop_module_id_array= array_diff($default_shop_module_id_array,$shop_module_id);
                }
            }
            $auth['module_id_array'] = implode(',',$default_module_id_array).$module_id_arrays;
            $auth['shop_module_id_array'] = implode(',',$default_shop_module_id_array).$shop_module_id_arrays;
            if(count($bonus_id)==count($unbonus_id) && $bonus_id){
                $unid = [];
                $real_module_id_array = explode(',',$auth['module_id_array']);
                $unid[] = $bonus_id[0];
                $auth['module_id_array'] = implode(',',array_diff($real_module_id_array,$unid));
            }
        }
        Session::set('addons_sign_module', []);
        $user = new User();
        $no_control = $user->getNoControlAuth();
        $module = new ModuleModel();
        $addons = new SysAddonsModel();
        $addons_sign_module = '';
        $up_status_ids = $addons->Query(['up_status'=>2],'id');
        if($up_status_ids){
            foreach ($up_status_ids as $v){
                $addons_sign_module .= ','.implode(',',$module->Query(['addons_sign' => $v],'module_id'));
            }
            if($addons_sign_module){
                $addons_sign_modules = explode(',',$addons_sign_module);
                foreach($addons_sign_modules as $k=>$v){
                    if( !$v )
                        unset($addons_sign_modules[$k] );
                }
                Session::set('addons_sign_module', $addons_sign_modules);
            }
        }
        Session::set($model.'module_id_array', $no_control.$auth['module_id_array']);
        Session::set($model.'shop_module_id_array', $no_control.$auth['shop_module_id_array']);
        Session::set('module_list', []);
        Session::set($model.'module_list', []);
        $retval = VslDelDir('./runtime/cache');
        $website = new WebSite();
        $dateArr = $website->getWebCreateTime($this->website_id);
        $path = '/public/addons_status/' . $dateArr['year'].'/'.$dateArr['month'].'/'.$dateArr['day'].'/'. $this->website_id;
        VslDelDir('.' . $path);
        
        return $retval;
    }

    /**
     * 图片选择
     */
    public function dialogAlbumList()
    {
        $query = $this->getAlbumClassALL();
        $this->assign("number", isset($_GET['number']) ? $_GET['number'] : 1);
        $this->assign("album_list", $query);
        return view($this->style . "System/dialogAlbumList");
    }

    /**
     * 获取图片分组
     */
    public function albumList()
    {
        if (request()->isAjax()) {
            $page_index = request()->post("page_index", 1);
            $page_size = request()->post('page_size', PAGESIZE);
            $search_text = isset($_POST['album_name']) ? $_POST['album_name'] : '';
            $album = new Album();
            $condition = array(
                'shop_id' => $this->instance_id,
                'album_name' => array(
                    'like',
                    '%' . $search_text . '%'
                ),
                'website_id'=>$this->website_id
            );
            $retval = $album->getAlbumClassList($page_index, $page_size, $condition);
            return $retval;
        } else {
            $album_list = $this->getAlbumClassALL();
            $album = new Album();
            $default_album_detail = $album -> getDefaultAlbumDetail();
            $this->assign('default_album_id', $default_album_detail['album_id']);
            $this->assign('album_list', $album_list);
            return view($this->style . "System/albumList");
        }
    }

    /**
     * 创建相册
     */
    public function addAlbumClass()
    {
        $album_name = $_POST['album_name'];
        $sort = isset($_POST['sort']) ? $_POST['sort'] : 0;
        $album = new Album();
        $retval = $album->addAlbumClass($album_name, $sort, 0, '', 0, $this->instance_id);
        return AjaxReturn($retval);
    }

    /**
     * 删除相册
     */
    public function deleteAlbumClass()
    {
        $defult_album = $this->get_default_album();
        $defult_id = $defult_album['album_id'];  //默认ID

        $aclass_id_array = $_POST['aclass_id_array'];  //删除ID
        $album = new Album();
        //将删除ID变成默认ID
        $data['album_id'] = $defult_id;
        $where['album_id'] = $aclass_id_array;
        $album->update_album_id($data,$where);
        $retval = $album->deleteAlbumClass($aclass_id_array);
        return AjaxReturn($retval);
    }
    /**
     * 图片空间弹窗 相册图片获取
     */
    public function getAlbunPic(){
        $album = new Album();
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post("page_size", PAGESIZE);
        $album_id = intval(request()->post('album_id', 0));
        $file_type = intval(request()->post('file_type', 0));
        if(!$album_id){
            $album_id = $album->getDefaultAlbumDetail()['album_id'];
        }
        $sort_name = intval(request()->post('sort_name', 0));
        $condition['album_id'] = $album_id;
        $condition['is_wide'] = $file_type;
        $order='upload_time desc';
        if (0 < $sort_name) {
            switch ($sort_name) {
                case '1':
                    $order = 'upload_time asc';
                    break;

                case '2':
                    $order = 'upload_time desc';
                    break;

                case '3':
                    $order = 'pic_size asc';
                    break;

                case '4':
                    $order = 'pic_size desc';
                    break;

                case '5':
                    $order = 'pic_name asc';
                    break;

                case '6':
                    $order = 'pic_name desc';
                    break;
            }
        }
        $list = $album->getPictureList($page_index, $page_size, $condition,$order);
        return $list;
    }
    /**
     * 相册图片列表
     */
    public function albumPictureList()
    {
        if (request()->isAjax()) {
            $page_index = request()->post("page_index", 1);
            $page_size = request()->post("page_size", PAGESIZE);
            $album_id = isset($_POST["album_id"]) ? $_POST["album_id"] : 0;
            $is_use = isset($_POST["is_use"]) ? $_POST["is_use"] : 0;
            $condition = array();
            $condition["album_id"] = $album_id;
            if($_REQUEST['pic_name']){
                $condition['pic_name'] = ['like',"%".$_REQUEST['pic_name']."%"];
            }
            $album = new Album();
            if ($is_use > 0) {
                $img_array = $album->getGoodsAlbumUsePictureQuery([
                    "shop_id" => $this->instance_id
                ]);
                if (! empty($img_array)) {
                    $img_string = implode(",", $img_array);
                    $condition["pic_id"] = [
                        "not in",
                        $img_string
                    ];
                }
            }
            $list = $album->getPictureList($page_index, $page_size, $condition);
            
            return $list;
        } else {
            $album_list = $this->getAlbumClassALL();
            $this->assign('album_list', $album_list);
            $album_id = isset($_GET["album_id"]) ? $_GET["album_id"] : 0;
            $album_name = Db::table('sys_album_class')->where('album_id',$album_id)->value('album_name');
            $domain = request()->domain();
            $this->assign("album_name", $album_name);
            $this->assign("album_id", $album_id);
            $this->assign("domain", $domain);
            return view($this->style . "System/albumPictureList");
        }
    }

    /**
     * 相册图片列表
     */
    public function dialogAlbumPictureList()
    {
        if (request()->isAjax()) {
            $page_index = $_POST["pageIndex"];
            $album_id = $_POST["album_id"];
            $condition = "album_id = $album_id";
            $album = new Album();
            $list = $album->getPictureList($page_index, 10, $condition);
            foreach ($list["data"] as $k => $v) {
                $list["data"][$k]["upload_time"] = date("Y-m-s", strtotime($v["upload_time"]));
            }
            return $list;
        } else {
            return view($this->style . "System/dialogAlbumPictureList");
        }
    }

    //修改图片名称
    public function update_pic_name(){

        $id = $_REQUEST['id'];
        $name = $_REQUEST['name'];
        if(empty($name)){
            return AjaxReturn('-1','图片名称不能为空');
        }

        $album = new AlbumPictureModel();
        $album->save(['pic_name'=>$name],['pic_id'=>$id]);
        return AjaxReturn('1','修改成功');
    }
    /**
     * 删除图片
     *
     * @param unknown $pic_id_array            
     * @return unknown
     */
    public function deletePicture()
    {
        $pic_id_array = $_POST["pic_id_array"];
        $album = new Album();
        $retval = $album->deletePicture($pic_id_array);
        return AjaxReturn($retval);
    }

    /**
     * 获取相册详情
     *
     * @return Ambigous <\think\static, multitype:, \think\db\false, PDOStatement, string, \think\Model, \PDOStatement, \think\db\mixed, multitype:a r y s t i n g Q u e \ C l o , \think\db\Query, NULL>
     */
    public function getAlbumClassDetail()
    {
        $album_id = $_POST["album_id"];
        $album = new Album();
        $retval = $album->getAlbumClassDetail($album_id);
        return $retval;
    }

    /**
     * 修改相册
     */
    public function updateAlbumClass()
    {
        $album_id = $_POST["album_id"];
        $aclass_name = $_POST["album_name"];
        $aclass_sort = $_POST["sort"];
        $album = new Album();
        $retval = $album->updateAlbumClass($album_id, $aclass_name, $aclass_sort, 0);
        return AjaxReturn($retval);
    }

    /**
     * 删除制定路径文件
     */
    function delete_file()
    {
        $file_url = isset($_POST['file_url']) ? $_POST['file_url'] : '';
        if (file_exists($file_url)) {
            @unlink($file_url);
            $retval = array(
                'code' => 1,
                'message' => '文件删除成功'
            );
        } else {
            $retval = array(
                'code' => 0,
                'message' => '文件不存在'
            );
        }
        return $retval;
    }

    /**
     * 获取所有相册
     */
    public function getAlbumClassALL()
    {
        $album = new Album();
        $retval = $album->getAlbumClassAll([
            'shop_id' => $this->instance_id,
            'website_id' => $this->website_id
        ]);
        return $retval;
    }

    //添加消息弹出层
    public function addMsg()
    {
         return view($this->style . "System/addMsgDialog");
    
    }

    /**
     * 图片名称修改
     */
    public function modifyAlbumPictureName()
    {
        $pic_id = $_POST["pic_id"];
        $pic_name = $_POST["pic_name"];
        $album = new Album();
        $retval = $album->ModifyAlbumPictureName($pic_id, $pic_name);
        return AjaxReturn($retval);
    }

    /**
     * 转移图片所在相册
     */
    public function modifyAlbumPictureClass()
    {
        $pic_id = $_POST["pic_id"];
        $album_id = $_POST["album_id"];
        $album = new Album();
        $retval = $album->ModifyAlbumPictureClass($pic_id, $album_id);
        return AjaxReturn($retval);
    }

    /**
     * 设此图片为本相册的封面
     */
    function modifyAlbumClassCover()
    {
        $pic_id = $_POST["pic_id"];
        $album_id = $_POST["album_id"];
        $album = new Album();
        $retval = $album->ModifyAlbumClassCover($pic_id, $album_id);
        return AjaxReturn($retval);
    }
    /**
     * 搜索商品
     */
    public function searchGoods()
    {
        $goods_name = request()->post('goods_name', '');
        $category_id = request()->post('category_id', '');
        $category_level = request()->post('category_level', '');
        $where['ng.goods_name'] = array(
            'like',
            '%' . $goods_name . '%'
        );
        $where['ng.category_id_' . $category_level] = $category_id;
        $where['ng.state'] = 1;
        if($this->shopStatus==0){
            $where['ng.shop_id'] = 0;
        }
        $where['ng.website_id'] = $this->website_id;
        $where = array_filter($where);
        $goods = new Goods();
        $list = $goods->getGoodsList(1, 0, $where);
        return $list;
    }


    //移动图片
    public function move_pic(){

        $album = $this->getAlbumClassALL();
        $this->assign("album",$album);
        return view($this->style . "System/movePic");
    }
    /**
     * 修改上级分销商
     */
    public function updateReferee()
    {
        $uid = $_GET['uid'];
        $referee_name = $_GET['name'];
        $this->assign('uid',$uid);
        $this->assign('name',$referee_name);
        $this->GetTeamMember($uid);
        if($this->members){
            $ids = implode(',',$this->members);
            $this->assign('lower_id',$ids);
        }
        $this->assign('refereeDistributorListUrl', __URL(addons_url_platform('distribution://distribution/refereeDistributorList')));
        return view($this->style . "System/updateReferee");
    }
    /**
     * 选择区域
     */
    public function updateReferee3()
    {
        //获取所有区域等级
        
        $this->assign('getProvinceUrl', __URL(addons_url_platform('distribution://distribution/getProvince')));
        $this->assign('getCityUrl', __URL(addons_url_platform('distribution://distribution/getCity')));
        $this->assign('getDistrictUrl', __URL(addons_url_platform('distribution://distribution/getDistrict')));
        $agent_level = new VslAgentLevelModel();
        $list=[];
        $list2= $agent_level->pageQuery(1,0,['website_id' => $this->website_id,'from_type'=>2,'is_default'=>1],'','id,level_name');
        $agent_info['area'] = $list2['data'];
        $this->assign('agent_info',$agent_info);
        return view($this->style . "System/updateReferee3");
    }
    /**
     * 修改上级分销商
     */
    public function updateReferees()
    {
        $uid = $_GET['uid'];
        $this->assign('uid',$uid);
        $this->GetTeamMember($uid);
        if($this->members){
            $ids = implode(',',$this->members);
            $this->assign('lower_id',$ids);
        }
        $this->assign('refereeDistributorListUrl', __URL(addons_url_platform('distribution://distribution/refereeDistributorList')));
        return view($this->style . "System/updateReferees");
    }
    public  function  GetTeamMember($mid){
        $member = new VslMemberModel();
        $Tempmerbers=$member->Query(['referee_id'=>$mid,'isdistributor'=>2,'website_id'=>$this->website_id],'uid');//查询id为mid的用户的直接下级
        $this->members = array_merge($this->members,$Tempmerbers);//查询结果保存到私有属性members中
        if(count($Tempmerbers)>0){//再将上面查询到的直接下级递归查询下级
            foreach ($Tempmerbers as $value) {
                $this->GetTeamMember($value);
            }
        }
    }
    //获取默认相册
    public function get_default_album(){

        $album = new Album();
        $default_album = $album->getDefaultAlbumDetail();
        return $default_album;
    }
    /**
     * 操作日志
     */
    public function operationLog() {
        if (request()->isAjax()) {
            $page_index = request()->post('pageIndex', 1);
            $uid = request()->post('uid', 0);
            $search = request()->post('search_text', '');
            $condition = ['website_id' => $this->website_id];
            if ($uid) {
                $condition['uid'] = $uid;
            }
            if ($search) {
                $condition['module_name|data'] = array(
                    [
                        "like",
                        "%" . $search . "%"
                    ],
                    [
                        "like",
                        "%" . $search . "%"
                    ],
                    'or'
                );
            }
            $list = $this->user->getUserLogList($page_index, PAGESIZE, $condition, "create_time desc");
            if ($list['data']) {
                foreach ($list['data'] as $key => $val) {
                    if ($val['create_time']) {
                        $list['data'][$key]['create_time'] = date('Y-m-d H:i:s', $val['create_time']);
                    }
                    $userService = new User();
                    $userInfo = $userService->getUserInfoByUid($val['uid']);
                    $list['data'][$key]['user_name'] = $userInfo['user_name'];
                }
            }
            return $list;
        } else {
            $userList = $this->user->adminUserList(1, 0, ['sua.website_id' => $this->website_id],'sur.instance_id asc,sur.reg_time asc');
            $userList = $userList['data'];
            $this->assign('userlist', $userList);
            return view($this->style . 'System/operationLog');
        }
    }
    /**
     * 选择文章
     */
    public function selectQuestionList()
    {
        if (request()->isPost()) {
            $article = new helpServer();
            $page_index = request()->post('page_index', 1);
            $page_size = request()->post('page_size', PAGESIZE);
            $search_text = request()->post('search_text', '');
            $condition = array(
                'vq.title' => array(
                    'like',
                    '%' . $search_text . '%'
                )
            );
            $condition["vq.website_id"] = $this->website_id;
            $result = $article->questionList($page_index, $page_size, $condition);
            return $result;
        } else {
            return view($this->style . 'System/selectQuestion');
        }
    }
    public function getBuckets() {
        $Accesskey = request()->post("Accesskey", "");
        $Secretkey = request()->post("Secretkey", "");
        $aliOss = new AliOss();
        $buckets = $aliOss->attachment_alioss_buctkets($Accesskey, $Secretkey);
        if (!is_array($buckets)) {
            return AjaxReturn($buckets);
        }
        $bucket_datacenter = array(
            'oss-cn-hangzhou' => '杭州数据中心',
            'oss-cn-qingdao' => '青岛数据中心',
            'oss-cn-beijing' => '北京数据中心',
            'oss-cn-hongkong' => '香港数据中心',
            'oss-cn-shenzhen' => '深圳数据中心',
            'oss-cn-shanghai' => '上海数据中心',
            'oss-us-west-1' => '美国硅谷数据中心',
        );
        $bucket = array();
        foreach ($buckets as $key => $value) {
            $value['loca_name'] = $key . '@@' . $bucket_datacenter[$value['location']];
            $bucket[] = $value;
        }
        return AjaxReturn(1, $bucket);
    }
    /**
     * 修改阿里云配置
     */
    public function setStorageConfig() {

        $config_service = new \data\service\Config();
        $Accesskey = request()->post("Accesskey", "");
        $Secretkey = request()->post("Secretkey", "");
        $type = request()->post("type", "1");
        $location = request()->post("location", "");
        $endpoint = 'http://oss-cn-hangzhou.aliyuncs.com';
        if($location){
            $endpoint = 'http://'.$location.'.aliyuncs.com';
        }
        $res = $config_service->setUploadType($type,0);
        if ($res && $type==1) {
            return AjaxReturn(1);
        }
        if($Accesskey && $Secretkey){
            $aliOss = new AliOss();
            $buckets = $aliOss->attachment_alioss_buctkets($Accesskey, $Secretkey);
            list($Bucket, $url) = explode('@@', request()->post("Bucket", ""));
            $url = 'http://' . $Bucket . '.' . $url . '.aliyuncs.com';
            if (empty($buckets[$Bucket])) {
                return AjaxReturn(0);
            }
            $AliossUrl = trim(request()->post("AliossUrl", ""), '/');
            if ($AliossUrl) {
                if ((strpos($AliossUrl, 'http://') === false) && (strpos($AliossUrl, 'https://') === false)) {
                    $url = 'http://' . $AliossUrl;
                } else {
                    $url = $AliossUrl;
                }
            }
            $value = array(
                "Accesskey" => $Accesskey,
                "Secretkey" => $Secretkey,
                "Bucket" => $Bucket,
                "AliossUrl" => $url,
                "endPoint" => $endpoint,
            );
            $value = json_encode($value);
            $result = $config_service->setAliossConfig($value,0);
            return AjaxReturn($result);
        }
    }
    /**
     * 商家模块列表
     */
    public function platformModule() {
        $condition = array(
            'module' => 'platform',
            'pid' => 0
        );
        $frist_list = $this->website->getSystemModuleList(1, 0, $condition, 'sort asc', 'pid,sort,module_name,is_menu,module_id');
        $frist_list = $frist_list['data'];
        $moduleModel = new ModuleModel();
        foreach ($frist_list as $k => $v) {
            $submenu = $moduleModel->getInfo(['pid' => $v['module_id']],'module_id');
            $frist_list[$k]['sub_menu'] = 0;
            if ($submenu) {
                $frist_list[$k]['sub_menu'] = 1;
            }
        }
        $this->assign("list", $frist_list);
        $this->assign("type", 'platform');
        return view($this->style . 'Config/platformModule');
    }

    /**
     * 添加商家模块
     */
    public function addPlatformModule() {
        if (request()->isAjax()) {
            $module_name = $_POST['module_name'];
            $controller = $_POST['controller'];
            $module = $_POST['module'];
            $method = $_POST['method'];
            $pid = $_POST['pid'];
            $url = $_POST['url'];
            $is_menu = $_POST['is_menu'];
            $is_control_auth = $_POST["is_control_auth"]; // 是否控制权限
            $is_dev = $_POST['is_dev'];
            $sort = $_POST['sort'];
            $module_picture = $_POST['module_picture'];
            $desc = $_POST['desc'];
            $icon_class = $_POST['icon_class'];
            $jump = $_POST['jump'];
            $retval = $this->website->addSytemModule($module_name, $controller, $method, $pid, $url, $is_menu, $is_dev, $sort, $module_picture, $desc, $icon_class, $is_control_auth, $module, $jump,1);
            $this->deleteCache();
            return AjaxReturn($retval, $retval);
        }
        $condition = array(
            'pid' => 0,
            'module' => 'platform'
        );
        $frist_list = $this->website->getSystemModuleList(1, 100, $condition, 'pid,sort');
        $frist_list = $frist_list['data'];
        $list = array();
        foreach ($frist_list as $k => $v) {
            $submenu = $this->website->getSystemModuleList(1, 100, 'pid=' . $v['module_id'], 'pid,sort');
            $list[$k]['data'] = $v;
            $list[$k]['sub_menu'] = $submenu['data'];
        }
        unset($v);
        $this->assign("list", $list);
        $this->assign("module", 'platform');
        $pid = $_GET['pid'];
        $this->assign("pid", $pid);
        return view($this->style . 'Config/addPlatformModule');
    }

    /**
     * 修改模块
     */
    public function editModule() {
        if (request()->isAjax()) {
            $module_id = $_POST['module_id'];
            $module_name = $_POST['module_name'];
            $controller = $_POST['controller'];
            $method = $_POST['method'];
            $pid = $_POST['pid'];
            $url = $_POST['url'];
            $is_menu = $_POST['is_menu'];
            $is_dev = $_POST['is_dev'];
            $is_control_auth = $_POST['is_control_auth']; // 是否控制权限
            $sort = $_POST['sort'];
            $module_picture = $_POST['module_picture'];
            $desc = $_POST['desc'];
            $icon_class = $_POST['icon_class'];
            $jump = $_POST['jump'];
            $retval = $this->website->updateSystemModule($module_id, $module_name, $controller, $method, $pid, $url, $is_menu, $is_dev, $sort, $module_picture, $desc, $icon_class, $is_control_auth, $jump);
            $this->deleteCache();
            return AjaxReturn($retval);
        } else {
            $module_id = $_GET['module_id'];
            $module_info = $this->website->getSystemModuleInfo($module_id);
            $condition = array(
                'pid' => 0,
                'module' => $module_info['module']
            );
            if ($module_info['level'] == 1) {
                $frist_list = $this->website->getSystemModuleList(1, 100, $condition, 'pid,sort');
                $list = array();
                foreach ($frist_list['data'] as $k => $v) {
                    $submenu = $this->website->getSystemModuleList(1, 100, 'pid=' . $v['module_id'], 'pid,sort');
                    $list[$k]['data'] = $v;
                    $list[$k]['sub_menu'] = $submenu['data'];
                }
            } else
                if ($module_info['level'] == 2) {
                    $frist_list = $this->website->getSystemModuleList(1, 100, $condition, 'pid,sort');
                    $list = array();
                    foreach ($frist_list['data'] as $k => $v) {
                        $submenu = $this->website->getSystemModuleList(1, 100, 'pid=' . $v['module_id'], 'pid,sort');
                        $list[$k]['data'] = $v;
                        $list[$k]['sub_menu'] = $submenu['data'];
                    }
                } else
                    if ($module_info['level'] == 3) {
                        $frist_list = $this->website->getSystemModuleList(1, 100, $condition, 'pid,sort');
                        $frist_list = $frist_list['data'];
                        $list = array();
                        foreach ($frist_list as $k => $v) {
                            $submenu = $this->website->getSystemModuleList(1, 100, 'pid=' . $v['module_id'], 'pid,sort');
                            $list[$k]['data'] = $v;
                            $list[$k]['sub_menu'] = $submenu['data'];
                        }
                    }

            $this->assign('module_info', $module_info);
            $this->assign("list", $list);
            return view($this->style . 'Config/editModule');
        }
    }
    /**
     * 店铺模块
     */
    public function shopModule() {
        $condition = array(
            'pid' => 0,
            'module' => 'admin'
        );
        $frist_list = $this->website->getSystemModuleList(1, 0, $condition, 'pid,sort');
        $frist_list = $frist_list['data'];
        $moduleModel = new ModuleModel();
        foreach ($frist_list as $k => $v) {
            $submenu = $moduleModel->getInfo(['pid' => $v['module_id']],'module_id');
            $frist_list[$k]['sub_menu'] = 0;
            if ($submenu) {
                $frist_list[$k]['sub_menu'] = 1;
            }
        }
        $this->assign("list", $frist_list);
        $this->assign("type", 'admin');
        return view($this->style . 'Config/platformModule');
    }

    /**
     * 添加模块
     */
    public function addShopModule() {
        if (request()->isAjax()) {
            $module_name = $_POST['module_name'];
            $controller = $_POST['controller'];
            $module = $_POST['module'];
            $method = $_POST['method'];
            $pid = $_POST['pid'];
            $url = $_POST['url'];
            $is_menu = $_POST['is_menu'];
            $is_control_auth = $_POST["is_control_auth"]; // 是否控制权限
            $is_dev = $_POST['is_dev'];
            $sort = $_POST['sort'];
            $module_picture = $_POST['module_picture'];
            $desc = $_POST['desc'];
            $icon_class = $_POST['icon_class'];
            $jump = $_POST['jump'];
            $retval = $this->website->addSytemModule($module_name, $controller, $method, $pid, $url, $is_menu, $is_dev, $sort, $module_picture, $desc, $icon_class, $is_control_auth, $module, $jump,0,1);
            $this->deleteCache();
            return AjaxReturn($retval, $retval);
        }
        $condition = array(
            'pid' => 0,
            'module' => 'admin'
        );
        $this->assign("module", 'admin');
        $frist_list = $this->website->getSystemModuleList(1, 100, $condition, 'pid,sort');
        $frist_list = $frist_list['data'];
        $list = array();
        foreach ($frist_list as $k => $v) {
            $submenu = $this->website->getSystemModuleList(1, 100, 'pid=' . $v['module_id'], 'pid,sort');
            $list[$k]['data'] = $v;
            $list[$k]['sub_menu'] = $submenu['data'];
        }
        $this->assign("list", $list);
        $pid = $_GET['pid'];
        $this->assign("pid", $pid);
        return view($this->style . 'Config/addShopModule');
    }
    /**
     * 删除模块
     */
    public function delModule()
    {
        $module_id = $_POST['module_id'];
        $retval = $this->website->deleteSystemModule($module_id);
        $this->deleteCache();
        return AjaxReturn($retval);
    }
    /**
     * 修改单个字段
     */
    public function modifyField()
    {
        $fieldid = $_POST['fieldid'];
        $fieldname = $_POST['fieldname'];
        $fieldvalue = $_POST['fieldvalue'];
        $retval = $this->website->ModifyModuleField($fieldid, $fieldname, $fieldvalue);
        $this->deleteCache();
        return AjaxReturn($retval);
    }
    /*
   * 根据上级id获取菜单列表
   */
    public function getModuleListByParentId() {
        $pid = request()->post("pid", 1);
        $moduleList = $this->website->getModuleListByParentId($pid);
        if($moduleList){
            $moduleModel = new ModuleModel();
            foreach ($moduleList as $k => $v) {
                $submenu = $moduleModel->getInfo(['pid' => $v['module_id']],'module_id');
                $moduleList[$k]['sub_menu'] = 0;
                if ($submenu) {
                    $moduleList[$k]['sub_menu'] = 1;
                }
            }
        }
        return $moduleList;
    }
        /*
     * 账号体系
     */
    public function accountSystem(){
        return view($this->style . 'System/accountSystem');
    }
    /*
     * 保存账号体系类型
     * **/
    public function saveAccountType()
    {
        $account_type = request()->post('account_type', 0);
        $is_bind_phone = request()->post('is_bind_phone', 0);
        $mobile_type = request()->post('mobile_type', 0);
        $website = new WebSiteModel();
        $condition = ['website_id'=>$this->website_id];
        //先查出来是否已经设置过
        $account_info = $website->getInfo($condition, 'account_type');
        if($account_info['account_type'] != 0){
            return json(['code' => -1, 'message' => '您已经设置过账号体系了']);
        }
        $res = $website->save(['account_type' => $account_type, 'is_bind_phone' => $is_bind_phone,'mobile_type' => $mobile_type], $condition);
        if($res){
            $this->addUserLogByParam('添加账号体系类型', $res);
        }
        return ajaxReturn($res);
    }
    /*
     * 获取当前账号体系类型
     * **/
    public function getAccountType()
    {
        $website = new WebSiteModel();
        $account_type_arr = $website->getInfo(['website_id' => $this->website_id], 'account_type, is_bind_phone,mobile_type');
        return json($account_type_arr);
    }
    /*
     * 获取当前账号是否设置了账号体系
     * **/
    public function isSetAccountSystem()
    {
        $website_id = $this->website_id;
        $website = new WebSiteModel();
        $website->getInfo(['website_id' => $website_id], 'account_type')['account_type'];
    }
    /*
     * 获取当前商城是否开始移动商城
     * **/
    public function getWapConfig()
    {
        $web_info = $this->website->getWebSiteInfo();
        if($web_info['wap_status']==1){
            $data['wap_status'] = $web_info['wap_status'];
            if($web_info['realm_ip']){
                $data['wap_url'] = $this->http.$web_info['realm_ip'].'/wap/mall/index';
            }else{
                $ip = top_domain($_SERVER['HTTP_HOST']);
                $web_info['realm_two_ip'] = $web_info['realm_two_ip'].'.'.$ip;
                $data['wap_url'] = $this->http.$web_info['realm_two_ip'].'/wap/mall/index';
            }
        }else{
            $data['wap_status'] = $web_info['wap_status'];
        }
        return $data;
    }
    public function getCoinConfig(){
        $config = new AddonsConfig();
        $blockchain = $config->getAddonsConfig("blockchain",$this->website_id);
        $blockchain_info = json_decode($blockchain['value'],true);
        $blockchain_info['is_use'] = $blockchain['is_use'];
        $data['eth_status'] =0;
        $data['eos_status'] = 0;
        $wallet_type = explode(',',$blockchain_info['wallet_type']);
        if( $blockchain_info['is_use']==1 && in_array(1,$wallet_type)){
             $data['eth_status']=1;
        }
        if($blockchain_info['is_use']==1 && in_array(2,$wallet_type)){
            $data['eos_status']=1;
        }
        return $data;
    }

    public function setBindPhone()
    {
        $is_bind_phone = request()->post('is_bind_phone', 0);
        $website = new WebSiteModel();
        $bool = $website->where(['website_id' => $this->website_id])->update(['is_bind_phone' => $is_bind_phone]);
        return ajaxReturn($bool);
    }
    /**
     * 选择会员
     */
    public function updateReferee2()
    {
        $uid = $_GET['uid'];
        $referee_name = $_GET['name'];
        $this->assign('uid',$uid);
        $this->assign('name',$referee_name);
        $this->GetTeamMember($uid);
        if($this->members){
            $ids = implode(',',$this->members);
            $this->assign('lower_id',$ids);
        }
        return view($this->style . "System/updateReferee2");
    }
    /**
     * 计划任务
     */
    public function planTask()
    {
        if (request()->isAjax()) {
            $page_index = request()->post('pageIndex', 1);
            $page_size = request()->post('page_size', PAGESIZE);
            $return_data = [];
            $condition = [
                'website_id' => $this->website_id,
                'shop_id' => $this->instance_id
            ];
            //获取商品助手数据
            if (getAddons('goodhelper', $this->website_id)) {
                $goodsHelper = new GoodHelperServer();
                $goodsHelpList = $goodsHelper->getGoodsHelperList($page_index, $page_size, $condition, 'create_time desc');
                if($goodsHelpList['data']){
                    foreach($goodsHelpList['data'] as $key => $val){
                        switch ($val['add_type']){
                            case 0:
                                $task_type = '商品数据包导入（商城）';
                                break;
                            case 1:
                                $task_type = '商品数据包导入（淘宝）';
                                break;
                            default :
                                $task_type = '商品数据包导入（商城）';
                                break;
}
                        $return_data[] = [
                            'type' => 1,
                            'help_id' => $val['help_id'],
                            'task_type' => $task_type,
                            'status' => $val['status'],
                            'log' => $val['error_info'] ?: '',
                            'excel_name' => $val['old_excel_name'] ?: '',
                            'zip_name' => $val['old_zip_name'] ?: '',
                            'create_time' => $val['create_time'],
                        ];
                    }
                }

            }
            //获取发票助手数据
            if (getAddons('invoice', $this->website_id)) {
                $invoice = new InvoiceServer();
                $invoice_files = $invoice->getInvoiceFileList($page_index, $page_size, $condition, 'create_time desc');
                if ($invoice_files['data']) {
                    foreach($invoice_files['data'] as $key => $val){
                        $create_time = $val['create_time'];
                        $return_data[] = [
                            'type' => 2,
                            'id' => $val['id'],
                            'task_type' => '发票批量导入',
                            'status' => $val['status'],
                            'log' => $val['error_info'] ?: '',
                            'excel_name' => $val['old_excel_name'] ?: '',
                            'zip_name' => $val['old_zip_name'] ?: '',
                            'create_time' => $create_time,
                        ];
                    }
                }
            }
            //整合处理商品助手，发票助手按时间降序返回数据
            array_multisort(array_column($return_data,'create_time'),SORT_DESC, $return_data);
            $data = [
                'data' => $return_data,
                'total_count' => ($goodsHelpList['total_count'] ?: 0) + ($invoice_files['total_count'] ?: 0),
                'page_count' => ($goodsHelpList['page_count'] ?: 0) + ($invoice_files['page_count'] ?:0)
            ];
            return $data;
        }
        return view($this->style . 'System/plantask');
    }
    /**
     * 删除任务计划
     */
    public function delTask()
    {
        if (request()->isAjax()) {
            $type = request()->post('type');
            $id = request()->post('id');
            try {
                Db::startTrans();
                if ($type == 1 && getAddons('goodhelper', $this->website_id)) {//商品助手
                    $goodsHelp = new VslGoodsHelpModel();
                    $state = $goodsHelp::get($id)->status;
                    if ($state != 3) {
                        return ['code' => -1, 'message' => '状态已改变'];
                    }
                    $res = $goodsHelp->where(['help_Id' => $id])->delete();
                }else if ($type == 2&& getAddons('invoice', $this->website_id)) {//发票助手
                    $invoiceFile = new VslInvoiceFileModel();
                    $state = $invoiceFile::get($id)->status;
                    if ($state != 3) {
                        return ['code' => -1, 'message' => '状态已改变'];
                    }
                    $res = $invoiceFile->where(['id' => $id])->delete();
                }
                Db::commit();
                $type = $type == 1 ? '商品导入' : '发票导入';
                $target = '删除'.$type.'id:'.$id;
                $operation = '删除计划任务';
                $this->addUserOperationLog($operation, $target);
                return ['code' => 1, 'message' => '删除成功!'];
            } catch (Exception $e) {
                Db::rollback();
                return ['code' => -1, 'message' => $e->getMessage()];
            }
        }
    }
    /**
     * 下载失败内容
     * @return array
     */
    public function download()
    {
        $id = request()->post('id');
        $type = request()->post('type');//1： 商品助手任务  2：发票助手任务
        $condition['website_id'] = $this->website_id;
        $condition['shop_id'] = $this->instance_id;
        $condition['status'] = 5;//等待中
        $error_excel_path = '';
        if ($type == 1) {
            $condition[ 'help_id'] = $id;
            //下载商品助手
            $goodsHelper = new VslGoodsHelpModel();
            $error_excel_path = $goodsHelper->getInfo($condition, 'error_excel_path')['error_excel_path'];
        }
        if ($type == 2) {
            //下载发票助手
            $condition[ 'id'] = $id;
            $invoiceFile = new VslInvoiceFileModel();
            $error_excel_path = $invoiceFile->getInfo($condition, 'error_excel_path')['error_excel_path'];
        }
        if ($error_excel_path) {
            return ['code' => 1, 'data' => $error_excel_path];
        }
        return ['code' => -1, 'message' => '获取失败！'];
    }
    /**
     * 添加用户操作记录
     * @param $operation string [操作类型]
     * @param $target string [操作日志]
     */
    public function addUserOperationLog($operation, $target)
    {
        $user_name = $this->user->getUserInfo()['user_name'];
        $operation = $user_name . $operation;
        $this->user->addUserLog($this->uid, 1, $this->controller, $this->action, \think\Request::instance()->ip(), $target, $operation);
    }
    /**
     * 设为招商员
     */
    public function becomeMerchants()
    {
        $this->assign('getProvinceUrl', __URL(addons_url_platform('merchants://Merchants/getProvince')));
        $this->assign('getCityUrl', __URL(addons_url_platform('merchants://Merchants/getCity')));
        $this->assign('getDistrictUrl', __URL(addons_url_platform('merchants://Merchants/getDistrict')));
        return view($this->style . "System/becomeMerchants");
    }
}   