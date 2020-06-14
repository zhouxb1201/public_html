<?php
namespace data\model;

use think\Model;
use think\Db;
use think\Validate;
use think\Loader;

class BaseModel extends Model
{
    protected $error = 0;

    protected $table;
    
    protected $rule = [];
    
    protected $msg = [];
    
    protected $Validate;
    
    public function __construct($data = []){
        parent::__construct($data);
        $this->Validate = new Validate($this->rule, $this->msg);
        $this->Validate->extend('no_html_parse', function ($value, $rule) {
            return true;
        });  
    } 
    /**
     * 获取空模型
     */
    public function getEModel($tables)
    {
        $rs = Db::query('show columns FROM `' . config('database.prefix') . $tables . "`");
        $obj = [];
        if ($rs) {
            foreach ($rs as $key => $v) {
                $obj[$v['Field']] = $v['Default'];
                if ($v['Key'] == 'PRI')
                    $obj[$v['Field']] = 0;
            }
        }
        return $obj;
    }

    public function save($data = [], $where = [], $sequence = null){
        $data = $this->htmlClear($data);
        $retval = parent::save($data, $where, $sequence);
        if(!empty($where))
        {
            //表示更新数据
            if($retval == 0)
            {
                if($retval !== false)
                {
                    $retval = 1;
                }
            }
        } 
//         $retval = ['code' => $code, 'message' => $this->getError()];
        return $retval;
    }
    
