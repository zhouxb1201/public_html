<?php
namespace data\extend\globepay;
/**
 *
 * 数据对象基础类，该类中定义数据类最基本的行为，包括：
 * 计算/设置/获取签名、输出json格式的参数、从json读取数据对象等
 * @author Leijid
 *
 */
class GlobePayDataBase
{
    protected $pathValues = array();

    protected $queryValues = array();

    protected $bodyValues = array();


    /**
     * 设置随机字符串，不长于30位。推荐随机数生成算法
     * @param string $value
     **/
    public function setNonceStr($value)
    {
        $this->queryValues['nonce_str'] = $value;
    }

    /**
     * 获取随机字符串，不长于30位。推荐随机数生成算法的值
     * @return 值
     **/
    public function getNonceStr()
    {
        return $this->queryValues['nonce_str'];
    }

    /**
     * 判断随机字符串，不长于32位。推荐随机数生成算法是否存在
     * @return true 或 false
     **/
    public function isNonceStrSet()
    {
        return array_key_exists('nonce_str', $this->queryValues);
    }

    /**
     * 设置时间戳
     * @param long $value
     **/
    public function setTime($value)
    {
        $this->queryValues['time'] = $value;
    }

    /**
     * 获取时间戳
     * @return 值
     **/
    public function getTime()
    {
        return $this->queryValues['time'];
    }

    /**
     * 判断时间戳是否存在
     * @return true 或 false
     **/
    public function isTimeSet()
    {
        return array_key_exists('time', $this->queryValues);
    }

    /**
     * 设置签名，详见签名生成算法
     * @param string $value
     **/
    public function setSign()
    {
        $sign = $this->makeSign();
        $this->queryValues['sign'] = $sign;
        return $sign;
    }

    /**
     * 获取签名，详见签名生成算法的值
     * @return 值
     **/
    public function getSign()
    {
        return $this->queryValues['sign'];
    }

    /**
     * 判断签名，详见签名生成算法是否存在
     * @return true 或 false
     **/
    public function isSignSet()
    {
        return array_key_exists('sign', $this->queryValues);
    }

    /**
     * 格式化参数格式化成url参数
     */
    public function toQueryParams()
    {
        $buff = "";
        foreach ($this->queryValues as $k => $v) {
            if ($v != "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff = trim($buff, "&");
        return $buff;
    }

    /**
     * 格式化参数格式化成json参数
     */
    public function toBodyParams()
    {
        return json_encode($this->bodyValues);
    }

    /**
     * 格式化签名参数
     */
    public function toSignParams()
    {
        $buff = "";
        $buff .= GlobePayConfig::PARTNER_CODE . '&' . $this->getTime() . '&' . $this->getNonceStr() . "&" . GlobePayConfig::CREDENTIAL_CODE;
        return $buff;
    }
	/**
     * 格式化签名参数
     */
    public function toSignParams_new($payconfig,$time,$nonce_str)
    {
        $buff = "";
        $buff .= $payconfig['partner_code'] . '&' . $time . '&' . $nonce_str . "&" . $payconfig['credential_code'];
        return $buff;
    }
    /**
     * 生成签名
     * @return 签名，本函数不覆盖sign成员变量，如要设置签名需要调用setSign方法赋值
     */
    public function makeSign()
    {
        //签名步骤一：构造签名参数
        $string = $this->toSignParams();
        //签名步骤三：SHA256加密
        $string = hash('sha256', utf8_encode($string));
        //签名步骤四：所有字符转为小写
        $result = strtolower($string);
        return $result;
    }
	/**
     * 生成签名
     * @return 签名，本函数不覆盖sign成员变量，如要设置签名需要调用setSign方法赋值
     */
    public function makeSign_new($payconfig,$time,$nonce_str)
    {
        //签名步骤一：构造签名参数
        $string = $this->toSignParams_new($payconfig,$time,$nonce_str);
        //签名步骤三：SHA256加密
        $string = hash('sha256', utf8_encode($string));
        //签名步骤四：所有字符转为小写
        $result = strtolower($string);
        return $result;
    }
    /**
     * 获取设置的path参数值
     */
    public function getPathValues()
    {
        return $this->pathValues;
    }

    /**
     * 获取设置的query参数值
     */
    public function getQueryValues()
    {
        return $this->queryValues;
    }

    /**
     * 获取设置的body参数值
     */
    public function getBodyValues()
    {
        return $this->bodyValues;
    }
}

