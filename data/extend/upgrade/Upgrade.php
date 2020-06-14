<?php
/**
 * Cms.php
 * 微商来 - 专业移动应用开发商!
 * =========================================================
 * Copyright (c) 2014 广州领客信息科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: http://www.vslai.com
 *
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================



 */
namespace data\extend\upgrade;

use think\Db;
use data\service\Upgrade as UpgradeService;
use data\service\Config;
use data\extend\database;
use \think\Session as Session;
use data\extend\dir;
/**
 * 升级
 */
class Upgrade
{
    public $backup_code=0;
    public $backup_message="数据库备份成功!";
    /**
     * 要处理的文件夹路径
     * @var unknown
     */
    protected  $deal_file_dir_array=array();
    /**
     * 要处理的文件路径
     * @var unknown
     */
    protected  $deal_file_array=array();
    protected  $dir_array=array();
    /**
     * 升级版本
     */
    public function vslai_patch_upgrade()
    {
        /**
         * 下载补丁包
         */
        $this->update_file_download();
        /**
         * 解压补丁包
         */
        $this->update_file_unzip();
        /**
         * 处理更新文件
         */
        $this->update_file_deal($this->file_start_path);
        /**
         * 更新文件备份
         */
        $this->update_file_backup();
        /**
         * 覆盖文件
         */
        $this->update_file_cover();
        if($this->upgrade_code!=0){
            //升级失败   代码恢复
            $this->update_file_regain();
        }
        $this->sql_file_path=$this->download_update_file."/vslai_".$this->version_no."_patch.sql";
        /**
         * 导入升级数据库数据
         */
        $this->update_sql_execute();

        return array(
            "code"=>$this->upgrade_code,
            "message"=>$this->upgrade_message
        );
    }

    public function create_upgrade_file($patch_release){
        $upgrade_code=0;
        $upgrade_message=0;
        $download_file_path_dow=ROOT_PATH.'download/';
        $download_file_path_upgrade=ROOT_PATH.'download/upgrade/';
        $download_file_path=ROOT_PATH.'download/upgrade/'.'vslai_patch_'.$patch_release.'/';
        if (! is_dir($download_file_path)) {
            if (! @mkdir($download_file_path, 0777, true)) {
                $upgrade_code=-1;
                $upgrade_message="创建下载目录失败，请确认download目录有写入权限！";
            }
        }
        if (! is_dir($download_file_path_dow)) {
            if (! @mkdir($download_file_path_dow, 0777, true)) {
                $upgrade_code=-1;
                $upgrade_message="创建下载目录失败，请确认download目录有写入权限！";
            }
        }
        if (! is_dir($download_file_path_upgrade)) {
            if (! @mkdir($download_file_path_upgrade, 0777, true)) {
                $upgrade_code=-1;
                $upgrade_message="创建下载目录失败，请确认download目录有写入权限！";
            }
        }
        return array(
            "upgrade_code"=>$upgrade_code,
            "upgrade_message"=>$upgrade_message
        );

    }
    /**
     * 下载升级包
     */
    public function update_file_download($download_url, $patch_release)
    {
        $upgrade_code=0;
        $upgrade_message="更新包下载成功!";
        $result=$this->create_upgrade_file($patch_release);
        if($result["upgrade_code"]!=0){
            return $result;
        }
        try {
            $data = Http::doGet($download_url, 20);
            $length_str=strlen($data);
            if($length_str<500){
                $upgrade_code=-1;
                $upgrade_message="更新包下载有误!";
            }else{
                $fileName = explode('/', $download_url);
                $fileName = end($fileName);
                //初始化下载路径
                $download_file_path=ROOT_PATH.'download/upgrade/'.'vslai_patch_'.$patch_release.'/';
                $download_zip_path = $download_file_path.$fileName;
                //处理解压路径
                $update_name=str_replace(".zip", "", $fileName);
                $download_update_file=$download_file_path.$update_name;

                if (! @file_put_contents($download_zip_path, $data)) {
                    $upgrade_code=-1;
                    $upgrade_message="下载补丁包失败！下载路径：".$download_url;
                }

                $download_back_file=$download_update_file.'_backup/';
                if (! is_dir($download_back_file)) {
                    if (! @mkdir($download_back_file, 0777, true)) {
                        $upgrade_code=-1;
                        $upgrade_message="创建备份目录失败，请确认download目录有写入权限！";
                    }
                }
            }
        } catch (\Exception $e) {
            $upgrade_code=-1;
            $upgrade_message="下载补丁包失败!";
        }
        return array(
            "upgrade_code"=>$upgrade_code,
            "upgrade_message"=>$upgrade_message,
            "download_file_path"=>$download_file_path,
            "download_zip_path"=>$download_zip_path,
            "download_update_file"=>$download_update_file,
            "download_back_file"=>$download_back_file
        );
    }

