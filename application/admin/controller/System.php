<?php

namespace app\admin\controller;

use addons\goodhelper\model\VslGoodsHelpModel;
use addons\goodhelper\server\GoodHelper as GoodHelperServer;
use addons\invoice\model\VslInvoiceFileModel;
use addons\invoice\server\Invoice as InvoiceServer;
use data\service\Album as Album;
use data\service\User;
use data\model\AdminUserModel as AdminUserModel;
use data\model\AuthGroupModel as AuthGroupModel;
use think\Db;
use think\Exception;
use \think\Session as Session;
use data\service\WebSite;
/**
 * 系统模块控制器
 *
 * @author  www.vslai.com
 *        
 */
class System extends BaseController {

    public function __construct() {
        parent::__construct();
    }

    /**
     * 更新缓存
     */
    public function deleteCache() {
        $model = \think\Request::instance()->module();
        $admin = new AdminUserModel();
        $admin_info = $admin->getInfo('uid=' . Session::get($model.'uid'), 'is_admin,group_id_array');
        $auth_group = new AuthGroupModel();
        $auth = $auth_group->get($admin_info['group_id_array']);
        $user = new User();
        $no_control = $user->getNoControlAuth();
        Session::set($model.'module_id_array', $no_control.$auth['module_id_array']);
        Session::set('module_list', []);
        Session::set($model.'module_list', []);
        $retval = VslDelDir('./runtime/cache');
        $website = new WebSite();
        $dateArr = $website->getWebCreateTime($this->website_id);
        $path = '/public/addons_status/' . $dateArr['year'].'/'.$dateArr['month'].'/'.$dateArr['day'].'/'. $this->website_id;
        @unlink('.' . $path . '/addons_' . $this->instance_id);
        return $retval;
    }

    /**
     * 图片选择
     */
    public function dialogAlbumList() {
        $number = request()->get('number', 1);
        $spec_id = request()->get('spec_id', 0);
        $spec_value_id = request()->get('spec_value_id', 0);
        $upload_type = request()->get('upload_type', 1);
        $this->assign("number", $number);
        $this->assign("spec_id", $spec_id);
        $this->assign("spec_value_id", $spec_value_id);
        $this->assign("upload_type", $upload_type);
        $album = new Album();
        $default_album_detail = $album->getDefaultAlbumDetail();
        $this->assign('default_album_id', $default_album_detail['album_id']);
        return view($this->style . "System/dialogAlbumList");
    }
    /**
     * 图片空间
     */
    public function pic_space() {
        return view($this->style . "System/uploadAlbumImgDialog");
    }
    /**
     * 视频空间
     */
    public function video_space() {
        return view($this->style . "System/uploadAlbumVideoDialog");
    }
    //图片视频空间列表
    public function picvideo_space()
    {
        return view($this->style . 'System/pictureVideoDialog');
    }
    /**
     * 获取图片相册
     */
    public function albumList() {
        if (request()->isAjax()) {
            $page_index = request()->post("page_index", 1);
            $page_size = request()->post('page_size', PAGESIZE);
            $search_text = request()->post('search_text', '');
            $album = new Album();
            $condition = array(
                'shop_id' => $this->instance_id,
                'website_id' => $this->website_id,
                'album_name' => array(
                    'like',
                    '%' . $search_text . '%'
                )
            );
            $order = " create_time asc";
            $retval = $album->getAlbumClassList($page_index, $page_size, $condition, $order);
            return $retval;
        } else {
            $album = new Album();
            $default_album_detail = $album->getDefaultAlbumDetail();
            $this->assign('default_album_id', $default_album_detail['album_id']);
            return view($this->style . "System/albumList");
        }
    }

    /**
     * 图片空间弹窗 相册图片获取
     */
    public function getAlbunPic() {
        $album = new Album();
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post("page_size", PAGESIZE);
        $album_id = intval(request()->post('album_id', 0));
        $file_type = intval(request()->post('file_type', 0));
        if (!$album_id) {
            $album_id = $album->getDefaultAlbumDetail()['album_id'];
        }
        $sort_name = intval(request()->post('sort_name', 0));
        $condition['album_id'] = $album_id;
        $condition['is_wide'] = $file_type;

        $order = 'upload_time desc';
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
        $list = $album->getPictureList($page_index, $page_size, $condition, $order);
        return $list;
    }

