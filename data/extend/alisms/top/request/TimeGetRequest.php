<?php
/**
 * TOP API: taobao.time.get request
 * 
 * @author  www.vslai.com
 * @since 1.0, 2016.03.28
 */
class TimeGetRequest
{
	
	private $apiParas = array();
	
	public function getApiMethodName()
	{
		return "taobao.time.get";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