    /**
     * 解压版本
     */
    public function update_file_unzip($download_zip_path, $download_file_path, $download_update_file)
    {
        $upgrade_code=0;
        $upgrade_message="更新包解压成功!";
        $file_start_path="";
        try {
            $unzip=new Unzip();
            $result=$unzip->unzip($download_zip_path, $download_file_path);
            if(!$result){
                $upgrade_code=-1;
                $upgrade_message="更新包解压失败!！";
            }
            /**
             * 检索文件开始路径
             */
            $file_start_path=$download_update_file."/vslai_b2c/";
        } catch (\Exception $e) {
            $upgrade_code=-1;
            $upgrade_message="更新包解压失败!";
        }
        return array(
            "upgrade_code"=>$upgrade_code,
            "upgrade_message"=>$upgrade_message,
            "file_start_path"=>$file_start_path,
        );
    }
    /**
     * 检索需要更新的文件
     */
    public function update_file_deal($file_start_path)
    {
        $upgrade_code=0;
        $upgrade_message="处理更新文件成功!";
        try {
            if (is_dir($file_start_path)) {
                if ($dh = opendir($file_start_path)) {
                    while (($file = readdir($dh)) !== false) {
                        if ((is_dir($file_start_path . "/" . $file)) && $file != "." && $file != "..") {
                            // 当前目录问文件夹
                            $this->deal_file_dir_array[]=$file;
                            array_push($this->dir_array,$file);
                        } else {
                            if ($file != "." && $file != "..") {
                                // 当前目录为文件
                                $this->deal_file_array[]=$file;
                            }
                        }
                    }
                    closedir($dh);
                }
            }
            if(count($this->deal_file_dir_array)>0){
                $this->get_update_file($file_start_path);
            }
        } catch (\Exception $e) {
            $upgrade_code=-1;
            $upgrade_message="处理更新文件失败!";
        }
        return array(
            "upgrade_code"=>$upgrade_code,
            "upgrade_message"=>$upgrade_message,
            "deal_file_array"=>array_merge($this->deal_file_array,$this->dir_array)
        );
    }
    /**
     * 目录下的文件
     */
    private function get_update_file($file_start_path){
        while (count($this->deal_file_dir_array)>0){
            $length=count($this->deal_file_dir_array);
            for ($i=0;$i<$length; $i++){
                $dir_path=$this->deal_file_dir_array[$i];
                $deal_path=$file_start_path.'/'.$dir_path;
                if (is_dir($deal_path)) {
                    if ($dh = opendir($deal_path)) {
                        while (($file = readdir($dh)) !== false) {
                            if ((is_dir($deal_path . "/" . $file)) && $file != "." && $file != "..") {
                                // 当前目录问文件夹
                                $this->deal_file_dir_array[]=$dir_path."/".$file;
                                array_push($this->dir_array,$dir_path."/".$file);
                            } else {
                                if ($file != "." && $file != "..") {
                                    // 当前目录为文件
                                    $this->deal_file_array[]=$dir_path."/".$file;
                                }
                            }
                        }
                        closedir($dh);
                    }
                }
                unset($this->deal_file_dir_array[$i]);
                $length=$length-1;
                $this->deal_file_dir_array=array_values($this->deal_file_dir_array);
            }
        }
    }

