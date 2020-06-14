<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/18 0018
 * Time: 17:37
 */

namespace addons\miniprogram\controller;

use addons\cpsunion\server\Cpsunion;
use addons\miniprogram\Miniprogram as baseMiniProgram;
use addons\miniprogram\model\MpCustomTemplateModel;
use addons\miniprogram\model\MpSubmitModel;
use addons\miniprogram\model\WeixinAuthModel;
use data\extend\WchatOpen;
use data\model\AddonsConfigModel;
use addons\miniprogram\service\MiniProgram as miniProgramService;
use data\model\ConfigModel;
use data\model\UserModel;
use data\model\WebSiteModel;
use data\service\AddonsConfig;
use data\service\Config as configServer;
use think\Db;
use think\Config;
use think\Exception;
use think\Request;
use addons\qlkefu\server\Qlkefu as QlkefuService;

class Miniprogram extends baseMiniProgram
{
    protected $authorizer_access_token;
    protected $app_id;
    protected $auth_id;
    protected $template_id;
    protected $access_token;
    protected $nick_name;
    public $wchat_open;
    public $mini_program_service;
    public $weixin_auth_model;

    public function __construct()
    {
        parent::__construct();
        $this->wchat_open = new WchatOpen($this->website_id);
        $this->weixin_auth_model = new WeixinAuthModel();
        $this->mini_program_service = new miniProgramService();
        $mp_info = $this->mini_program_service->miniProgramInfo(['website_id' => $this->website_id]);
        $this->authorizer_access_token = $mp_info['authorizer_access_token'];
        $this->app_id = $mp_info['authorizer_appid'];
        $this->template_id = $mp_info['template_id'];
        $this->auth_id = $mp_info['auth_id'];
        $this->nick_name = $mp_info['nick_name'];
    }

    /**
     * 小程序设置
     */
    public function miniProgramSetting()
    {
        try {
            $post_data = request()->post();
            $is_mini_program = $post_data['is_mini_program'];
            // 发布中不能关闭
            $condition = [
                'website_id' => $this->website_id,
                'shop_id' => $this->instance_id,
                'submit_uid' => $this->uid
            ];
            $mini_status = $this->mini_program_service->getLastSumitStatus($condition);
            if ($mini_status == 2 && $is_mini_program ==0) {
                return ['code' => -1, 'message' => '小程序发布中，不能关闭！'];
            }
            unset($post_data['is_mini_program']);

            // 保存用户提交的类目
            $commit_category = '';
            if ($post_data['second_id']) {
                $commit_category = $this->mini_program_service->getMpCategoryForCommit($this->authorizer_access_token, $post_data['second_id']);
                unset($post_data['second_id']);
            }
            if ($post_data['authorizer_secret']) {
                // 写入小程序授权表
                $this->mini_program_service->saveWeixinAuth([
                    'authorizer_secret' => $post_data['authorizer_secret'],
                    'category' => $commit_category
                ],
                [
                    'website_id' => $this->website_id,
                    'shop_id' => $this->instance_id
                ]);
                unset($post_data['authorizer_secret']);
            }
            // 关闭原因，写入addons
            $addons_config_model = new AddonsConfigModel();
            $mini_program_info = $addons_config_model::get(['website_id' => $this->website_id, 'addons' => parent::$addons_name]);
            // 商城关闭，修改关闭原因
            if (!empty($mini_program_info)) {
                $res = $addons_config_model->save(
                    [
                        'is_use' => $is_mini_program,
                        'modify_time' => time(),
                        'value' => json_encode($post_data, JSON_UNESCAPED_UNICODE)
                    ],
                    [
                        'website_id' => $this->website_id,
                        'addons' => parent::$addons_name
                    ]
                );
            } else {
                $data['is_use'] = $is_mini_program;
                $data['value'] = json_encode($post_data, JSON_UNESCAPED_UNICODE);
                $data['desc'] = '小程序设置';
                $data['create_time'] = time();
                $data['addons'] = parent::$addons_name;
                $data['website_id'] = $this->website_id;
                $res = $addons_config_model->save($data);
            }
            setAddons('miniprogram', $this->website_id, $this->instance_id);
            return ['code' => $res, 'message' => '修改成功'];
        } catch (\Exception $e) {
            return ['code' => -1, 'message' => $e->getMessage()];
        }
    }

    public function miniProgramCustomList()
    {
        $page_index = request()->post('page_index', 1);
        $page_size = request()->post('page_size', 0);
        $template_name = request()->post('template_name', '');
        $template_type = request()->post('template_type');
        $condition = [
            'shop_id' => $this->instance_id,
            'website_id' => $this->website_id
        ];
        if ($template_type == 'diy') {
            $condition['type'] = 6;
        } else {
            $condition['type'] = ['NOT IN', [6, 7, 8]];
        }
        if ($template_name) {
            $condition['template_name'] = ['like', "%" . $template_name . "%"];
        }

            $list = $this->mini_program_service->customTemplateList($page_index, $page_size, $condition, 'modify_time DESC');

        return $list;
    }

