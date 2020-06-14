// 接口数据文件
const app = getApp();
let publicUrl = wx.getStorageSync('publicUrl');
if (publicUrl) {
  app.publicUrl = publicUrl
} else {

  publicUrl = app.publicUrl
}

var open_api = {
  get_config: publicUrl + '/wapapi/config',
  get_custom: publicUrl + '/wapapi/custom',
  get_login: publicUrl + '/wapapi/login',
  get_oauthLogin: publicUrl + '/wapapi/login/oauthLogin',
  get_oauthLogin_new: publicUrl + '/wapapi/login/oauthLogin_new',
  get_register: publicUrl + '/wapapi/login/register',
  get_registerProtocol: publicUrl + '/wapapi/login/registerProtocol',
  get_resetPassword: publicUrl + '/wapapi/login/resetPassword',
  get_CountryCode: publicUrl + '/wapapi/config/getCountryCode',
  get_getMpBaseInfo: publicUrl + '/wapapi/addons/miniprogram/miniprogram/getMpBaseInfo',

  //商品
  get_goodsList: publicUrl + '/wapapi/goods/goodsList',
  get_categoryInfo: publicUrl + '/wapapi/goods/categoryInfo',
  get_myGoodsCollection: publicUrl + '/wapapi/member/myGoodsCollection',
  get_goodsDetail: publicUrl + '/wapapi/goods/goodsDetail',
  get_goodsReviewsList: publicUrl + '/wapapi/goods/goodsReviewsList',
  get_collectGoods: publicUrl + '/wapapi/goods/collectGoods',
  get_cancelCollectGoods: publicUrl + '/wapapi/goods/cancelCollectGoods',
  get_addCart: publicUrl + '/wapapi/goods/addCart',
  get_goodsShareDetail: publicUrl + '/wapapi/goods/goodsShareDetail',

  // 店铺
  get_shopSearch: publicUrl + '/wapapi/addons/shop/shop/shopSearch',
  get_shopInfo: publicUrl + '/wapapi/addons/shop/shop/shopInfo',
  get_collectShop: publicUrl + '/wapapi/addons/shop/shop/collectShop',
  get_cancelCollectShop: publicUrl + '/wapapi/addons/shop/shop/cancelCollectShop',
  get_myShopCollection: publicUrl + '/wapapi/addons/shop/shop/myShopCollection',
  get_applyForWap: publicUrl + '/wapapi/addons/shop/shop/applyForWap',
  get_getShopProtocolByWap: publicUrl + '/wapapi/addons/shop/shop/getShopProtocolByWap',
  get_getApplyStateByWap: publicUrl + '/wapapi/addons/shop/shop/getApplyStateByWap',
  get_getApplyCustomForm: publicUrl + '/wapapi/addons/shop/shop/getApplyCustomForm',
  get_shopgroup: publicUrl + '/wapapi/addons/shop/shop/shopgroup',

  // 购物车
  get_cart: publicUrl + '/wapapi/goods/cart',
  get_delete_car_goods: publicUrl + '/wapapi/goods/delete_car_goods',
  get_cartAdjustNum: publicUrl + '/wapapi/goods/cartAdjustNum',
  get_getStoreList: publicUrl + '/wapapi/goods/getStoreList',
  get_cartGetGoodsList: publicUrl + '/wapapi/goods/cartGetGoodsList',

  //优惠券
  get_goodsCouponList: publicUrl + '/wapapi/addons/coupontype/coupontype/goodsCouponList',
  get_userArchiveCoupon: publicUrl + '/wapapi/addons/coupontype/coupontype/userArchiveCoupon',
  get_couponCentre: publicUrl + '/wapapi/addons/coupontype/coupontype/couponCentre',
  get_getcouplist: publicUrl + '/wapapi/member/getcouplist',
  get_couponDetail: publicUrl + '/wapapi/addons/coupontype/coupontype/couponDetail',
  get_couponGoodsList: publicUrl + '/wapapi/addons/coupontype/coupontype/couponGoodsList',

  // 验证码
  get_getVerificationCode: publicUrl + '/wapapi/login/getVerificationCode',
  get_AssociateAccount: publicUrl + '/wapapi/login/AssociateAccount',
  get_checkVerificationCode: publicUrl + '/wapapi/login/checkVerificationCode',
  get_check_pay_password: publicUrl + '/wapapi/member/check_pay_password',

  //会员中心
  get_memberIndex: publicUrl + '/wapapi/Member/memberIndex',
  get_getMemberBaseInfo: publicUrl + '/wapapi/member/getMemberBaseInfo',
  get_saveMemberBaseInfo: publicUrl + '/wapapi/member/saveMemberBaseInfo',
  get_logout: publicUrl + '/wapapi/login/logout',

  //修改用户信息
  get_uploadImage: publicUrl + '/wapapi/upload/uploadImage',
  get_updatePassword: publicUrl + '/wapapi/member/updatePassword',
  get_updatePaymentPassword: publicUrl + '/wapapi/member/updatePaymentPassword',
  get_updateMobile: publicUrl + '/wapapi/member/updateMobile',
  get_getEmailVerificationCode: publicUrl + '/wapapi/login/getEmailVerificationCode',
  get_updateEmail: publicUrl + '/wapapi/member/updateEmail',

  //地址
  get_receiverAddressDetail: publicUrl + '/wapapi/member/receiverAddressDetail',
  get_area: publicUrl + '/wapapi/goods/area',
  get_saveReceiverAddress: publicUrl + '/wapapi/member/saveReceiverAddress',
  get_receiverAddressList: publicUrl + '/wapapi/member/receiverAddressList',
  get_setDefaultAddress: publicUrl + '/wapapi/member/setDefaultAddress',
  get_deleteAddress: publicUrl + '/wapapi/member/deleteAddress',

  //拼团
  get_getGroupMemberListForWap: publicUrl + '/wapapi/addons/groupshopping/groupshopping/getGroupMemberListForWap',
  get_groupShoppingListForWap: publicUrl + '/wapapi/addons/groupshopping/groupshopping/groupShoppingListForWap',

  //砍价
  get_myActionBargain: publicUrl + '/wapapi/addons/bargain/bargain/myActionBargain',
  get_helpBargain: publicUrl + '/wapapi/addons/bargain/bargain/helpBargain',
  get_getBargainList: publicUrl + '/wapapi/addons/bargain/bargain/getBargainList',

  //分红
  get_applyagent: publicUrl + '/wapapi/Member/applyagent',
  get_areaAgentApply: publicUrl + '/wapapi/addons/areabonus/areabonus/areaAgentApply',
  get_globalAgentApply: publicUrl + '/wapapi/addons/globalbonus/Globalbonus/globalAgentApply',
  get_teamAgentApply: publicUrl + '/wapapi/addons/teambonus/Teambonus/teamAgentApply',
  get_bonusIndex: publicUrl + '/wapapi/Member/bonusIndex',
  get_myBonus: publicUrl + '/wapapi/Member/myBonus',
  get_bonus_detail: publicUrl + '/wapapi/Member/bonus_detail',
  get_bonus_order: publicUrl + '/wapapi/Member/bonus_order',
  get_bonusSet: publicUrl + '/wapapi/member/bonusSet',

  //渠道商
  get_applayChannelForm: publicUrl + '/wapapi/addons/channel/channel/applayChannelForm',
  get_applayChannel: publicUrl + '/wapapi/addons/channel/channel/applayChannel',
  get_channelIndex: publicUrl + '/wapapi/addons/channel/channel/channelIndex',
  get_getChannelGoodsCategoryList: publicUrl + '/wapapi/addons/channel/channel/getChannelGoodsCategoryList',
  get_getChannelGradeGoods: publicUrl + '/wapapi/addons/channel/channel/getChannelGradeGoods',
  get_addChannelCart: publicUrl + '/wapapi/addons/channel/channel/addChannelCart',
  get_getChannelCartGoodsInfo: publicUrl + '/wapapi/addons/channel/channel/getChannelCartGoodsInfo',
  get_channelCartAdjustNum: publicUrl + '/wapapi/addons/channel/channel/channelCartAdjustNum',
  get_deleteChannelCart: publicUrl + '/wapapi/addons/channel/channel/deleteChannelCart',
  get_channelSettlement: publicUrl + '/wapapi/addons/channel/channel/channelSettlement',
  get_channelOrderCreate: publicUrl + '/wapapi/addons/channel/channel/orderCreate',
  get_countChannelFree: publicUrl + '/wapapi/addons/channel/channel/countChannelFree',
  get_getChannelOrderDetailList: publicUrl + '/wapapi/addons/channel/channel/getChannelOrderDetailList',
  get_channelOrderPay: publicUrl + '/wapapi/order/channelOrderPay',
  get_channelOrderClose: publicUrl + '/wapapi/addons/channel/channel/channelOrderClose',
  get_getPurchaseOrderDetail: publicUrl + '/wapapi/addons/channel/channel/getPurchaseOrderDetail',
  get_orderTakeDelivery: publicUrl + '/wapapi/order/orderTakeDelivery',
  get_MyChannelBalance: publicUrl + '/wapapi/addons/channel/channel/MyChannelBalance',
  get_cloudStorageLog: publicUrl + '/wapapi/addons/channel/channel/cloudStorageLog',
  get_cloudStorage: publicUrl + '/wapapi/addons/channel/channel/cloudStorage',
  get_cloudStorageDetail: publicUrl + '/wapapi/addons/channel/channel/cloudStorageDetail',
  get_getMyTeam: publicUrl + '/wapapi/addons/channel/channel/getMyTeam',
  get_MyChannelPerformance: publicUrl + '/wapapi/addons/channel/channel/MyChannelPerformance',

  //分销商
  get_distributorApply_show: publicUrl + '/wapapi/addons/distribution/distribution/distributorApply_show',
  get_distributorapply: publicUrl + '/wapapi/addons/distribution/distribution/distributorapply',
  get_distributionCenter: publicUrl + '/wapapi/addons/distribution/distribution/distributionCenter',
  get_customerList: publicUrl + '/wapapi/addons/distribution/distribution/customerList',
  get_myCommissiona: publicUrl + '/wapapi/addons/distribution/distribution/myCommissiona',
  get_commissionDetail: publicUrl + '/wapapi/addons/distribution/distribution/commissionDetail',
  get_distributionOrder: publicUrl + '/wapapi/addons/distribution/distribution/distributionOrder',
  get_teamList: publicUrl + '/wapapi/addons/distribution/distribution/teamList',
  get_commissionWithdraw_show: publicUrl + '/wapapi/addons/distribution/distribution/commissionWithdraw_show',
  get_commissionWithdraw: publicUrl + '/wapapi/addons/distribution/distribution/commissionWithdraw',
  get_qrcode: publicUrl + '/wapapi/member/qrcode',
  get_dataComplete: publicUrl + '/wapapi/addons/distribution/distribution/dataComplete',
  get_distributionSet: publicUrl + '/wapapi/addons/distribution/distribution/distributionSet',

  //礼品卷
  get_userGiftvoucherInfo: publicUrl + '/wapapi/addons/giftvoucher/giftvoucher/userGiftvoucherInfo',
  get_userGiftvoucher: publicUrl + '/wapapi/addons/giftvoucher/giftvoucher/userGiftvoucher',
  get_giftvoucherDetail: publicUrl + '/wapapi/addons/giftvoucher/giftvoucher/giftvoucherDetail',
  get_giftvoucherStore: publicUrl + '/wapapi/addons/giftvoucher/giftvoucher/giftvoucherStore',
  get_giftvoucherReceive: publicUrl + '/wapapi/addons/giftvoucher/giftvoucher/giftvoucherReceive',

  //订单
  get_refundDetail: publicUrl + '/wapapi/order/refundDetail',
  get_cancelOrderRefund: publicUrl + '/wapapi/order/cancelOrderRefund',
  get_orderGoodsRefundExpress: publicUrl + '/wapapi/order/orderGoodsRefundExpress',
  get_orderDetail: publicUrl + '/wapapi/order/orderDetail',
  get_buyAgain: publicUrl + '/wapapi/goods/buyAgain',
  get_orderTakeDelivery: publicUrl + '/wapapi/order/orderTakeDelivery',
  get_getGroupMemberListForWap: publicUrl + '/wapapi/addons/groupshopping/groupshopping/getGroupMemberListForWap',
  get_addOrderEvaluate: publicUrl + '/wapapi/order/addOrderEvaluate',
  get_addOrderEvaluateAgain: publicUrl + '/wapapi/order/addOrderEvaluateAgain',
  get_orderlist: publicUrl + '/wapapi/order/orderlist',
  get_deleteOrder: publicUrl + '/wapapi/order/deleteOrder',
  get_orderClose: publicUrl + '/wapapi/order/orderClose',
  get_orderShippingInfo: publicUrl + '/wapapi/order/orderShippingInfo',
  get_refundAsk: publicUrl + '/wapapi/order/refundAsk',
  get_orderInfo: publicUrl + '/wapapi/goods/orderInfo',
  get_orderCreate: publicUrl + '/wapapi/Order/orderCreate',
  get_orderPay: publicUrl + '/wapapi/order/orderPay',
  get_StoreOrderCreate: publicUrl + '/wapapi/order/StoreOrderCreate',

  //支付
  get_getPayValue: publicUrl + '/wapapi/member/getPayValue',
  get_wchatPay: publicUrl + '/wapapi/Member/wchatPay',
  get_balance_pay: publicUrl + '/wapapi/member/balance_pay',
  get_get_pay_result_info: publicUrl + '/wapapi/order/get_pay_result_info',
  get_pay_last_money: publicUrl + '/wapapi/pay/pay_last_money',
  get_globePay: publicUrl + '/wapapi/Member/GlobePay',

  //账户
  get_bank_account: publicUrl + '/wapapi/member/bank_account',
  get_del_account: publicUrl + '/wapapi/member/del_account',
  get_balance: publicUrl + '/wapapi/member/balance',
  get_balancewater: publicUrl + '/wapapi/member/balancewater',
  get_asset: publicUrl + '/wapapi/member/asset',
  get_integralWater: publicUrl + '/wapapi/member/integralWater',
  get_update_account: publicUrl + '/wapapi/member/update_account',
  get_add_bank_account: publicUrl + '/wapapi/member/add_bank_account',
  get_recharge: publicUrl + '/wapapi/member/recharge',
  get_createRechargeOrder: publicUrl + '/wapapi/member/createRechargeOrder',
  get_withdraw_form: publicUrl + '/wapapi/member/withdraw_form',
  get_withdraw: publicUrl + '/wapapi/member/withdraw',

  //秒杀
  get_getAllSecTime: publicUrl + '/wapapi/addons/seckill/seckill/getAllSecTime',
  get_getSeckillGoodsList: publicUrl + '/wapapi/addons/seckill/seckill/getSeckillGoodsList',

  //扫码成为下线
  get_checkReferee: publicUrl + '/wapapi/member/checkReferee',
  //手机号码是否存在该平台
  get_mobile: publicUrl + '/wapapi/login/mobile',
  //获取小程序二维码
  get_getLimitMpCode: publicUrl + '/wapapi/addons/miniprogram/miniprogram/getLimitMpCode',
  get_getUnLimitMpCode: publicUrl + '/wapapi/addons/miniprogram/miniprogram/getUnLimitMpCode',

  //根据店铺id获取门店列表
  get_getStoreListForWap: publicUrl + '/wapapi/addons/store/store/getStoreListForWap',
  //获取平台下所有门店列表
  get_getAllStoreListForWap: publicUrl + '/wapapi/addons/store/store/getAllStoreListForWap',
  get_storeIndex: publicUrl + '/wapapi/addons/store/store/storeIndex',
  get_getStoreGoodsCategoryList: publicUrl + '/wapapi/addons/store/store/getStoreGoodsCategoryList',
  get_getStoreGoods: publicUrl + '/wapapi/addons/store/store/getStoreGoods',
  get_storeCart: publicUrl + '/wapapi/addons/store/store/cart',
  get_editCartNum: publicUrl + '/wapapi/addons/store/store/editCartNum',
  get_deleteCartGoods: publicUrl + '/wapapi/addons/store/store/deleteCartGoods',
  get_storeAddCart: publicUrl + '/wapapi/addons/store/store/addCart',

  //获取商城相关超级海报
  get_getKindPoster: publicUrl + '/wapapi/addons/poster/poster/getKindPoster',

  //消费卡
  get_consumerCard: publicUrl + '/wapapi/member/consumerCard',
  get_consumerCardDetail: publicUrl + '/wapapi/member/consumerCardDetail',
  get_getwxCard: publicUrl + '/wapapi/member/getwxCard',

  //大转盘
  get_userFrequency: publicUrl + '/wapapi/addons/wheelsurf/wheelsurf/userFrequency',
  get_wheelsurfInfo: publicUrl + '/wapapi/addons/wheelsurf/wheelsurf/wheelsurfInfo',
  get_userWheelsurf: publicUrl + '/wapapi/addons/wheelsurf/wheelsurf/userWheelsurf',
  get_prizeRecords: publicUrl + '/wapapi/addons/wheelsurf/wheelsurf/prizeRecords',

  //我的奖品
  get_prizeList: publicUrl + '/wapapi/member/myPrize',
  get_prizeDetail: publicUrl + '/wapapi/member/prizeDetail',
  get_acceptPrize: publicUrl + '/wapapi/member/acceptPrize',

  //砸金蛋
  get_smasheggFrequency: publicUrl + '/wapapi/addons/smashegg/smashegg/userFrequency',
  get_smasheggInfo: publicUrl + '/wapapi/addons/smashegg/smashegg/smasheggInfo',
  get_smasheggRecords: publicUrl + '/wapapi/addons/smashegg/smashegg/prizeRecords',
  get_userSmashegg: publicUrl + '/wapapi/addons/smashegg/smashegg/userSmashegg',

  //刮刮乐
  get_scratchFrequency: publicUrl + '/wapapi/addons/scratchcard/scratchcard/userFrequency',
  get_scratchcardInfo: publicUrl + '/wapapi/addons/scratchcard/scratchcard/scratchcardInfo',
  get_scratchRecords: publicUrl + '/wapapi/addons/scratchcard/scratchcard/prizeRecords',
  get_userScratchcard: publicUrl + '/wapapi/addons/scratchcard/scratchcard/userScratchcard',

  //积分商城
  get_categorylist: publicUrl + '/wapapi/addons/integral/integral/integralcategorylist',
  get_integralGoodsList: publicUrl + '/wapapi/addons/integral/integral/goodsList',
  get_integralGoodsDetail: publicUrl + '/wapapi/addons/integral/integral/goodsdetail',
  get_integralOrderInfo: publicUrl + '/wapapi/addons/integral/integral/orderInfo',
  get_integralPay: publicUrl + '/wapapi/addons/integral/integral/integralPay',
  get_MemberBalancePoint: publicUrl + '/wapapi/addons/integral/integral/getMemberBalancePoint',

  //节日关怀
  get_acceptFestivalcare: publicUrl + '/wapapi/addons/festivalcare/festivalcare/acceptFestivalcare',

  //关注有礼
  get_acceptFollowgift: publicUrl + '/wapapi/addons/followgift/followgift/acceptFollowgift',

  //微店
  get_micCentreInfo: publicUrl + '/wapapi/addons/microshop/microshop/microShopCenter',
  get_micShopInfo: publicUrl + '/wapapi/goods/orderMicroShopInfo',
  get_micGradeInfo: publicUrl + '/wapapi/addons/microshop/microshop/microShopLevelCenter',
  get_micRenew: publicUrl + '/wapapi/addons/microshop/microshop/immediateRenewal',
  get_micUpGrade: publicUrl + '/wapapi/addons/microshop/microshop/upgradeLevel',
  get_micShopLog: publicUrl + '/wapapi/addons/microshop/microshop/profitDetail',
  get_micShopDetail: publicUrl + '/wapapi/addons/microshop/microshop/myProfit',
  get_micWithdrawsInfo: publicUrl + '/wapapi/addons/microshop/microshop/profitShow',
  get_micApplyWithdraw: publicUrl + '/wapapi/addons/microshop/microshop/profitWithdraw',
  get_micShopSet: publicUrl + '/wapapi/addons/microshop/microshop/microShopSet',
  get_micSelectGoods: publicUrl + '/wapapi/addons/microshop/microshop/selectGoods',
  get_micDelGoods: publicUrl + '/wapapi/addons/microshop/microshop/delGoods',
  get_micPreviewShop: publicUrl + '/wapapi/addons/microshop/microshop/previewMicroShop',
  get_micPreviewShopGoods: publicUrl + '/wapapi/addons/microshop/microshop/previewMicroShopGoods',
  get_micPreviewShopCategory: publicUrl + '/wapapi/addons/microshop/microshop/previewMicroShopCategory',

  //券包
  get_voucherpackageDetail: publicUrl + '/wapapi/addons/voucherpackage/voucherpackage/voucherPackage',
  get_voucherpackage: publicUrl +'/wapapi/addons/voucherpackage/voucherpackage/userArchiveVoucherPackage',
  get_voucherpackage: publicUrl +'/wapapi/addons/voucherpackage/voucherpackage/userArchiveVoucherPackage',

  //签到
  get_signinInfo: publicUrl + '/wapapi/addons/signin/signin/userSignInInfo',
  get_signinList: publicUrl + '/wapapi/addons/signin/signin/userSignInList',
  get_signinLog: publicUrl + '/wapapi/addons/signin/signin/userSignInRecord',
  set_signin: publicUrl + '/wapapi/addons/signin/signin/userSignIn',

  // 任务中心
  get_getTaskList: publicUrl + '/wapapi/addons/taskcenter/taskcenter/getTaskList',
  get_getTaskDetail: publicUrl + '/wapapi/addons/taskcenter/taskcenter/getTaskDetail',
  get_getMyTaskList: publicUrl + '/wapapi/addons/taskcenter/taskcenter/getMyTaskList',
  get_getMyTask: publicUrl + '/wapapi/addons/taskcenter/taskcenter/getMyTask',

  // 授权证书
  get_getUserWchat: publicUrl + '/wapapi/addons/credential/credential/getUserWchat',
  get_getUserCredential: publicUrl + '/wapapi/addons/credential/credential/getUserCredential',
  get_searchUserCredential: publicUrl + '/wapapi/addons/credential/credential/searchUserCredential',
  get_searchUserCredentialPage: publicUrl + '/wapapi/addons/credential/credential/searchUserCredentialPage',

  // 防伪溯源
  get_isLogin: publicUrl + '/wapapi/addons/anticounterfeiting/anticounterfeiting/isLogin',
  get_searchAnticounterfeiting: publicUrl + '/wapapi/addons/anticounterfeiting/anticounterfeiting/searchAnticounterfeiting',

  //货到付款
  get_dPay: publicUrl + '/wapapi/Member/dPay',
  //小程序支付设置
  get_mipConfig: publicUrl + '/wapapi/config/mipConfig',
  //会员客服消息列表
  get_chatList: publicUrl + '/wapapi/addons/qlkefu/qlkefu/chatList',

  //获取客服信息
  get_qlkefuInfo: publicUrl + '/wapapi/addons/qlkefu/qlkefu/qlkefuInfo',

  //获取银行卡列表
  get_bank_list: publicUrl + '/wapapi/member/bank_list',

  //好物圈
  get_getThingcircleList: publicUrl + '/wapapi/addons/thingcircle/thingcircle/getThingcircleList',
  get_likesThingcircle: publicUrl + '/wapapi/addons/thingcircle/thingcircle/likesThingcircle',
  get_thingcircleDetail: publicUrl + '/wapapi/addons/thingcircle/thingcircle/getThingcircleDetail',
  get_thingcircleShareInfo: publicUrl + '/wapapi/addons/thingcircle/thingcircle/getShareInfo',
  get_thingcircleAttention: publicUrl + '/wapapi/addons/thingcircle/thingcircle/attentionThingcircle',
  get_thingcircleLikes: publicUrl + '/wapapi/addons/thingcircle/thingcircle/likesThingcircle',
  get_thingcircleCollection: publicUrl + '/wapapi/addons/thingcircle/thingcircle/collectionThingcircle',
  get_thingcircleDetailComment: publicUrl + '/wapapi/addons/thingcircle/thingcircle/pushThingcircleComment',
  get_thingcircleDetailReplyComment: publicUrl + '/wapapi/addons/thingcircle/thingcircle/replyThingcircleComment',
  get_thingcircleDetailCommentList: publicUrl + '/wapapi/addons/thingcircle/thingcircle/getComment',
  get_thingcircleDetailCommentLikes: publicUrl + '/wapapi/addons/thingcircle/thingcircle/likesThingcircleComment',
  get_thingcircleDetailCommentMore: publicUrl + '/wapapi/addons/thingcircle/thingcircle/getThingcircleReply',
  get_thingcircleDetailCommentDel: publicUrl + '/wapapi/addons/thingcircle/thingcircle/delComment',
  get_thingcircleUserThingList: publicUrl + '/wapapi/addons/thingcircle/thingcircle/getUserThingList',//用户干货列表
  get_thingcircleVideoDetail: publicUrl + '/wapapi/addons/thingcircle/thingcircle/getThingcircleVideoDetail',//干货视频列表
  get_thingcircleViolationList: publicUrl + '/wapapi/addons/thingcircle/thingcircle/getViolationList',//
  get_thingcircleAddViolation: publicUrl + '/wapapi/addons/thingcircle/thingcircle/addViolation',//
  get_thingcircleAdd: publicUrl + '/wapapi/addons/thingcircle/thingcircle/addThingcircleWap',//
  get_thingcircleMessageInfo: publicUrl + '/wapapi/addons/thingcircle/thingcircle/getThingcircleMessageCenter',//
  get_thingcircleMessageNotice: publicUrl + '/wapapi/addons/thingcircle/thingcircle/getThingcircleMessage',//
  get_thingcircleMessageLac: publicUrl + '/wapapi/addons/thingcircle/thingcircle/getThingcircleLac',//
  get_thingcircleMessageComment: publicUrl + '/wapapi/addons/thingcircle/thingcircle/getThingcircleComment',//
  get_thingcircleArea: publicUrl + '/wapapi/addons/thingcircle/thingcircle/getarea',//

  //订阅消息模板ID
  get_getMpTemplateId: publicUrl + '/wapapi/addons/miniprogram/miniprogram/getMpTemplateId',
  get_postUserMpTemplateInfo: publicUrl + '/wapapi/addons/miniprogram/miniprogram/postUserMpTemplateInfo',

  //物流公司列表
  get_getvExpressCompany: publicUrl + '/wapapi/order/getvExpressCompany',

  //余额详情
  get_balanceDetail: publicUrl + '/wapapi/member/balanceDetail',

  get_ranking: publicUrl + '/wapapi/addons/distribution/distribution/ranking',

  //直播
  get_getWapLiveList: publicUrl + '/wapapi/addons/liveshopping/liveshopping/getWapLiveList',
  get_applyAnchor: publicUrl + '/wapapi/addons/liveshopping/liveshopping/applyAnchor',
  get_actApplyAnchor: publicUrl + '/wapapi/addons/liveshopping/liveshopping/actApplyAnchor',
  get_getBeAnchorCheckStatus: publicUrl + '/wapapi/addons/liveshopping/liveshopping/getBeAnchorCheckStatus',
  get_getAnchorUserInfo: publicUrl + '/wapapi/addons/liveshopping/liveshopping/getAnchorUserInfo', 
  get_getMyFans: publicUrl + '/wapapi/addons/liveshopping/liveshopping/getMyFans',
  get_getMyFocus: publicUrl + '/wapapi/addons/liveshopping/liveshopping/getMyFocus',
  get_addFocus: publicUrl + '/wapapi/addons/liveshopping/liveshopping/addFocus',
  get_cancleFocus: publicUrl + '/wapapi/addons/liveshopping/liveshopping/cancleFocus', 
  get_getWatchHistory: publicUrl + '/wapapi/addons/liveshopping/liveshopping/getWatchHistory',
  get_getLiveCateList: publicUrl + '/wapapi/addons/liveshopping/liveshopping/getLiveCateList',
  get_getPlayData: publicUrl + '/wapapi/addons/liveshopping/liveshopping/getPlayData',
  get_applyPlay: publicUrl + '/wapapi/addons/liveshopping/liveshopping/applyPlay',
  get_getAdvanceLiveData: publicUrl + '/wapapi/addons/liveshopping/liveshopping/getAdvanceLiveData',
  get_getAnchorGoodsList: publicUrl + '/wapapi/addons/liveshopping/liveshopping/getAnchorGoodsList',
  get_getLiveUrl: publicUrl + '/wapapi/addons/liveshopping/liveshopping/getLiveUrl',
  get_getAnchorInfo: publicUrl + '/wapapi/addons/liveshopping/liveshopping/getAnchorInfo',
  get_getUserSign: publicUrl + '/wapapi/addons/liveshopping/liveshopping/getUserSign',
  get_getGoodsCate: publicUrl + '/wapapi/addons/liveshopping/liveshopping/getGoodsCate',
  get_pickGoods: publicUrl + '/wapapi/addons/liveshopping/liveshopping/pickGoods',
  get_actPickGoods: publicUrl + '/wapapi/addons/liveshopping/liveshopping/actPickGoods',
  get_canclePickGoods: publicUrl + '/wapapi/addons/liveshopping/liveshopping/canclePickGoods',
  get_getAnchorLiveGoodsList: publicUrl + '/wapapi/addons/liveshopping/liveshopping/getAnchorLiveGoodsList',
  get_getAnchorGoodsForAdd: publicUrl + '/wapapi/addons/liveshopping/liveshopping/getAnchorGoodsForAdd',
  get_actAnchorAddGoods: publicUrl + '/wapapi/addons/liveshopping/liveshopping/actAnchorAddGoods',
  get_recommendLiveGoods: publicUrl + '/wapapi/addons/liveshopping/liveshopping/recommendLiveGoods',
  get_saveImGroupId: publicUrl + '/wapapi/addons/liveshopping/liveshopping/saveImGroupId',
  get_addFocus: publicUrl + '/wapapi/addons/liveshopping/liveshopping/addFocus',
  get_cancleFocus: publicUrl + '/wapapi/addons/liveshopping/liveshopping/cancleFocus',
  get_actEndLive: publicUrl + '/wapapi/addons/liveshopping/liveshopping/actEndLive',
  get_addLiveRemind: publicUrl + '/wapapi/addons/liveshopping/liveshopping/addLiveRemind',
  get_cancleLiveRemind: publicUrl + '/wapapi/addons/liveshopping/liveshopping/cancleLiveRemind',
  get_getViolationType: publicUrl + '/wapapi/addons/liveshopping/liveshopping/getViolationType',
  get_addLiveReport: publicUrl + '/wapapi/addons/liveshopping/liveshopping/addLiveReport',
  get_addLikes: publicUrl + '/wapapi/addons/liveshopping/liveshopping/addLikes',
  get_saveDisconnectTime: publicUrl + '/wapapi/addons/liveshopping/liveshopping/saveDisconnectTime',
  get_addWatchHistory: publicUrl + '/wapapi/addons/liveshopping/liveshopping/addWatchHistory',
  get_getPlatformLiveStatus: publicUrl + '/wapapi/addons/liveshopping/liveshopping/getPlatformLiveStatus',
  get_liveCountOnlinePeople: publicUrl + '/wapapi/addons/liveshopping/liveshopping/liveCountOnlinePeople',

  //小程序自带直播的直播广场
  get_getWapMpLiveList: publicUrl + '/wapapi/addons/mplive/mplive/getWapMpLiveList',
  get_updateMplive: publicUrl + '/wapapi/addons/mplive/mplive/updateMplive',

  get_ThingcircleUserInfo:publicUrl + '/wapapi/addons/thingcircle/thingcircle/getThingcircleUser',
  get_attentionUserList:publicUrl + '/wapapi/addons/thingcircle/thingcircle/attentionUserList', 
  get_attentionThingcircle:publicUrl + '/wapapi/addons/thingcircle/thingcircle/attentionThingcircle',
  get_fansUserList:publicUrl + '/wapapi/addons/thingcircle/thingcircle/fansUserList',
  get_recommendGoods:publicUrl + '/wapapi/addons/thingcircle/thingcircle/getRecommendGoods',
  get_topicList:publicUrl + '/wapapi/addons/thingcircle/thingcircle/getTopicList',
  get_lowerTopicList:publicUrl + '/wapapi/addons/thingcircle/thingcircle/getLowerTopicList',
  
  //知识付费
  get_courseDetail:publicUrl + '/wapapi/goods/seeKnowledgePayment',
  get_courseDetailList:publicUrl + '/wapapi/goods/wapGetKnowledgePaymentList',
  get_myCourse:publicUrl + '/wapapi/goods/myCourse',
}

module.exports = {
  open_api: open_api
}