    public function ihtmlspecialchars($string) {
        if(is_array($string)) {
            foreach($string as $key => $val) {
                $string[$key] = $this->ihtmlspecialchars($val);
            }
        } else {
            $string = preg_replace('/&amp;((#(d{3,5}|x[a-fa-f0-9]{4})|[a-za-z][a-z0-9]{2,5});)/', '&\1',
                str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $string));
        }
        return $string;
    }
    
    protected function htmlClear($data){
        $rule =  $this->rule;
        $info = empty($rule) ? $this->Validate : $rule;
        foreach ($data as $k=>$v){
            if (!empty($info)) {
                if (is_array($info)) {
                    $is_Specialchars=$this->is_Specialchars($info, $k);
                    // 数据对象赋值
                    if($is_Specialchars){
                        $data[$k] = $this->ihtmlspecialchars($v);
                    }else{
                        $data[$k] = $v;
                    }
//                     foreach ($rule as $key => $value) {
//                         if(strcasecmp($value,"no_html_parse")!= 0){
//                             $data[$k] = $this->ihtmlspecialchars($v);
//                         }else{
//                             $data[$k] = $v;
//                         }
//                     }
                } else {
                    ;
                }
            }            
        }
        return $data;
    }
    
    /**
     * 判断当前k 是否在数组的k值中
     * @param unknown $rule
     * @param unknown $k
     */
    protected function is_Specialchars($rule, $k){
        $is_have=true;
        foreach ($rule as $key => $value) {
            if($key==$k){
                if(strcasecmp($value,"no_html_parse")!= 0){
                    $is_have=true;
                }else{
                    $is_have=false;
                }
            }
        }
        return $is_have;
    }
    
    /**
     * 数据库开启事务
     */
    public function startTrans()
    {
        Db::startTrans();
    }

    /**
     * 数据库事务提交
     */
    public function commit()
    {
        Db::commit();
    }

    /**
     * 数据库事务回滚
     */
    public function rollback()
    {
        Db::rollback();
    }

    /**
     * 列表查询
     *
     * @param unknown $page_index            
     * @param number $page_size
     * @param string $condition
     * @param string $order            
     * @param string $field
     */
    public function pageQuery($page_index, $page_size, $condition, $order, $field)
    {
        $count = $this->where($condition)->count();
        if ($page_size == 0) {
            $list = $this->field($field)
                ->where($condition)
                ->order($order)
                ->select();
            $page_count = 1;
        } else {
            $start_row = $page_size * ($page_index - 1);
            $list = $this->field($field)
                ->where($condition)
                ->order($order)
                ->limit($start_row . "," . $page_size)
                ->select();
            if ($count % $page_size == 0) {
                $page_count = $count / $page_size;
            } else {
                $page_count = (int) ($count / $page_size) + 1;
            }
        }
        return array(
            'data' => $list,
            'total_count' => $count,
            'page_count' => $page_count
        );
    }
    /**
     * 获取一定条件下的列表
     * @param unknown $condition
     * @param unknown $field
     */
    public function getQuery($condition, $field, $order)
    {
        $list = $this->field($field)->where($condition)->order($order)->select();
        return $list;
    }
    /**
     * 获取一定条件下的列表
     * @param unknown $condition
     * @param unknown $field
     */
    public function getQuerys($condition, $field,$conditionOr, $order)
    {
        $list = $this->field($field)->where($condition)->whereOr($conditionOr)->order($order)->select();
        return $list;
    }
    /**
     * 获取一定条件下的字段
     * @param unknown $condition
     * @param unknown $field
     */
    public function Query($condition,$field)
    {
        $list = $this->where($condition)->column($field);
        return $list;
    }
    
    /**
     * 获取关联查询列表
     *
     * @param object $viewObj
     *            对应view对象
     * @param int $page_index
     * @param int $page_size
     * @param array $condition
     * @param string $order
     * @return multitype:number unknown
     */
    public function viewPageQuery($viewObj, $page_index, $page_size, $condition, $order)
    {
        if ($page_size == 0) {
            $list = $viewObj->where($condition)
                ->order($order)
                ->select();
        } else {
            $start_row = $page_size * ($page_index - 1);
            $list = $viewObj->where($condition)
                ->order($order)
                ->limit($start_row . "," . $page_size)
                ->select();
        }
        return $list;
    }
    public function viewPageQuerys($viewObj, $page_index, $page_size, $condition, $order,$group)
    {
        if ($page_size == 0) {
            $list = $viewObj->where($condition)->group($group)
                ->order($order)
                ->select();
        } else {
            $start_row = $page_size * ($page_index - 1);
            $list = $viewObj->where($condition)->group($group)
                ->order($order)
                ->limit($start_row . "," . $page_size)
                ->select();
        }
        return $list;
    }
    /**
     * 获取关联查询数量
     *
     * @param unknown $viewObj
     *            视图对象
     * @param unknown $condition
     *            下旬条件
     * @return unknown
     */
    public function viewCount($viewObj, $condition)
    {
        $count = $viewObj->where($condition)->count();
        return $count;
    }

    /**
     * 设置关联查询返回数据格式
     *
     * @param array $list
     *            查询数据列表
     * @param int $count
     *            查询数据数量
     * @param int $page_size
     *            每页显示条数
     * @return multitype|array
     */
    public function setReturnList($list, $count, $page_size)
    {
        if($page_size == 0)
        {
            $page_count = 1;
        }else{
//            if ($count % $page_size == 0) {
//                $page_count = $count / $page_size;
//            } else {
//                $page_count = (int) ($count / $page_size) + 1;
//            }
            $page_count = ceil($count / $page_size);
        }
        return array(
            'data' => $list,
            'total_count' => $count,
            'page_count' => $page_count
        );
    }

    /**
     * 获取单条记录的基本信息
     *
     * @param unknown $condition            
     * @param string $field            
     */
    public function getInfo($condition = '', $field = '*')
    {
        $info = Db::table($this->table)->where($condition)
            ->field($field)
            ->find();
        return $info;
    }
    /**
     * 查询数据的数量
     * @param unknown $condition
     * @return unknown
     */
    public function getCount($condition)
    {
        $count = Db::table($this->table)->where($condition)
        ->count();
        return $count;
    }
    /**
     * 查询条件数量
    * @param unknown $condition
    * @param unknown $field
    * @return number|unknown
    */
    public function getSum($condition, $field)
    {
        $sum = Db::table($this->table)->where($condition)
            ->sum($field);
        if(empty($sum))
        {
            return 0;
        }else
            return $sum;
    }
    /**
     * 查询多个数据最大值
     * @param unknown $condition
     * @param unknown $field
     * @return number|unknown
     */
    public function getFieldSum($condition, $field)
    {
        $sum = Db::table($this->table)->where($condition)->field($field)
            ->find();
        if(empty($sum))
        {
            return 0;
        }else
            return $sum;
    }
    /**
     * 查询数据最大值
     * @param unknown $condition
     * @param unknown $field
     * @return number|unknown
     */
    public function getMax($condition, $field)
    {
        $max = Db::table($this->table)->where($condition)
        ->max($field);
        if(empty($max))
        {
            return 0;
        }else
            return $max;
    }
    /**
     * 查询数据最小值
     * @param unknown $condition
     * @param unknown $field
     * @return number|unknown
     */
    public function getMin($condition, $field)
    {
        $min = Db::table($this->table)->where($condition)
        ->min($field);
        if(empty($min))
        {
            return 0;
        }else
            return $min;
    }
    /**
     * 查询数据均值
     * @param unknown $condition
     * @param unknown $field
     */
    public function getAvg($condition, $field)
    {
        $avg = Db::table($this->table)->where($condition)
        ->avg($field);
        if(empty($avg))
        {
            return 0;
        }else
            return $avg;
    }
    /**
     * 查询第一条数据
     */
    public function getFirstData($condition, $order, $field = '*')
    {
        $data = Db::table($this->table)->where($condition)->field($field)->order($order)
        ->limit(1)->select();
        if(!empty($data))
        {
            return $data[0];
        }else
            return '';
    }
    /**
     * 修改表单个字段值
     * @param unknown $pk_id
     * @param unknown $field_name
     * @param unknown $field_value
     */
    public function ModifyTableField($pk_name, $pk_id, $field_name, $field_value)
    {
        $data = array(
            $field_name => $field_value
        );
        $res = $this->save($data,[$pk_name => $pk_id]);
        return $res;
    }
    public function delData($condition)
    {
        $data = Db::table($this->table)->where($condition)->delete();
        return $data;

    }
    /**
     * 刚新增/更新的主键key
     * @param array $data array [更新数据]
     * @param array $where array [条件]
     * @param null $primary_key string [主键id]
     * @return false|int|mixed|string|null
     */
    public function saveGetPrimaryKey(array $data = [], $where = [], $primary_key = null)
    {
        $primary_key = $primary_key ?: $this->getPk();//模型主键
        $id = null;
        try {
            if ($where) {//更新
                $res = $this->save($data, $where);
                if ($res) {
                    $id = $this->getInfo($where, $primary_key)[$primary_key];
                }
            } else {//新增
                $id = $this->save($data,[], $primary_key);
            }
            return $id;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * 随机获取 $limit 条数据
     * @param array $condition
     * @param string $field
     * @param int $limit 获取调试
     * @return false|\PDOStatement|string|\think\Collection array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getRand($condition = [], $field = '*', $limit = 1)
    {
        $res = Db::table($this->table)->where($condition)->field($field)->order('rand()')->limit(1, $limit)->select();
        if ($limit == 1) {
            return $res[0];
        }
        return $res;
    }
}
