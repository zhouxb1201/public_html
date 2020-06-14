<?php
namespace data\service;

/**
 * 系统配置业务层
 */
use data\model\ConfigModel as ConfigModel;
use data\service\BaseService as BaseService;
use think\Cache;
use think\Db;

class CustomTemplateApi extends BaseService
{

    private $config_module;
    static $website;
    static $websiteid;

    function __construct()
    {
        parent::__construct();
        $this->config_module = new ConfigModel();
        self::$website = $this->website_id;
    }


    //获取商城页面，页面分布不集中，暂时写死
    public function getshopurl(){

        $shop_url = array();
        $shop_url['0'] = array("name"=>"首页","url"=>self::$website.'/index.php?s=/wap&website_id='.self::$websiteid);//首页
        $shop_url['1'] = array("name"=>"购物车","url"=>self::$website.'/index.php?s=/wap/goods/goodsclassificationlist&website_id='.self::$websiteid);//购物车
        $shop_url['2'] = array("name"=>"会员中心","url"=>self::$website.'/index.php?s=/wap/member/index&website_id='.self::$websiteid);//会员中心
        $shop_url['3'] = array("name"=>"个人资料","url"=>self::$website.'/index.php?s=/wap/member/personaldata&shop_id=0&website_id='.self::$websiteid);//首页
        return $shop_url;
    }


    //获取商城分类
    public function getgoodscategory(){

        return Db::table('vsl_goods_category')->where('website_id',self::$website)->select();
    }


    //获取商品列表，需返回分页信息
    public function goodslist($start,$limit){
        $sql = "select a.*,b.`pic_cover` from vsl_goods as a LEFT JOIN `sys_album_picture` as b on a.`picture` = b.`pic_id` where a.`website_id` = ".self::$websiteid." and a.`state` = 1 order by a.`sort` limit $start,$limit";
        return Db::query($sql);
    }

    //商品搜索
    public function search_goods($goodsname){

        $sql = "select a.*,b.`pic_cover` from vsl_goods as a LEFT JOIN `sys_album_picture` as b on a.`picture` = b.`pic_id` where a.`goods_name` like '%$goodsname%' and a.`website_id` = ".self::$websiteid." and a.`state` = 1 order by a.`sort`";
        return Db::query($sql);
    }

    //自动推荐商品 最大数量30个，分全平台和自营店 $type  1:自营商品  2:全平台
    public function autogoodslist($type){
        if($type=='1'){

            $sql = "select a.*,b.`pic_cover` from vsl_goods as a LEFT JOIN `sys_album_picture` as b on a.`picture` = b.`pic_id` where a.`shop_id`=0 and a.`website_id` = ".self::$websiteid." and a.`state` = 1 order by a.`sort` limit 0 ,30";
        }else{

            $sql = "select a.*,b.`pic_cover` from vsl_goods as a LEFT JOIN `sys_album_picture` as b on a.`picture` = b.`pic_id` where a.`website_id` = ".self::$websiteid." and a.`state` = 1 order by a.`sort` limit 0 ,30";

        }
        return Db::query($sql);
    }

    //手动推荐商品，自定的和全平台，列表展示所有商品
    public function allgoodslist($type,$start,$limit){

        if($type=='1'){
            $sql = "select a.*,b.`pic_cover` from vsl_goods as a LEFT JOIN `sys_album_picture` as b on a.`picture` = b.`pic_id` where a.`shop_id`=0 and  a.`website_id` = ".self::$websiteid." and a.`state` = 1 order by a.`sort` limit $start,$limit";
        }else{
            $sql = "select a.*,b.`pic_cover`,c.`shop_name` from vsl_goods as a left JOIN `sys_album_picture` as b on a.`picture` = b.`pic_id` left JOIN `vsl_shop` as c on a.`shop_id` = c.`shop_id` and a.`website_id` = c.`website_id` where a.`website_id` = ".self::$websiteid." and a.`state` = 1 order by a.`sort` limit $start,$limit";
        }
        return Db::query($sql);
    }



}