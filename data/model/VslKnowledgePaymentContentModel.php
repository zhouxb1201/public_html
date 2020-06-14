<?php

namespace data\model;

use data\model\BaseModel as BaseModel;

/**
 * 付费内容表
 * @author  www.vslai.com
 *
 */
class VslKnowledgePaymentContentModel extends BaseModel
{
    protected $table = 'vsl_knowledge_payment_content';
    protected $rule = [
        'id' => '',
    ];
    protected $msg = [
        'id' => '',
    ];

}