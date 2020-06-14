<?php
namespace data\service;
use think\Db;
/**
 * sql 执行表
 */
class DbQuery extends BaseService
{
	/* (non-PHPdoc)
     * @see \data\api\IDbQuery::repair()
     */
    public function repair($tables)
    {
        // TODO Auto-generated method stub
        if($tables) {
            Db::startTrans();
            try{
                if(is_array($tables)){
                    $tables = implode('`,`', $tables);
                    $list = Db::query("REPAIR TABLE `{$tables}`");
                } else {
                    $list = Db::query("REPAIR TABLE `{$tables}`");
                }
                Db::commit();
                return showMessage("数据表修复完成", 1);
            } catch (\Exception $e) {
            recordErrorLog($e);
                // 回滚事务
                Db::rollback();
                return showMessage("数据表修复失败");
            }
        } else {
            return showMessage("请指定要修复的表");
        }
    }
	/* (non-PHPdoc)
     * @see \data\api\IDbQuery::optimize()
     */
    public function optimize($tables)
    {
        // TODO Auto-generated method stub
        if($tables) {
            if(is_array($tables)){
                $tables = implode('`,`', $tables);
                $list = Db::query("OPTIMIZE TABLE `{$tables}`");
                if($list){
                    
                    return showMessage("数据表优化完成", 1);
                } else {
                    return showMessage("数据表优化失败");
                }
            } else {
                $list = Db::query("OPTIMIZE TABLE `{$tables}`");
                if($list){
                    return showMessage("数据表优化完成", 1);
                } else {
                    return showMessage("数据表优化失败");
                }
                }
        } else {
            return showMessage("请指定要优化的表");
        }
    }
    
    private function sql_execute($sql, $is_debug){
        
        if(trim($sql) != ''){
            $sql = str_replace("\r\n", "\n", $sql);
            $sql = str_replace("\r", "\n", $sql);
            
            $sql_array = explode(";\n", $sql);
            if(!$is_debug){
                Db::startTrans();
            }
            try {
                foreach ($sql_array as $item) {
                    if($is_debug){
                        Db::startTrans();
                    }
                    $querySql = trim($item);
                    if($querySql != ''){
                        $result = @Db::execute($querySql.";");
                        if($is_debug){
                            Db::rollback();
                        }
                    }
                }
                if(!$is_debug){
                    Db::commit();
                }
                return showMessage("执行完毕", 1);
            } catch (\Exception $e) {
            recordErrorLog($e);
                Db::rollback();
               return showMessage($e->getMessage());
            }
        }else{
            return showMessage("请填写要执行的sql语句！");
        }
    }
    /**
     * sql 执行
     * @param unknown $sql
     * @return mixed|multitype:integer string
     */
    public function sqlQuery($sql){
        $result=$this->sql_execute($sql, true);
//         if($result["status"]==1){
//             $result=$this->sql_execute($sql, false);
//         }
        return $result;
    }
    public function yujjia($sql){
        Db::startTrans();
        try {
            $result = Db::query($sql);
            Db::rollback();
            return "1";
        } catch (\Exception $e) {
            recordErrorLog($e);
            Db::rollback();
            return showMessage($e->getMessage());;
        }
        
    }
    
    /**
     * 查询所有表
     * (non-PHPdoc)
     * @see \data\api\IDbQuery::getDatabaseList()
     */
    public function getDatabaseList()
    {
        // TODO Auto-generated method stub
        $databaseList = Db::query("SHOW TABLE STATUS");
        return $databaseList;
    }


     
}