    /**
     * 创建相册
     */
    public function addAlbumClass() {
        $album_name = request()->post('album_name', '');
        $sort = request()->post('sort', 0);
        $album = new Album();
        $retval = $album->addAlbumClass($album_name, $sort, 0, '', 0, $this->instance_id);
        if ($retval) {
            $this->addUserLog('创建相册', $album_name);
        }
        return AjaxReturn($retval);
    }

    /**
     * 删除相册
     */
    public function deleteAlbumClass() {
        $aclass_id_array = request()->post('aclass_id_array', '');
        $album = new Album();
        $retval = $album->deleteAlbumClass($aclass_id_array);
        if ($retval) {
            $this->addUserLog('删除相册', $aclass_id_array);
        }
        return AjaxReturn($retval);
    }

    /**
     * 相册图片列表
     */
    public function albumPictureList() {
        if (request()->isAjax()) {
            $page_index = request()->post("page_index", 1);
            $page_size = request()->post("page_size", PAGESIZE);
            $album_id = request()->post("album_id", 0);
            $is_use = request()->post("is_use", 0);
            $pic_name = request()->post("pic_name", '');
            $condition = array();
            $condition["album_id"] = $album_id;
            if ($pic_name) {
                $condition['pic_name'] = array(
                    "like",
                    "%" . $pic_name . "%"
                );
            }
            $album = new Album();
            if ($is_use > 0) {
                $img_array = $album->getGoodsAlbumUsePictureQuery([
                    "shop_id" => $this->instance_id
                ]);
                if (!empty($img_array)) {
                    $img_string = implode(",", $img_array);
                    $condition["pic_id"] = [
                        "not in",
                        $img_string
                    ];
                }
            }
            $list = $album->getPictureList($page_index, $page_size, $condition);
            foreach ($list["data"] as $k => $v) {
                $list["data"][$k]["upload_time"] = date("Y-m-d", $v["upload_time"]);
            }
            return $list;
        } else {
            $album_list = $this->getAlbumClassALL();
            $this->assign('album_list', $album_list);
            $album_id = request()->get('album_id', 0);
            $url = "System/albumPictureList";
            if ($album_id > 0) {
                $url .= "?album_id=" . $album_id;
            }
            $child_menu_list = array(
                array(
                    'url' => "System/albumList",
                    'menu_name' => "相册",
                    "active" => 0
                ),
                array(
                    'url' => $url,
                    'menu_name' => "图片",
                    "active" => 1
                )
            );
            $album = new Album();
            $album_detial = $album->getAlbumClassDetail($album_id);
            $this->assign('child_menu_list', $child_menu_list);
            $this->assign("album_name", $album_detial['album_name']);
            $this->assign("album_id", $album_id);
            $this->assign("album_cover", $album_detial['album_cover']);
            return view($this->style . "System/albumPictureList");
        }
    }

    /**
     * 弹窗相册图片列表
     */
    public function dialogAlbumPictureList() {
        if (request()->isAjax()) {
            $page_index = request()->post('pageIndex', '');
            $album_id = request()->post('album_id', '');
            $condition = array(
                'album_id' => $album_id
            );
            $album = new Album();
            $list = $album->getPictureList($page_index, 10, $condition);
            foreach ($list["data"] as $k => $v) {
                $list["data"][$k]["upload_time"] = date("Y-m-d", $v["upload_time"]);
            }
            return $list;
        } else {
            return view($this->style . "System/dialogAlbumPictureList");
        }
    }

    /**
     * 删除图片
     *
     * @param unknown $pic_id_array            
     * @return unknown
     */
    public function deletePicture() {
        $pic_id_array = request()->post('pic_id_array', '');
        $album = new Album();
        $retval = $album->deletePicture($pic_id_array);
        if ($retval) {
            $this->addUserLog('删除图片', $pic_id_array);
        }
        return AjaxReturn($retval);
    }