    /**
     * 设置使用模板
     */
    public function useMpCustomTemplate()
    {
        $id = request()->post('id');
        $type = request()->post('type');
        if (empty($id) || empty($type)) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $data = $this->mini_program_service->customTemplateInfo(['id' => $id]);
        if(!$data){
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $templateData = json_decode($data['template_data'],true);
        if(!$templateData || !isset($templateData['items']) || !$templateData['items']){
            return ['code' => -1, 'message' => '空白模板无法使用'];
        }
        $result = $this->mini_program_service->useCustomTemplate($id, $type, $this->instance_id, $this->website_id);
        return AjaxReturn($result);
    }
    /**
     * 设置使用模板
     */
    public function editMpTemplateName()
    {
        $id = request()->post('id');
        $name = request()->post('name');
        $Custom = new MpCustomTemplateModel();
        $info = $Custom->save(['template_name'=>$name],['id' => $id]);
        return AjaxReturn($info);
    }
    /**
     * 删除装修模板
     */
    public function deleteMpCustomTemplate()
    {
        $id = request()->post('id/a', 0);
        if (!$id) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $condition = [
            'shop_id' => $this->instance_id,
            'website_id' => $this->website_id,
            'id' => ['in', $id]
        ];
        $res = $this->mini_program_service->deleteCustomTemplate($condition);
        return AjaxReturn($res);
    }

    /**
     * 模板选择
     */
    public function mpTemplateDialog()
    {
        $this->assign('mpSystemDefaultTemplateUrl', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://miniprogram/mpSystemDefaultTemplate')));
        $this->assign('createCustomTemplateUrl', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://miniprogram/createCustomTemplate')));
        $integralStatus = getAddons('integral', $this->website_id);
        $this->assign('integralStatus', $integralStatus);
        return $this->fetch('/template/' . $this->module . '/' . 'mpTemplateDialog');
    }

    /**
     * 系统默认模板列表
     * @return array
     * @throws \think\Exception\DbException
     */
    public function mpSystemDefaultTemplate()
    {
        $condition['is_system_default'] = 1;
        if ($this->instance_id > 0) {
            $condition['type'] = ['IN', [2, 3, 6, 9]];
        } else {
            $condition['type'] = ['IN', [1, 2, 3, 4, 5, 6, 9]];
        }
        $condition['shop_id'] = 0;
        $condition['website_id'] = 0;
        return $this->mini_program_service->systemDefaultTemplate($condition);
    }

    /**
     * 新增装修页面
     */
    public function createCustomTemplate()
    {
        $id = request()->post('id');
        $type = request()->post('type');
        $template_name = request()->post('template_name');
        if (empty($type)) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        if ($id) {
            $condition['id'] = $id;
            $system_default_template_data = $this->mini_program_service->customTemplateInfo($condition);
            $template_data = json_decode($system_default_template_data['template_data'], true);
        } else {
            $template_data = '';
        }
        $data['template_name'] = !empty($template_name) ? $template_name : ( isset($template_data['page']['name']) ? $template_data['page']['name'] : '新建模板');
        $data['type'] = $type;
        $data['shop_id'] = $this->instance_id;
        $data['website_id'] = $this->website_id;
        $data['create_time'] = time();
        $data['modify_time'] = time();
        $data['template_data'] = json_encode($template_data, JSON_UNESCAPED_UNICODE);
        $id = $this->mini_program_service->saveCustomTemplate($data);
        return AjaxReturn(1, ['id' => $id]);
    }

    /**
     * 体验者二维码
     */
    public function testerQrCode()
    {
        $weixin_auth = $this->weixin_auth_model->getInfo(['auth_id' => $this->auth_id], 'qr_code_url');
        if ($weixin_auth && $weixin_auth['qr_code_url']) {
            return $weixin_auth['qr_code_url'];
        }
        $result = $this->wchat_open->getQrCode($this->authorizer_access_token);
        if (stripos($result,'errcode')) {
            //使用默认图片
            return request()->domain().'/public/platform/images/mappqr.jpg';
        } else {
           //压缩图片
            // 上传云端
            $path = 'upload/qrCode/'. $this->website_id;
            $qrCodeUrlFromYun = getImageFromYun($result, $path, 'qrCode');
            if ($qrCodeUrlFromYun) {
                // 图片链接写入数据库
                $this->weixin_auth_model->update([
                    'qr_code_url' => $qrCodeUrlFromYun
                ],
                [
                    'auth_id' => $this->auth_id
                ]);
            }
            return $qrCodeUrlFromYun;
        }
    }

    /**
     * 提交小程序代码
     */
    public function commitMp()
    {
        $type = request()->post('type', 2);//1发布直播 2不发布直播
        // 查询template_id
        $template_id = $this->newTemplate();
        $ext_data['extEnable'] = true;
        $ext_data['extAppid'] = $this->app_id;
//        $ext_data['pages'] = Config::get('mp_route');
        $ext_data['networkTimeout'] = ['request' => 10000, 'downloadfile' => 10000];
        # 获取window相关参数
        $winCondition = [
            'shop_id' => $this->instance_id,
            'website_id' => $this->website_id,
            'in_use' => 1,
            'type' => 1
        ];

        $result = $this->mini_program_service->customTemplateInfo($winCondition);
        $templateData = json_decode($result->template_data, true);
        // 这里让所有小程序页面的window都一样，现在改为小程序调用接口获取
        $ext_data['window']['navigationBarBackgroundColor'] = $templateData['page']['navbarbackground'];//导航栏背景色
        $ext_data['window']['navigationBarTextStyle'] = $templateData['page']['navbarcolor'];//导航栏标题颜色
        $ext_data['window']['navigationBarTitleText'] = $templateData['page']['title'];//导航栏标题文字内容
        $ext_data['window']['backgroundColor'] = $templateData['page']['background'];//背景色
        $ext_data['window']['backgroundTextStyle'] = 'light';

        # 获取tabBar相关参数
        $tabCondition = [
            'shop_id' => $this->instance_id,
            'website_id' => $this->website_id,
            'in_use' => 1,
            'type' => 7
        ];
        $result = $this->mini_program_service->customTemplateInfo($tabCondition);
        $templateData = json_decode($result->template_data, true);
        $list = [];
        $pathList = [];
        $footIcon = Config::get('mp_foot.icon');
        foreach ($templateData['data'] as $key => $v) {
            $list[] = [
                'pagePath' => $v['path'],
                'text' => $v['text'],
                'iconPath' => $footIcon .basename($v['normal']),
                'selectedIconPath' => $footIcon .basename($v['active'])
            ];
            $pathList[] = $v['path'];
        }

        $ext_data['tabBar']['list'] = $list;
        # 传入ext文件
        $ext_data['ext']['domain'] = getIndependentDomain($this->website_id);//主域名
        $ext_data['ext']['domain_wap'] = getIndependentDomain($this->website_id, true);//独立域名
        $ext_data['ext']['pathList'] = $pathList;
        $ext_data['ext']['website_id'] = $this->website_id;
        $ext_data['ext']['auth_id'] = $this->auth_id;
        // 直播插件
        if ($type == 1){
            $ext_data['plugins'] = [
                'live-player-plugin' =>
                    [
                        'version' => MPLIVE_VERSION,
                        'provider' => 'wx2b03c6e691cd7370'
                    ]
            ];
        }
        # 获取最新template_id
        $this->weixin_auth_model->where(['auth_id' => $this->auth_id])->update(['template_id' => $template_id]);
        # 发布提交数据
        $data['ext_json'] = json_encode($ext_data, JSON_UNESCAPED_SLASHES);
        $data['template_id'] = $template_id;
        // 提交版本信息
        $condition = [
            'website_id' => $this->website_id,
            'shop_id' => $this->instance_id
        ];
        $new_submit_record = $this->mini_program_service->getNewMpSubmitRecord($condition);
        if ($new_submit_record) {
            $versionRes = $this->autoAddVersionForMiniprogram($new_submit_record['version']);
            if ($versionRes['code'] > 0){
                $data['user_version'] = $versionRes['message'];
            $data['user_desc'] = '版本v'.$data['user_version'];
            } else {
                return $versionRes;
            }
        } else {
            $data['user_version'] = '1.0.0';
            $data['user_desc'] = '版本v'.$data['user_version'];
        }

        // 设置第三方域名
        $result = $this->postDomainToMp();
        $result = is_object($result) ? objToArr($result) : $result;
        if ($result['errcode']) {
            return AjaxWXReturn($result['errcode'], [], $result->message);
        }
        // 域名设置好之后提交代码
        $result = $this->wchat_open->commitMpCode($this->authorizer_access_token, $data);
        if ($result->errcode) {
            return AjaxWXReturn($result->errcode, [], $result->errmsg);
        }

        // 类目获取
//        $category_result = $this->wchat_open->getMpCategory($this->authorizer_access_token);
        $category_result = $this->weixin_auth_model->getInfo(['website_id' => $this->website_id, 'shop_id' => $this->instance_id], 'category');
        $categoryList = json_decode($category_result['category'], true);
        if (empty($categoryList)) {
            $category_list = $this->mini_program_service->getMpCategoryForCommit($this->authorizer_access_token);
            $this->weixin_auth_model->save(['category' => $category_list], ['auth_id' => $this->auth_id]);
            $categoryList = json_decode($category_list, true);
        }
        $category = $categoryList[0];
        $submit_data['item_list'][0]['address'] = Config::get('mp_route')[0];
        $submit_data['item_list'][0]['tag'] = '首页';
        $submit_data['item_list'][0]['title'] = '首页';
        $submit_data['item_list'][0]['first_id'] = $category['first_id'];
        $submit_data['item_list'][0]['first_class'] = $category['first_class'];
        $submit_data['item_list'][0]['second_id'] = $category['second_id'];
        $submit_data['item_list'][0]['second_class'] = $category['second_class'];
        $audit_result = $this->wchat_open->submitAudit($this->authorizer_access_token, $submit_data);

        // 记录提交审核和失败记录
        $submit_history['auth_id'] = $this->auth_id;
        $submit_history['submit_time'] = time();
        $submit_history['submit_uid'] = $this->uid;
        $submit_history['shop_id'] = $this->instance_id;
        $submit_history['website_id'] = $this->website_id;
        $submit_history['audit_id'] = $audit_result->auditid;
        $submit_history['version'] = $data['user_version'];
        // 失败
        if ($audit_result->errcode) {
            return AjaxWXReturn($audit_result->errcode, [], $audit_result->errmsg);
        }
        // 成功
        $submit_history['status'] = 2;//审核中
        $this->mini_program_service->addSubmit($submit_history);

        return ['code' => 0, 'message' => '提交成功'];
    }

    /**
     * 获取草稿箱列表
     */
    public function draftList()
    {
        $retval = $this->wchat_open->draftList($this->authorizer_access_token);
        return $retval;
    }

    /**
     * 获取模板库列表
     */
    public function templateList()
    {
        $this->wchat_open->templateList();
    }

    /**
     * 获取最新模板
     */
    public function newTemplate()
    {
        $result = $this->wchat_open->templateList();
        if ($result->errcode){
            return AjaxWXReturn($result->errcode,[], $result->errmsg);
        }
        $resultArr = objToArr($result);
        $templateList = arrSortByValue($resultArr['template_list'], 'create_time');
        $new_template_id = $templateList[0]['template_id'];

        return $new_template_id;
    }

    /**
     * 删除模板
     */
    public function deleteTemplate()
    {
        $data['template_id'] = request()->post('template_id');
        $this->wchat_open->deleteTemplate($this->authorizer_access_token, $data);
    }

    /**
     * 添加草稿到模板库
     */
    public function addToTemplate()
    {
        $data['draft_id'] = request()->post('draft_id');
        $this->wchat_open->addToTemplate($this->authorizer_access_token, $data);
    }

    /**
     * 绑定小程序体验者
     */
    public function bindMpTester()
    {
        $wchat_id = request()->post('wchat_id');
        if (empty($wchat_id)) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $data['wechatid'] = $wchat_id;
        $result = $this->wchat_open->bindTester($this->authorizer_access_token, $data);
        if ($result->errcode) {
            return ['code' => -1, 'message' => $result->errmsg];
        } else {
            return ['code' => 1, 'message' => '绑定成功'];
        }
    }

    /**
     * 解绑小程序体验者
     */
    public function unBindTester()
    {
        $user_str = request()->post('user_str');
        if (empty($user_str)) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $data['userstr'] = $user_str;
        $result = $this->wchat_open->unBindTester($this->authorizer_access_token, $data);

        if ($result->errcode) {
            return ['code' => -1, 'message' => $result->errmsg];
        } else {
            return ['code' => 1, 'message' => '解绑成功'];
        }
    }

    /**
     * 获取小程序可选类目
     * @return array
     */
    public function getCategory()
    {
        $result = $this->wchat_open->getMpCategory($this->authorizer_access_token);
        if ($result->errcode) {
            return ['code' => -1, 'message' => $result->errmsg];
        } else {
            return ['code' => 1, 'message' => '获取成功', 'data' => $result->category_list];
        }
    }

    /**
     * 体验者列表
     */
    public function testerList()
    {
        $return = $this->wchat_open->testerList($this->authorizer_access_token);
        $list = [];
        $error_message = '';
        if (!$return->errcode) {
            if (is_array($return->members)) {
                foreach ($return->members as $v) {
                    $temp_tester = [];
                    $temp_tester['user_str'] = $v->userstr;

                    $list[] = $temp_tester;
                }
            }
        } else {
            $error_message = $return->errmsg;
        }
        $this->assign('error_message', $error_message);
        $this->assign('list', $list);
        $this->assign('unBindTesterUrl', __URL(call_user_func('addons_url_' . $this->module, 'miniprogram://Miniprogram/unbindtester')));
        return $this->fetch('/template/' . $this->module . '/' . 'testerList');
    }

    /**
     * 提交审核的modal框
     * @return mixed
     * @throws \Exception
     */
    public function submitModal()
    {
        $category_result = $this->wchat_open->getMpCategory($this->authorizer_access_token);
        $page_result = $this->wchat_open->getMpPage($this->authorizer_access_token);
        $category_list = $page_list = [];
        if ($category_result->errcode == 0) {
            $category_list = $category_result->category_list;
        }
        if ($page_result->errcode == 0) {
            $page_list = $page_result->page_list;
        }
        $this->assign('page_list', objToArr($page_list));
        $this->assign('category_list', objToArr($category_list));
        return $this->fetch('/template/' . $this->module . '/' . 'submitModal');
    }

    /**
     * 提交审核
     */
    public function submit()
    {
        $data['item_list'][0]['address'] = 'pages/index/index';
        $data['item_list'][0]['tag'] = '首页';
        $data['item_list'][0]['title'] = '首页';
        $data['item_list'][0]['first_id'] = 287;
        $data['item_list'][0]['first_class'] = '工具';
        $data['item_list'][0]['second_id'] = 620;
        $data['item_list'][0]['second_class'] = '企业管理';
        $result = $this->wchat_open->submitAudit($this->authorizer_access_token, $data);
        if ($result->errcode) {
            return ['code' => -1, 'message' => $result->errmsg];
        } else {
            $submit_history['auth_id'] = $this->auth_id;
            $submit_history['submit_time'] = time();
            $submit_history['submit_uid'] = $this->uid;
            $submit_history['status'] = 2;//审核中
            $submit_history['shop_id'] = $this->instance_id;
            $submit_history['website_id'] = $this->website_id;
            $submit_history['audit_id'] = $result->auditid;
            $this->mini_program_service->addSubmit($submit_history);
            return ['code' => 1, 'message' => '提交成功'];
        }
    }

    public function submitList()
    {
        $page_index = request()->post('page_index', 1);
        $page_size = request()->post('page_size', PAGESIZE);
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;

        $list = $this->mini_program_service->submitList($page_index, $page_size, $condition, 'submit_time DESC');
        return $list;
    }

    /**
     * 小程序支付信息
     */
    public function saveMpPay()
    {
        $config_service = new configServer();
        $post_data = request()->post();
        $data['value'] = json_encode($post_data, JSON_UNESCAPED_UNICODE);
        $data['desc'] = '小程序支付信息';
        $data['is_use'] = 1;
        $data['key'] = 'MPPAY';
        $data['website_id'] = $this->website_id;
        $data['instance_id'] = $this->instance_id;
        $condition = ['website_id' => $this->website_id, 'instance_id' => $this->instance_id, 'key' => 'MPPAY'];
        $result = $config_service->saveConfigNew($data, $condition);
        if ($result) {
            return ['code' => 1, 'message' => '保存成功'];
        } else {
            return ['code' => -1, 'message' => '保存失败'];
        }
    }

    /**
     * 启用消息模板
     */
    public function addMessageTemplateRelation()
    {
        $template_id = request()->post('template_id');
        // 如果存在就不去调用微信接口获取
        $wx_template_list = $this->getTemplateIdList($this->authorizer_access_token);
        $condition = [
            'website_id' => $this->website_id,
            'shop_id' => $this->instance_id,
            'template_id' => $template_id
        ];
        $result = $this->mini_program_service->relationTemplateId($condition);
        if ($result) {
            $mp_template_id = $result['mp_template_id'] ?: '';
            if ($wx_template_list && in_array($mp_template_id, $wx_template_list)) {
                $up_data = [
                    'status' => 1
                ];
                $this->mini_program_service->changeTemplateRelationState($up_data, $condition);
                return ['code' => 1, 'message' => '启用成功', 'data' => ['mp_template_id' => $mp_template_id]];
            }
        }

        $template_detail = $this->mini_program_service->mpTemplateDetail(['template_id' => $template_id]);

        if (empty($template_detail)) {
            return ['code' => -1, 'message' => '模板数据为空'];
        }
        $data['id'] = $template_detail['template_code'];
        $data['keyword_id_list'] = $template_detail['key_id'] ? explode(',', $template_detail['key_id']) : [];
        $result = $this->wchat_open->addMessageTemplate($this->authorizer_access_token, $data);

        if ($result->errcode) {
            return ['code' => -1, 'message' => '启用失败,' . $result->errmsg];
        } else {
            // 添加启用关系
            $relation_data['shop_id'] = $this->instance_id;
            $relation_data['website_id'] = $this->website_id;
            $relation_data['mp_template_id'] = $result->template_id;
            $relation_data['template_id'] = $template_id;
            $relation_data['status'] = 1;
            $this->mini_program_service->addTemplateRelation($relation_data);
            return ['code' => 1, 'message' => '启用成功', 'data' => ['mp_template_id' => $result->template_id]];
        }
    }

    /**
     * 取消模板消息
     * @return array
     */
    public function deleteMessageTemplateRelation()
    {
        $mp_template_id = request()->post('mp_template_id');
        $condition = [
            'website_id' => $this->website_id,
            'shop_id' => $this->instance_id,
            'mp_template_id' => $mp_template_id
        ];
        $result = $this->mini_program_service->relationTemplateId($condition);
        if ($result) {
            $up_data = [
                'status' => 0
            ];
            $this->mini_program_service->changeTemplateRelationState($up_data, $condition);
            return ['code' => 1, 'message' => '取消成功'];
        }

        $data['template_id'] = $mp_template_id;
        $result = $this->wchat_open->deleteMessageTemplate($this->authorizer_access_token, $data);
        if ($result->errcode) {
            return ['code' => -1, 'message' => '取消失败,' . $result->errmsg];
        } else {
            $condition['mp_template_id'] = $mp_template_id;
            $this->mini_program_service->deleteTemplateRelation($condition);
            return ['code' => 1, 'message' => '取消成功'];
        }
    }

    public function saveCustom()
    {
        //用户修改模板window,tabar与已发布不一样就提示
        // 默认头部修改，其他模板一起修改
        $template_data_temp = request()->post('template_data/a', '');
        $template_data = json_encode($template_data_temp, JSON_UNESCAPED_UNICODE); // 模板数据
        $tab_bar = json_encode(request()->post('tabbar/a', ''), JSON_UNESCAPED_UNICODE);
        $copyright = json_encode(request()->post('copyright/a', ''), JSON_UNESCAPED_UNICODE);
        $id = request()->post('id', ''); // 模板id
        if(!isset($template_data_temp['items']) || !$template_data_temp['items']){
            return ['code' => -1,'message' => '空白模板无法保存'];
        }
        $data['template_data'] = htmlspecialchars_decode($template_data);// 这里html 有'&'被转义了需要传值
        if ($id) {
            $data['modify_time'] = time();
        } else {
            $data['create_time'] = time();
        }
        $return = $this->mini_program_service->saveCustomTemplate($data, $id);

        if($return) {
            if(getAddons('cpsunion',$this->website_id)) {
            $cpsunion_server = new Cpsunion();
            $cpsunion_server->saveCpsGoods($template_data_temp);
            }
        }

        // 修改type = 1的默认模板，获取所有该小程序模板，都把头部修改(除了 [1,6, 7, 8 ])
        if ($template_data_temp['page']['type'] == 1) {
            $condition = [
                'shop_id' => $this->instance_id,
                'website_id' => $this->website_id,
                'type' => ['NOT IN', [1, 6, 7, 8]]
            ];
            $new_data = [
                'background' => $template_data_temp['page']['background'],
                'navbarcolor' => $template_data_temp['page']['navbarcolor'],
                'navbarbackground' => $template_data_temp['page']['navbarbackground'],
            ];
            $result = $this->mini_program_service->updateAllTemplateOfPage($condition, $new_data);
            if ($result) {
                return json(['code' => -1, 'message' => '保存失败' ]);
            }
        }

        //$custom_info = $web_config->getCustomTemplateInfo(['id' => $id]);
        if (isWIthTarBarAndCopyright($template_data_temp['page']['type'])) {
            //商城首页,会员中心才有底部信息
            $tab_bar_data['template_data'] = $tab_bar;
            $tab_bar_info = $this->mini_program_service->customTemplateInfo(['shop_id' => $this->instance_id, 'website_id' => $this->website_id, 'type' => 7]);
            if ($tab_bar_info) {
                $tab_bar_data['modify_time'] = time();
            } else {
                $tab_bar_data['shop_id'] = $this->instance_id;
                $tab_bar_data['website_id'] = $this->website_id;
                $tab_bar_data['type'] = 7; //底部
                $tab_bar_data['create_time'] = time();
            }
            $this->mini_program_service->saveCustomTemplate($tab_bar_data, $tab_bar_info['id'] ?: 0);

            $copyright_data['template_data'] = $copyright;
            $copyright_info = $this->mini_program_service->customTemplateInfo(['shop_id' => $this->instance_id, 'website_id' => $this->website_id, 'type' => 8]);
            if ($copyright_info) {
                $copyright_data['modify_time'] = time();
            } else {
                $copyright_data['shop_id'] = $this->instance_id;
                $copyright_data['website_id'] = $this->website_id;
                $copyright_data['type'] = 8; //版权
                $copyright_data['create_time'] = time();
            }
            $this->mini_program_service->saveCustomTemplate($copyright_data, $copyright_info['id'] ?: 0);
        }
        return AjaxReturn($return);
    }

    /**
     * 获取太阳码 （有限个）
     */
    public function getLimitMpCode()
    {
        $condition['website_id'] = request()->post('website_id') ?: $this->website_id;
        $condition['shop_id'] = request()->post('shop_id') ?: $this->instance_id;

        $mini_program_info = $this->mini_program_service->miniProgramInfo($condition);

        // 只要最新状态是1就去生成新太阳码
        if ($mini_program_info['new_auth_state'] == 0) {
            return json(['code' => 1, 'message' => '成功获取', 'data' => $mini_program_info['sun_code_url']]);
        }

        $mp_config = Config::get('mp_route');
        $params = [
            "path" => $mp_config[0],
            'width' => 280
        ];

        // 封装生成太阳码的方法，修改new_auth_state = 1
        $sun_code_url = $this->createSunCodeUrl($this->authorizer_access_token, $params, 1);
        if ($sun_code_url) {
            $this->mini_program_service->saveWeixinAuth(['new_auth_state' => 0], ['auth_id' => $this->auth_id]);//把授权改成 0，表示已经生成新太阳码
        }

        return json(['code' => 1, 'message' => '成功获取', 'data' => $sun_code_url]);
    }

    /**
     * 生成对应太阳码 (带场景值的暂时弃用)
     * @param $authorizer_access_token
     * @param array $params
     * @param int $type 1有限 2无限
     * @return bool|string|void
     */
    public function createSunCodeUrl($authorizer_access_token, $params = [], $type = 1)
    {
        # 带场景值的暂时弃用！因为用base64返回
        $imgRes = $this->wchat_open->getSunCodeApi($authorizer_access_token, $params, $type);
        if (empty($imgRes)) {
            return ;
        }
        // 图片处理
        try{
            // eg:  type = 1  'upload/sunCode/1/26    | type = 2  'upload/sunCode/2/0'
            $website_id = !empty($this->website_id) ? $this->website_id: 0;
            $path = 'upload/sunCode/' . $type .'/'. $website_id;
            $imgName = !empty($this->nick_name) ? md5($this->nick_name) : time();
            /**
             * 如果是场景值二维码（临时），为了防止云端存储多余临时图片，每次生成图片名都为1.jpeg,这样就覆盖上一次的图片
             * 保证每次请求都是新的场景值小程序码同时，云端都只有1.png的图片
             */
            if ($type == 2) {
                $imgName = $this->uid ? : 1;
                $scene_arr = explode('_', $params['scene']);
                if(strstr($params['scene'], '_poster')){
                    $arr_len = count($scene_arr);
                    if(is_array($scene_arr)){
                        $poster_id = $scene_arr[$arr_len - 2];
                    }
                    $imgName = $this->uid ? $this->uid.'_'.$poster_id : 1;
                }
                if(strstr($params['page'], 'goods') && strstr($params['scene'], '_poster')){
                    if(is_array($scene_arr)){
                        $goods_id = $scene_arr[1];
                    }
                    $imgName = $this->uid ? $this->uid.'_'.$goods_id.'_'.$poster_id : 1;
                }
            }

            // 上传云端
            $sunUrlFromYun = getImageFromYun($imgRes, $path, $imgName);
            if ($type == 1) {
                if ($sunUrlFromYun) {
                    // 图片链接写入数据库
                    $this->weixin_auth_model->update([
                        'sun_code_url' => $sunUrlFromYun
                    ],
                        [
                            'auth_id' => $this->auth_id
                        ]);
                }
            }

            return $sunUrlFromYun;
        } catch (\Exception $e) {
            $log = [
                'content' => $this->auth_id.'的太阳码存储错误:'.$e->getMessage(),
                'time' => date('Y-m-d H:i:s', time())
            ];
            Db::table('sys_log')->insert($log);
        }
    }

    public function downSunCode()
    {
        $auth_id = input('auth_id');
        $wxAuthRes = $this->weixin_auth_model->getInfo(['auth_id' => $auth_id]);
        $filename = $wxAuthRes['sun_code_url'];

        header("Content-Type: application/force-download");
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        $img = file_get_contents($filename);
        echo $img;
    }

    /**
     * 小程序调用返回商家名、logo
     * @return false|string
     */
    public function getMpBaseInfo()
    {
        $website_id = request()->post('website_id');

        # 获取名字
        $webSiteModel = new WebSiteModel();
        $mall_name = $webSiteModel->getInfo(['website_id' => $website_id], 'mall_name');
        # 获取logo
        $logo = $this->weixin_auth_model->getInfo(['website_id' => $website_id],  'head_img');

        $returnArr = [
            'name' => isset($mall_name['mall_name']) ? $mall_name['mall_name'] : '',
            'logo' => isset($logo['head_img']) ? $logo['head_img']: ''
        ];

        return json(['code' => 1, 'message' => '成功获取', 'data' => $returnArr]);
    }
    /**
     * 获取提交最新状态(ajax调用)
     * @return mixed
     */
    public function getPublicStatus()
    {

        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $condition['submit_uid'] = $this->uid;
        $status = $this->mini_program_service->getLastSumitStatus($condition);

        return $status;
    }

    /**
     * 获取太阳码（无限个，带场景值）
     * @param array $data 场景值参数
     * @return \think\response\Json
     */
    public function getUnLimitMpCode($data = [])
    {
        $request  = Request::instance()->post();
        $code     = $request['code'] ?: -1;
        $goods_id = $request['goodsId'] ? '_'.$request['goodsId'] : '';
        $id = $goods_id;
        $shopkeeper_id = $request['shopkeeperId'] ? '_' . $request['shopkeeperId'] : '';
        if($shopkeeper_id){
            $id = $shopkeeper_id;
        }
        $page     = $request['page'] ?: Config::get('mp_route')[0];
        $website_id = $request['website_id'] ?: $this->website_id;

        if (empty($website_id) && empty($data)) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $params = [
            'scene' => $code . $id,
            'page' => $page,
            'width' => 280
        ];
        if (!empty($data)) {
            $params = $data;
        }

        $mp_info = $this->weixin_auth_model->getInfo(['website_id' => $website_id],'authorizer_access_token');
        if (empty($mp_info)) {
            return json(['code' => -1, 'message' => '参数错误！']);
        }
        $sun_code_url = $this->createSunCodeUrl($mp_info['authorizer_access_token'], $params, 2);

        if ($sun_code_url) {
            return json(['code' => 1, 'message' => '获取成功', 'data' => $sun_code_url .'?'. time()]);
        } else {
            return json(['code' => -1, 'message' => '没有小程序二维码']);
        }
    }

    /**
     *  调试获取最新审核状态
     */
    public function lastStatus()
    {
        $res = $this->wchat_open->getLasAudistatus($this->authorizer_access_token);
        $res = is_object($res) ? objToArr($res) : $res;
        $status = $res['status'];
        if($res['errcode'] == 0){
            switch ($res['status']) {
                case 0:
                    $status = '审核成功';
                    break;
                case 1:
                    $status = '审核被拒绝';
                    break;
                case 2:
                    $status = '审核中';
                    break;
                case 3:
                    $status = '已撤回';
                    break;
            }
        }
        $res['status'] = $status;
        p($res);
    }

    /**
     * 调试获指定版本审核状态
     */
    public function lastStatusById()
    {
        $auditid = \request()->get('id');
        $data = [
            'auditid' => $auditid
        ];
        $res = $this->wchat_open->getAuditStatus($this->authorizer_access_token, $data);
        debugLog($res, '测试：获取'. $auditid.'最新审核状态=》');
    }

    /***
     * 获取addons数据
     */
    public function getMpSetting(){

        $addonsConfigSer = new AddonsConfig();
        $addons_info = $addonsConfigSer->getAddonsConfig(parent::$addons_name, $this->website_id);
        $addons_data = json_decode($addons_info['value'], true) ?: [];
        $addons_data['is_use'] = $addons_info['is_use'] ?: 0;
        return $addons_data;
    }

    /**
     * 获取小程序基本信息（名称、头像、认证、类目、小程序码）
     */
    public function getNewMpBaseInfo()
    {
        $auth_base_info = $this->wchat_open->get_authorizer_info($this->app_id);
        $authorizer_info = $auth_base_info->authorizer_info;
        // 获取类目
        // 获取类目（默认第一个）
        $category_list = $this->mini_program_service->getMpCategoryList($this->authorizer_access_token);
        $auth_data = [];
        if ($authorizer_info) {
            $auth_data['nick_name'] = $authorizer_info->nick_name;
            $auth_data['head_img'] = $authorizer_info->head_img;
            $auth_data['category'] = $category_list;
            $auth_data['real_name_status'] = $authorizer_info->verify_type_info->id;
            $auth_data['new_auth_state'] = 0;

            $result = $this->weixin_auth_model->save($auth_data, ['auth_id' => $this->auth_id]);
        }
        if ($result) {
            // 太阳码
            $mp_config = Config::get('mp_route');
            $params = [
                "path" => $mp_config[0]
            ];
            $sun_code_url = $this->createSunCodeUrl($this->authorizer_access_token, $params, 1);
            $auth_data['sun_code_url'] = $sun_code_url;
        }

        return json(['code' => 1, 'message' => '更新基本信息成功！', 'data' => $auth_data]);
    }

    /***
     * 是否开启小程序商城，类目是否添加
     * @return  code
     */
    public function isUseAndHasCategory()
    {
        $is_shop_open = $this->mini_program_service->getMiniProgramUseStatus();
        $is_has_category = $this->mini_program_service->isExistCategory();

        $data = [
            'is_shop_open' => $is_shop_open ?: 0,
            'is_has_category' => $is_has_category ?: 0
        ];
        return json(['code' => -1, 'data' => $data]);
    }

    /**
     * 获取帐号下已存在的消息模板ID列表
     * @param string $authorizer_access_token [小程序access_token]
     * @param int $offset [用于分页，表示从offset开始]
     * @param int $count [用于分页，表示拉取count条记录。最大为 20]
     * @return array
     */
    public function getTemplateIdList($authorizer_access_token)
    {
        $result = $this->wchat_open->getTemplateListOfSub($authorizer_access_token);
        if ($result->errcode != 0) {
            return AjaxWXReturn($result->errcode);
        }
            $template_id_list = [];
        foreach ($result->data as $key => $v) {
            $template_id_list[$key] = $v->priTmplId;
            }
            return $template_id_list;
    }

    /**
     * 小程序开发者配置
     */
    public function getMiniProgramAppId()
    {
        $condition['shop_id'] = $this->instance_id;
        $condition['website_id'] = $this->website_id;
        $mini_program_info = $this->weixin_auth_model->getInfo($condition, 'authorizer_appid');
        if ($mini_program_info['authorizer_appid']) {
            return ['code' =>1, 'message' =>  $mini_program_info['authorizer_appid']];
        }
        return ['code' => -1];
    }
    /**
     * 保存小程序appSecret
     */
    public function saveMpAppSecret()
    {
        $app_secret = request()->post('app_secret');
        if ($app_secret) {
            $condition['shop_id'] = $this->instance_id;
            $condition['website_id'] = $this->website_id;
            $res = $this->weixin_auth_model->save(['authorizer_secret' => $app_secret], $condition);
            if ($res) {
                return ['code' => 1, 'message' => '授权成功'];
            }
        }
        return ['code' => -1, 'message' => '授权成功'];
    }
    public function payConfigMir() {
        $config = new configServer();
        $list['pay_list'] = $config->getPayConfigMir($this->instance_id);
        $list['b_set'] = $config->getBpayConfigMir($this->website_id);
        $list['d_set'] = $config->getDpayConfigMir($this->website_id);
        $list['wx_set'] = $config->getWpayConfigMir($this->website_id);
	$list['gp_set'] = $config->getGpayConfigMir($this->website_id);
        return $list;
    }
    /**
     * 余额支付
     */
    public function payBConfigMir()
    {
        $web_config = new configServer();
        if (request()->isAjax()) {
            $is_use = request()->post('is_use', 0);
            // 获取数据
            $retval = $web_config->setBpayConfigMir($this->instance_id,$is_use);
            return AjaxReturn($retval);
        }
    }
    /**
     * 到货付款
     */
    public function payDConfigMir()
    {
        $web_config = new configServer();
        if (request()->isAjax()) {
            $is_use = request()->post('is_use', 0);
            // 获取数据
            $retval = $web_config->setDpayConfigMir($this->instance_id,$is_use);
            return AjaxReturn($retval);
        }
    }
    /**
     * 微信配置
     */
    public function payWxConfigMir()
    {
        $web_config = new configServer();
        if (request()->isAjax()) {
            // 微信支付
            $appkey = str_replace(' ', '', request()->post('appkey', ''));
            $MCH_KEY = str_replace(' ', '', request()->post('MCH_KEY', ''));
            $MCHID = str_replace(' ', '', request()->post('MCHID', ''));
            $is_use = request()->post('is_use', 0);
            // 获取数据
            $retval = $web_config->setWpayConfigMir($this->instance_id, $appkey,$MCHID,$MCH_KEY,$is_use);
            return AjaxReturn($retval);
        }
    }
	/**
     * GlobePay
     */
    public function payGpConfigMir()
    {
        $web_config = new configServer();
        if (request()->isAjax()) {
            // GlobePay
            $appid = str_replace(' ', '', request()->post('appid', ''));
            $partner_code = str_replace(' ', '', request()->post('partner_code', ''));
            $credential_code = str_replace(' ', '', request()->post('credential_code', ''));
			$currency = str_replace(' ', '', request()->post('currency', ''));
            $is_use = request()->post('is_use', 0);
            // 获取数据
            $retval = $web_config->setGpayConfigMir($this->instance_id, $appid,$partner_code,$credential_code,$currency,$is_use);
            return AjaxReturn($retval);
        }
    }
    /**
     * 临时发布小程序:用户定时没跑，但是微信审核过了的情况下
     */
    public function tempToRelease()
    {
        // 查询最新一次提交审核状态
        $lastRes = $this->wchat_open->getLasAudistatus($this->authorizer_access_token);
        $lastId = 0;
        if ($lastRes->errcode == 0) {
            $lastId = $lastRes->auditid;
        }
        $res = $this->wchat_open->release($this->authorizer_access_token);
        if ($res->errcode == 0) {
            $submit_data['review_message'] = '发布成功!';
            $submit_data['status'] = 4;
        } else {
            $submit_data['review_message'] = '发布失败：'.$res->errmsg;
            $submit_data['status'] = 3;
        }
        $mp_submit_model = new MpSubmitModel();
        $mp_submit_model->save($submit_data, ['audit_id' => $lastId]);
        echo '最后的auditid：'.$lastId ."\r\n";
        echo json_encode($res);
    }
    /**
     * 版本叠加
     * @param string $version 小程序版本
     * @return string
     */
    public function autoAddVersionForMiniprogram($version = '')
    {
        return $this->mini_program_service->autoAddVersionForMiniprogram($version);
    }

    /**
     * 小程序审核撤回
     * 【单个帐号每天审核撤回次数最多不超过1次，一个月不超过10次。】
     */
    public function recallcommitMp()
    {
        $res = $this->wchat_open->recallcommitMp($this->authorizer_access_token);
        if ($res->errcode) {
            return AjaxWXReturn($res->errcode, [], $res->errmsg);
        }
        // 修改状态
        $condition = [
            'website_id' => $this->website_id,
            'shop_id' => $this->instance_id
        ];
        $new_submit_record = $this->mini_program_service->getNewMpSubmitRecord($condition);
        $new_data = [
            'status' => 1,
            'review_message' => '撤回发布！撤回时间：'.date("Y-m-d H:i:s" ,time())
        ];
        $mp_submit_model = new MpSubmitModel();
        $mp_submit_model->save($new_data, ['id' => $new_submit_record['id']]);
        return AjaxWXReturn(SUCCESS);
    }
    /**
     * 是否填写AppSecret
     */
    public function isHasAppSecret()
    {
        $condition = [
            'website_id' => $this->website_id,
            'shop_id' => $this->instance_id
        ];
        $secret = $this->mini_program_service->getMpAuthorizerInfo($condition, 'authorizer_secret');
        if ($secret['authorizer_secret']) {
            return json(['code' => 1, 'data' => $secret['authorizer_secret']]);
        } else {
            return json(['code' => -1, 'data' => '未填写AppSecret!']);
        }
    }

    /**
     * 添加域名到小程序服务器域名
     * $add_url  需要添加到小程序服务器的域名
     */
    public function postDomainToMp()
    {
        $url = request()->post('add_url');
        $domain_data = [];
        if ($url) {
            array_push($domain_data, $url);
        }
        //第三方域名
        $third_domain_name = getIndependentDomain($this->website_id);
        if (empty($third_domain_name)) {//不含 https
            return ['errcode' => -1, 'message' => '独立域名不存在！'];
        }
        array_push($domain_data, $third_domain_name);
        // 云服务器域名
        $config_model = new ConfigModel();
        $mp_data = $config_model::get(['instance_id' => 0, 'website_id' => 0, 'key' => 'ALIOSS_CONFIG']);
        if ($mp_data) {
            $mp_info = json_decode($mp_data['value'],true);
            if ($mp_info['AliossUrl']) {
                array_push($domain_data, $mp_info['AliossUrl']);
            }
        }
        //客服域名
        if (getAddons('qlkefu', $this->website_id)) {
            $kf = new QlkefuService();
            $kfResult = $kf->qlkefuConfig($this->website_id, $this->instance_id);
            if ($kfResult['ql_domain']) {
                 array_push($domain_data, $kfResult['ql_domain']);
            }
        }
        $result = $this->mini_program_service->modifyDomain($this->authorizer_access_token, $domain_data, 'add');
        if (!$result) {
            return ['errcode' => 0, 'message' => '刷新成功'];
        }else{
            return $result;
        }
    }

    /**
     * 启用消息模板
     */
    public function addMessageTemplateRelationKey()
    {

        $template_data = json_decode($_POST['data'],true);
        $template_id = $template_data['template_id'];
        $state = $template_data['state'];
        $template_no = $template_data['template_no'];
            
        $key_list = [];
        $index = 1;
        foreach($template_data['key'] as $val) {
            $val['key_id'] = $index;
            $val['value'] = trim($val['value']);
            $val['content'] = trim($val['content']);
            $key_list[$index] = $val;
            $index++;
        }
        $relation_data = [
            'template_id' => $template_id,
            'mp_template_id' => $template_no,
            'status' => $state,
            'key_list' => json_encode($key_list)
        ];
        $relation_condition = [
            'website_id' => $this->website_id,
            'shop_id' => $this->instance_id,
            'template_id' => $template_id
        ];
        Db::startTrans();
        try {
            $relationRes = $this->mini_program_service->relationTemplateId($relation_condition);
            if ($relationRes) {
                $this->mini_program_service->changeTemplateRelationState($relation_data, $relation_condition);
            } else {
                $relation_data['website_id'] = $this->website_id;
                $relation_data['shop_id'] = $this->instance_id;
                $this->mini_program_service->addTemplateRelation($relation_data);
            }
            Db::commit();

            $this->mini_program_service->putMpTemplateIdOfRedis($relation_data);
            return AjaxReturn(SUCCESS);
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => -1, 'message' => '保存失败:'.$e->getMessage()];
        }
    }

    /**
     * 获取帐号下的模板列表
     * @return mixed
     */
    public function getTemplateList()
    {
        $result = $this->mini_program_service->getTemplateList($this->authorizer_access_token);
        if ($result['code'] < 0) {
            return $result;
        }
        return $result;
    }
    /********************* API start ****************************/
    /**
     * 小程序 - 订阅消息 - 获取模板ID
     * @return mixed|\multitype|void
     * @throws \think\Exception\DbException
     */
    public function getMpTemplateId()
    {
        $types = request()->post('type');
        if (!$types) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $types = explode(',', $types);
        $html_ids = '';
        foreach ($types as $type) {
        switch ($type) {
            case 1:
                    $html_ids .= 'pay_success,';
                break;
            case 2:
                    $html_ids .= 'order_close,';
                break;
            case 3:
                    $html_ids .= 'balance_change,';
                break;
            case 4:
                    $html_ids .= 'refund_info,';
                break;
            default :
                break;
            }
        }
        $html_ids = trim($html_ids, ',');
        if (!$html_ids) {
            return AjaxReturn(-1);
        }
        $condition = [
            'sys_mp_template_relation.website_id' => $this->website_id,
            'sys_mp_template_relation.shop_id' => $this->instance_id,
            'sys_mp_message_template.html_id' => ['in', $html_ids]
        ];
        $results = $this->mini_program_service->getMpTemplateInfo($condition);
        $return = [];
        $template_array = $this->getTemplateList();
        if ($template_array['code'] < 0) {
            return $template_array;
        }
        foreach ($results as $key => $result) {
            if (in_array($result['mp_template_id'], $template_array) && $result['message']) {
                $type = 0;
                switch ($result['message']['html_id']) {
                    case 'pay_success':
                        $type = 1;
                        break;
                    case 'order_close':
                        $type = 2;
                        break;
                    case 'balance_change':
                        $type = 3;
                        break;
                    case 'refund_info':
                        $type = 4;
                        break;
                    default :
                        break;
        }
                $return[$key] = [
                    'type' => $type,
                    'template_id' => $result['mp_template_id'],
                    'status' => $result['status']
                ];
            }
        }
        return AjaxReturn(SUCCESS, $return);
    }
    /**
     * 前端用户点击了订阅消息弹窗后，传入的数据
     * @return array|\multitype
     * @throws Exception\DbException
     */
    public function postUserMpTemplateInfo()
    {
        $uid = request()->post('uid');
        $template_list = request()->post('list/a');
        if (!$uid || !$template_list) {
            return AjaxReturn(LACK_OF_PARAMETER);
        }
        $templates = '';
        foreach ($template_list as $key => $list)
        {
            $templates .= $list['template_id'] . ',';
        }
        $templates = trim($templates, ',');
        //通过模板id查询该模板的类型
        $condition = [
            'sys_mp_template_relation.website_id' => $this->website_id,
            'sys_mp_template_relation.shop_id' => $this->instance_id,
            'sys_mp_template_relation.mp_template_id' => ['in', $templates],
        ];
        $results = $this->mini_program_service->getMpTemplateInfo($condition);
        $new_template = [];
        foreach ($results as $k => $v)
        {
            foreach ($template_list as $kk => $vv)
            {
                if (($vv['template_id'] == $v['mp_template_id']) && $v['message']) {
                    $new_template[$v['message']['html_id']] = [
                        'template_id' => $vv['template_id'],
                        'action' => $vv['action'],
                    ];
        }
            }
        }
        Db::startTrans();
        try {
            $array = [];
            $userModel = new UserModel();
            $result = $userModel->getInfo(['uid' => $uid], 'mp_sub_message');
            if ($result['mp_sub_message']) {
                $array = json_decode($result['mp_sub_message'], true);
            }
            $array = array_merge($array, $new_template);
            // 写入user表
            $userModel->save(['mp_sub_message' => json_encode($array, JSON_UNESCAPED_UNICODE)],['uid' => $uid]);
            Db::commit();
            return AjaxReturn(SUCCESS);
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => -1, 'message' => $e->getMessage()];
        }
    }
    /********************* TEST start ****************************/
    /**
     * 添加小程序订阅消息 - 商城变量
     */
    public function postMpSubKeys()
    {
        $template_id = request()->post('category');
        $name = request()->post('name');
        $condition = [];
         if ($name) {
             $condition['name'] = ['LIKE', '%' . $name . '%'];
         }
         if ($template_id) {
             $condition['template_id'] = $template_id;
         }
        $data = [
            'template_id' => $template_id,
            'name' => $name,
        ];
        return $this->mini_program_service->postMpSubKeys($condition, $data);
    }
    /**
     * 手动发布小程序
     */
    public function releaseMp()
    {
        $release_result = $this->wchat_open->release($this->authorizer_access_token);
        p($release_result);
    }
    /**
     * 手动查询发布审核状态并发布
     */
    public function updateCommitStatus()
    {
        $result = $this->wchat_open->getLasAudistatus($this->authorizer_access_token);
        $submit_data = [];
        $mp_submit_model = new MpSubmitModel();
        $condition = [
            'audit_id' => $result->auditid,
            'website_id' => $this->website_id
        ];
        Db::startTrans();
        try{
            if ($result->errcode == 0) {
                if ($result->status == 0) {// 通过审核
                    $submit_data['status'] = 0;
                    $submit_data['review_message'] = '审核成功';
                    $mp_submit_model->isUpdate(true)->save($submit_data, $condition);
                    // 发布小程序
                    $release_result = $this->wchat_open->release($this->authorizer_access_token);
                    if ($release_result->errcode == 0) {
                        $submit_data['review_message'] = '发布成功!';
                        $submit_data['status'] = 4;
                    } else {
                        $submit_data['review_message'] = '发布失败：'.$release_result->errmsg;
                        $submit_data['status'] = 3;
                    }
                } elseif ($result->status == 2){
                    $submit_data['status'] = $result->status;
                    $submit_data['review_message'] = '审核中';
                } else {
                    $submit_data['status'] = 1;
                    $submit_data['review_message'] = $result->reason;
                }
            } else {//审核失败
                $submit_data = [
                    'status' => 1,
                    'review_message' => $result->reason
                ];
            }
            $res = $mp_submit_model->isUpdate(true)->save($submit_data, $condition);
            p($res);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            p($e->getMessage());
        }
    }
    /**
     * 查询服务商的当月提审限额（quota）和加急次数
     */
    public function getQuata()
    {
        $result = $this->wchat_open->queryQuota($this->authorizer_access_token);
        p($result);
    }
}