    /**
     * 检测升级文件路径的权限
     */
    public function detect_file_permission($deal_file_array){
        $upgrade_code=0;
        $upgrade_message="权限符合更新要求，可以正常更新！";
        foreach ($deal_file_array as $file_path){
            $file_path=str_replace("\\", "/", $file_path);
            $file_str=explode("/", $file_path);
            if(file_exists($file_path)) {
                //如果该文件存在，检查是否可写
                if(!is_writable($file_path)){
                    $upgrade_code=-1;
                    $upgrade_message=$file_path.", 该目录权限不足!";
                    break;
                }
            } else {
                //如果此文件不存在，则进行目录检查是否可写
                $dirName = dirname($file_path);
                if(!$this->dirWriteable($dirName)){
                    $upgrade_code=-1;
                    $upgrade_message=$file_path.", 该目录权限不足!";
                    break;
                }
            }
        }
        return array(
            "upgrade_code"=>$upgrade_code,
            "upgrade_message"=>$upgrade_message
        );
    }
    /**
     * 文件备份
     */
    public function update_file_backup($deal_file_array, $download_back_file){
        $upgrade_code=0;
        $upgrade_message="文件备份成功!";
        try {
            foreach ($deal_file_array as $file_path){
                $result=$this->create_backup_file($file_path, $download_back_file);
                if($result<0){
                    $upgrade_code=-1;
                    $upgrade_message="文件备份失败!";
                    break;
                }
            }
        } catch (\Exception $e) {
            $upgrade_code=-1;
            $upgrade_message="文件备份失败,".$e->getMessage();
        }
        return array(
            "upgrade_code"=>$upgrade_code,
            "upgrade_message"=>$upgrade_message
        );
    }
    /**
     * 创建文件备份的文件夹
     */
    public function create_backup_file($file_path, $back_path){
        try {
            $file_path=str_replace("\\", "/", $file_path);
            $file_str=explode("/", $file_path);
            $from_path=ROOT_PATH.$file_path;
            $to_path="";
            if(count($file_str)>1){
                for ($i=0; $i<count($file_str); $i++){
                    $middle_path=$file_str[$i];
                    if($middle_path==end($file_str)){
                        $to_path=$back_path.$middle_path;
                    }else{
                        $back_path=$back_path.$middle_path."/";
                        if (! is_dir($back_path)) {
                            @mkdir($back_path, 0777, true);
                        }
                    }
                }
            }else{
                $to_path=$back_path.$file_path;
            }
            if (file_exists($from_path)){
                @copy($from_path,$to_path);
            }
            return 1;
        } catch (\Exception $e) {
            return -1;
        }

    }