    /**
     * 获取相册详情
     *
     * @return Ambigous <\think\static, multitype:, \think\db\false, PDOStatement, string, \think\Model, \PDOStatement, \think\db\mixed, multitype:a r y s t i n g Q u e \ C l o , \think\db\Query, NULL>
     */
    public function getAlbumClassDetail() {
        $album_id = request()->post('album_id', '');
        $album = new Album();
        $retval = $album->getAlbumClassDetail($album_id);
        return $retval;
    }

    /**
     * 修改相册
     */
    public function updateAlbumClass() {
        $album_id = request()->post('album_id', '');
        $aclass_name = request()->post('album_name', '');
        $aclass_sort = request()->post('sort', '');
        $album_cover = request()->post('album_cover', '');
        $album = new Album();
        $retval = $album->updateAlbumClass($album_id, $aclass_name, $aclass_sort, 0, $album_cover);
        if ($retval) {
            $this->addUserLog('修改相册', $album_id . '-' . $aclass_name);
        }
        return AjaxReturn($retval);
    }

    /**
     * 获取所有相册
     */
    public function getAlbumClassALL() {
        $album = new Album();
        $retval = $album->getAlbumClassAll([
            'shop_id' => $this->instance_id
        ]);
        return $retval;
    }

    /**
     * 图片名称修改
     */
    public function modifyAlbumPictureName() {
        $pic_id = request()->post('pic_id', '');
        $pic_name = request()->post('pic_name', '');
        $album = new Album();
        $retval = $album->ModifyAlbumPictureName($pic_id, $pic_name);
        if ($retval) {
            $this->addUserLog('图片名称修改', $pic_id . '-' . $pic_name);
        }
        return AjaxReturn($retval);
    }

    /**
     * 转移图片所在相册
     */
    public function modifyAlbumPictureClass() {
        $pic_id = request()->post('pic_id', '');
        $album_id = request()->post('album_id', '');
        $album = new Album();
        $retval = $album->ModifyAlbumPictureClass($pic_id, $album_id);
        if ($retval) {
            $this->addUserLog('转移图片所在相册', '图片id：' . $pic_id . '->相册id:' . $album_id);
        }
        return AjaxReturn($retval);
    }

    /**
     * 设此图片为本相册的封面
     */
    function modifyAlbumClassCover() {
        $pic_id = request()->post('pic_id', '');
        $album_id = request()->post('album_id', '');
        $album = new Album();
        $retval = $album->ModifyAlbumClassCover($pic_id, $album_id);
        if ($retval) {
            $this->addUserLog('设此图片为本相册的封面', '图片id：' . $pic_id . '-相册id:' . $album_id);
        }
        return AjaxReturn($retval);
    }
    
    /**
     * 操作日志
     */
    public function operationLog() {
        if (request()->isAjax()) {
            $page_index = request()->post('pageIndex', 1);
            $uid = request()->post('uid', 0);
            $search = request()->post('search_text', '');
            $condition = ['website_id' => $this->website_id, 'instance_id' => $this->instance_id];

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
            $userList = $this->user->adminUserList(1, 0, ['sua.website_id' => $this->website_id,'sur.instance_id' => $this->instance_id])['data'];
            $this->assign('userlist', $userList);
            return view($this->style . 'System/operationLog');
        }
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
            if (!getAddons('invoice', $this->website_id)) {
                return ['code' => -1, 'message' => '应用不存在！'];
            }
            try {
                Db::startTrans();
                if ($type == 1) {//商品助手
                    $goodsHelp = new VslGoodsHelpModel();
                    $state = $goodsHelp::get($id)->status;
                    if ($state != 3) {
                        return ['code' => -1, 'message' => '状态已改变'];
                    }
                    $res = $goodsHelp->where(['help_Id' => $id])->delete();
                }else if ($type == 2) {//发票助手
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
}
