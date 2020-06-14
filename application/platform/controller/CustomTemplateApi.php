<?php
namespace app\platform\controller;
use data\model\CustomTemplateModel;
use data\service\CustomTemplateApi as api;
use think\db;
use data\service\GoodsCategory as GoodsCategory;

    class CustomTemplateApi extends BaseController{

        protected  $api;
        static $web_site;

        public function __construct(){
            parent::__construct();
            $this->api = new api();
            self::$web_site = $_SERVER['HTTP_HOST'];
        }


        //商城页面
        public function getshopurl(){
            $url = $this->api->getshopurl();

            return json(['code' => 0,'data' => $url]);
        }

        // 装修的页面
        public function getCustomList()
        {
            // 固定的商城首页,会员中心,分销中心，注册登录页面
            $list[0]['link'] = '/mall/index';
            $list[0]['name'] = '商城首页';
            $list[1]['link'] = '/member/centre';
            $list[1]['name'] = '会员中心';
            $list[2]['link'] = '/login';
            $list[2]['name'] = '登录页';
            $list[3]['link'] = '/register';
            $list[3]['name'] = '注册页';
            $list[4]['link'] = '/commission/centre';
            $list[4]['name'] = '分销中心';

            $custom_model = new CustomTemplateModel();
            $custom_list = $custom_model::all(['website_id' => $this->website_id, 'shop_id' => $this->instance_id, 'type' => 6]);
            foreach ($custom_list as $k => $v){
                $temp['link'] = '/diy/' . $v['id'];
                $temp['name'] = $v['template_name'];

                $list[] = $temp;
            }

            return $list;
        }


        //获取商城商品分类
        public function getshopcategory(){

            $goodscate = new GoodsCategory();
            $category_list = $goodscate->getGoodsCategoryTree(0); //商品分类
            if(empty($category_list)){
                return json("暂无数据",'0');
            }

            //将所有的分类拼装
            foreach ($category_list as $k=>$v){
                foreach ($v['child_list'] as $key=>$value){
                    if($value['is_parent']=='1'){
                        $a = $goodscate->getGoodsCategoryTree($value['category_id']);
                        $category_list[$k]['child_list'][$key]['child_list'] = $this->object2array($a);
                    }
                }
            }
            return json(['code' => 1, 'data' => $category_list]);
        }


        function object2array(&$object) {
            $object =  json_decode( json_encode( $object),true);
            return  $object;
        }


        //获取商品列表，需返回分页接口信息

        public function getgoodslist(){

            $goods_count = Db::query("select count(*) as count from vsl_goods where `state` = 1 and `website_id` = ".$this->website_id);
            $count = $goods_count['0']['count'];  //商品总数
            $limit = 10;                          //分页
            $page_count = ceil($count/$limit); //总页数大小

            $page = $_REQUEST['page']?$_REQUEST['page']:1; //当前分页
            if($page>$page_count || $page<1){
                $page = 1;
            }
            $start = $page*$limit-10;

            $goods_info = $this->api->goodslist($start,$limit);
            $data = array();
            foreach ($goods_info as $key=>$value){
                $data['page'] = $page;
                $data['page_count'] = $page_count;
                $data['page_size'] = $limit;
                $data['goods_count'] = $count;
                $data[$key]['name'] = $value['goods_name'];
                $data[$key]['price'] = $value['price'];
                $data[$key]['goods_id'] = $value['goods_id'];
                $data[$key]['url'] = self::$web_site."/index.php?s=/wap/goods/goodsdetail&id=".$value['goods_id']."&website_id=".$this->website_id;
                $data[$key]['pic'] = self::$web_site."/".$value['pic_cover'];
            }
            return json(['code' => 1, 'data' => $data]);

        }


        //搜索商品
        public function searchgoods(){

            $goods_name = $_REQUEST['goods_name']?$_REQUEST['goods_name']:'';
            $goodsinfo = $this->api->search_goods($goods_name);

            foreach ($goodsinfo as $key=>$value){
                $data[$key]['name'] = $value['goods_name'];
                $data[$key]['price'] = $value['price'];
                $data[$key]['goods_id'] = $value['goods_id'];
                $data[$key]['url'] = self::$web_site."/index.php?s=/wap/goods/goodsdetail&id=".$value['goods_id']."&website_id=".$this->website_id;
                $data[$key]['pic'] = self::$web_site."/".$value['pic_cover'];
            }
            return json(['code' => 1, 'data' => $data]);

        }

        //自动推荐商品   自营和全平台  1为自营店  2为全平台
        public function firstgoods(){

            $type = $_REQUEST['type']?intal($_REQUEST['type']):'1';
            if($type!=1 || $type!=2){
                return json(['code' => 1, 'data' => '非法参数']);
            }
            $goodslist = $this->api->autogoodslist($type);
            foreach ($goodslist as $key=>$value){
                $data[$key]['name'] = $value['goods_name'];
                $data[$key]['price'] = $value['price'];
                $data[$key]['goods_id'] = $value['goods_id'];
                $data[$key]['url'] = self::$web_site."/index.php?s=/wap/goods/goodsdetail&id=".$value['goods_id']."&website_id=".$this->website_id;
                $data[$key]['pic'] = self::$web_site."/".$value['pic_cover'];
                $data[$key]['sales'] = $value['sales'];
                $data[$key]['collects'] = $value['collects'];
                $data[$key]['sale_date'] = $value['sale_date'];  //上下架时间
            }
            return json(['code' => 1, 'data' => $data]);
        }

        //手动推荐商品，带分页信息
        public function allgoodslist(){

            $type=$_REQUEST['type']?$_REQUEST['type']:1;
            if($type=='1') {
                $goods_count = Db::query("select count(*) as count from vsl_goods where `shop_id` = 0 and `state` = 1 and `website_id` = ".$this->website_id);
            }else{
                $goods_count = Db::query("select count(*) as count from vsl_goods where `state` = 1 and `website_id` = ".$this->website_id);
            }

            $count = $goods_count['0']['count'];  //商品总数
            $limit = 10;                          //分页
            $page_count = ceil($count/$limit); //总页数大小

            $page = $_REQUEST['page']?$_REQUEST['page']:1; //当前分页
            if($page>$page_count || $page<1){
                $page = 1;
            }
            $start = $page*$limit-10;

            $goods_info = $this->api->allgoodslist($type,$start,$limit);
            $data = array();
            foreach ($goods_info as $key=>$value){
                $data['page'] = $page;
                $data['page_count'] = $page_count;
                $data['page_size'] = $limit;
                $data['goods_count'] = $count;
                $data[$key]['name'] = $value['goods_name'];
                if($type=='1' || empty($data[$key]['shop_name'])){
                    $data[$key]['shop_name'] = "自营店";
                }else{
                    $data[$key]['shop_name'] = $value['shop_name'];
                }

                $data[$key]['price'] = $value['price'];
                $data[$key]['goods_id'] = $value['goods_id'];
                $data[$key]['url'] = self::$web_site."/index.php?s=/wap/goods/goodsdetail&id=".$value['goods_id']."&website_id=".$this->website_id;
                $data[$key]['pic'] = self::$web_site."/".$value['pic_cover'];
            }
            return json(['code' => 1, 'data' => $data]);

        }


        public function returnJson($data,$code='0'){
            ob_clean();
            $returndata['code'] = $code;
            $returndata['data'] = $data;
            $result = json_encode($returndata);
            header('Content-Type:application/json');
            echo $result;exit;
        }

    }