    /**
     * 文件覆盖
     */
    public function update_file_cover($deal_file_array, $file_start_path){
        $upgrade_code=0;
        $upgrade_message="文件覆盖成功!";
        try {
            foreach ($deal_file_array as $file_path){
                $from_path=$file_start_path.$file_path;
                $to_path=ROOT_PATH.$file_path;
                if (file_exists($from_path) && file_exists($to_path)){
                    @copy($from_path, $to_path);
                }
                if(file_exists($to_path) && !file_exists($from_path)){
                    if (is_dir($to_path)) {
                        $dirs = new dir($to_path);
                        $dirs->delDir($to_path);
                    }else{
                        @unlink($to_path);
                    }
                }
                if(!file_exists($to_path) && file_exists($from_path)){
                    if (is_dir($from_path)) {
                        mkdir($to_path, 0777, true);
                    }else{
                        @copy($from_path, $to_path);
                    }
                }
            }
        } catch (\Exception $e) {
            $upgrade_code=-1;
            $upgrade_message="文件覆盖失败!";
        }
        return array(
            "upgrade_code"=>$upgrade_code,
            "upgrade_message"=>$upgrade_message
        );
    }
    /**
     * 数据库升级  整体执行
     * 导入sql文件
     */
    public function update_sql_execute($download_update_file, $version_no)
    {
        $upgrade_code=0;
        $upgrade_message="数据库导入成功!";
        Db::startTrans();
        try {
            $sqlfile = $download_update_file."/vslai_".$version_no."_patch.sql";
            if (file_exists($sqlfile)){
                $sql = file_get_contents($sqlfile);
                $sql = str_replace("\r", "\n", $sql);
                $sql = explode(";\n", $sql);
                $execute_sql="";
                if ($sql) {
                    foreach ($sql as $k=>$v) {
                        $execute_sql.=$v.";";
                    }
                }
                Db::execute($execute_sql);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $upgrade_code=-1;
            $upgrade_message="数据库升级失败, 请下载升级包手动执行sql语句!".$e->getMessage();
        }
        return array(
            "upgrade_code"=>$upgrade_code,
            "upgrade_message"=>$upgrade_message
        );
    }
    /**
     * 数据库升级   分条执行
     * @param unknown $download_update_file
     * @param unknown $version_no
     */
    public function execute_upgrade_sql($download_update_file, $version_no){
        $sqlfile = $download_update_file."/vslai_".$version_no."_patch.sql";
        $result=$this->sql_execute($sqlfile, true);
        if($result["upgrade_code"]==0){
            $result=$this->sql_execute($sqlfile, false);
        }
        return $result;
    }
    /**
     *  执行sql 语句
     * @param unknown $sqlfile
     */
    private function sql_execute($sqlfile, $is_debug){
        $upgrade_code=0;
        $upgrade_message="数据库导入成功!";
        if(!$is_debug){
            Db::startTrans();
        }
        try {
            if (file_exists($sqlfile)){
                $sql = file_get_contents($sqlfile);
                $sql = str_replace("\r\n", "\n", $sql);
                $sql = str_replace("\r", "\n", $sql);
                $sql = explode(";\n", $sql);
                if ($sql) {
                    foreach ($sql as $k=>$v) {
                        if($is_debug){
                            Db::startTrans();
                        }
                        $querySql = trim($v);
                        if(!empty($querySql)){
                            if($is_debug){
                                Db::execute($querySql);
                            }else{
                                if(stripos($querySql,"alter table")=== false && stripos($querySql,"create table")=== false){
                                    Db::execute($querySql);
                                }
                            }
                        }
                        if($is_debug){
                            Db::rollback();
                        }
                    }
                }
            }
            if(!$is_debug){
                Db::commit();
            }
        } catch (\Exception $e) {
            Db::rollback();
            $upgrade_code=-1;
            $upgrade_message="数据库升级失败, 请下载升级包手动执行sql语句!".$e->getMessage();
        }
        return array(
            "upgrade_code"=>$upgrade_code,
            "upgrade_message"=>$upgrade_message
        );
    }

    /**
     * 更新失败 还原文件
     */
    public function update_file_regain($download_back_file){
        $upgrade_code=0;
        $upgrade_message="版本文件恢复成功!";
        try {
            //得到要恢复的文件
            $result=$this->update_file_deal($download_back_file);
            $deal_file_array=array();
            if($result["upgrade_code"]==0){
                $deal_file_array=$result["deal_file_array"];
                $deal_file_array=json_decode($deal_file_array, true);
            }
            foreach ($deal_file_array as $file_path){
                $from_path=$download_back_file.$file_path;
                $to_path=ROOT_PATH.$file_path;
                if (file_exists($from_path)){
                    @chmod($from_path, 0777);
                    @copy($from_path,$to_path);
                    @chmod($to_path, 0777);
                }
            }
        } catch (\Exception $e) {
            $upgrade_code=-1;
            $upgrade_message="文件恢复失败!".$e->getMessage();
        }
        return array(
            "upgrade_code"=>$upgrade_code,
            "upgrade_message"=>$upgrade_message
        );
    }
    /**
     * 检查是否可写
     * @param unknown $dir
     * @return number
     */
    private function dirWriteable($dir)
    {
        $writeable = 0;
        if(!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        if(is_dir($dir)) {
            if($fp = @fopen("$dir/test.txt", 'w')) {
                @fclose($fp);
                @unlink("$dir/test.txt");
                $writeable = 1;
            } else {
                $writeable = 0;
            }
        }
        return $writeable;
    }
    /**
     * 数据库备份
     */
    public function database_backup($real_time){
        try {
            $web_config = new Config();
            $database_list = $web_config->getDatabaseList();
            $name_array=array();
            foreach ($database_list as $database_obj){
                $name_array[]=$database_obj["Name"];
            }
            $result=$this->exportDatabase($name_array, "", "",$real_time);
            $is_end=0;
            while ($is_end==0){
                if(is_array($result)){
                    $result=$this->exportDatabase('',$result["tab"]["id"], $result["tab"]["start"],$real_time);
                }else{
                    $is_end=1;
                }
            }
        } catch (\Exception $e) {
            $this->backup_code=-1;
            $this->backup_message="数据库备份失败!";
        }

        return array(
            "code"=>$this->backup_code,
            "message"=>$this->backup_message
        );
    }
    /**
     ** 备份数据
     */
    public function exportDatabase($tables, $id, $start,$real_time){
        if(!empty($tables) && is_array($tables)){ //初始化
            //读取备份配置
            $config = array(
                'path'	 => ROOT_PATH,
                'part'	 => 20971520,
                'compress' => 1,
                'level'	=> 9,
            );
            Session::set('backup_config', $config);
            //生成备份文件信息
            $file = array(
                'name' => date('Ymd-His', $real_time),
                'part' => 1
            );
            Session::set('backup_file', $file);
            //缓存要备份的表
            Session::set('backup_tables', $tables);
            //创建备份文件
            $database = new database($file, $config);
            if(false !== $database->create()){
                $tab = array('id' => 0, 'start' => 0);
                $data=array();
                $data['status']=1;
                $data['message']='初始化成功';
                $data['tables']=$tables;
                $data['tab']=$tab;
                return $data;
            } else {
                $this->backup_code=-1;
                $this->backup_message="backup_set_error";
                return ;
            }
        } elseif (is_numeric($id) && is_numeric($start)) { //备份数据
            $tables = session('backup_tables');
            //备份指定表
            $database = new database(session('backup_file'), session('backup_config'));
            $start  = $database->backup($tables[$id], $start);
            if(false === $start){ //出错
                $this->backup_code=-1;
                $this->backup_message="备份失败!";
                return ;
            } elseif (0 === $start) { //下一表
                if(isset($tables[++$id])){
                    $tab = array('id' => $id,'table'=>$tables[$id],'start' => 0);
                    $data=array();
                    $data['rate'] = 100;
                    $data['status']=1;
                    $data['info']='备份完成！';
                    $data['tab']=$tab;
                    return $data;
                } else { //备份完成，清空缓存
                    session('backup_tables', null);
                    session('backup_file', null);
                    session('backup_config', null);
                    return ;
                }
            } else {
                $tab  = array('id' => $id,'table'=>$tables[$id],'start' => $start[0]);
                $rate = floor(100 * ($start[0] / $start[1]));
                $data=array();
                $data['status']=1;
                $data['rate'] = $rate;
                $data['info']="正在备份...({$rate}%)";
                $data['tab']=$tab;
                return  $data;
            }
        } else { //出错
            $this->backup_code=-1;
            $this->backup_message="备份失败!";
            return ;
        }
    }
}