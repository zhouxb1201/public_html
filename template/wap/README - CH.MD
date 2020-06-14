# wap

> 基于vant-ui移动端商城项目

## Features
```
.
├── App.vue  							// 入口文件
├── api									// 所有请求
├── assets								// 样式、字体等静态资源
│   ├── fonts							// 字体文件
│   └── styles							// 样式文件
│       ├── app.css                     // 全局样式
│       └── iconfont.css                // icon样式
├── components							// 公共组件
├── main.js 							// 入口 加载组件 初始化等
├── pages 								// 页面
│   ├── account							// 账号相关
│   │   ├── Info.vue 					// 基本信息
│   │   ├── Relevant.vue 				// 关联账号
│   │   ├── Post.vue 					// 修改密码/手机/邮箱
│   │   └── Set.vue 					// 帐号设置
│   ├── address 						// 地址相关
│   │   ├── List.vue 					// 收货地址
│   │   └── Post.vue 					// 添加/修改 收货地址
│   ├── bonus 							// 分红相关
│   ├── commission 						// 分销相关
│   ├── goods 							// 商品相关
│   │   ├── Category.vue 				// 商品分类
│   │   ├── Collection.vue 				// 商品收藏
│   │   ├── Detail.vue 					// 商品详情
│   │   ├── List.vue 					// 商品列表
│   │   └── Share.vue 					// 商品分享
│   ├── mall 							// 商城相关
│   │   ├── About.vue 					// 关于
│   │   ├── Cart.vue 					// 购物车
│   │   ├── Index.vue 					// 首页
│   │   └── Search.vue 					// 搜索
│   ├── member 							// 会员相关
│   │   ├── Centre.vue 					// 会员中心
│   │   ├── Coupon.vue 					// 优惠券
│   │   └── Points.vue 					// 积分
│   ├── order 							// 订单相关
│   │   ├── Evaluate.vue 				// 评价
│   │   ├── Confirm.vue 				// 确认订单
│   │   ├── Detail.vue 					// 订单详情
│   │   ├── List.vue 					// 订单列表
│   │   └── Logistics.vue 				// 物流信息
│   ├── pay 							// 支付相关
│   │   ├── Payment.vue 				// 支付页
│   │   └── Result.vue 					// 结果页(支付成功/失败)
│   ├── property 						// 资产相关
│   │   ├── Index.vue 					// 资产首页
│   │   ├── Account.vue 				// 提现账户
│   │   ├── AccountPost.vue 			// 添加/编辑 账户
│   │   ├── Balance.vue 				// 余额
│   │   ├── Log.vue 					// 明细
│   │   ├── Recharge.vue 				// 充值
│   │   └── Withdraw.vue 				// 提现
│   ├── return							// 退款/退货相关
│   │   ├── Confirm.vue 				// 确认页面
│   │   ├── Info.vue 					// 退款/退货信息
│   │   └── Result.vue 					// 结果页(退款/退货：成功/失败/拒绝)
│   ├── shop 							// 店铺相关
│   │   ├── Collection.vue 				// 店铺收藏
│   │   ├── Index.vue 					// 店铺首页
│   │   └── Search.vue 					// 店铺搜索
│   └── template 						// 页面装修组件
├── router								// 路由相关
│   └── index.js 						// 路由配置
├── store								// 全局store管理
│   └── index.js 						// store配置
└── utils 								// 全局公用方法
    └── auth.js 						// 设置token
    └── base64.js 						// 封装base64方法
    └── filter.js 						// 封装全局过滤器方法
    └── request.js 						// 封装请求js
    └── storage.js 						// 封装浏览存储方式
    └── util.js 						// 公共方法
    └── validator.js 					// 封装各种验证方法
```

## Build Setup

``` bash
# install dependencies
npm install

# serve with hot reload at localhost:8080
npm run dev

# build for production with minification
npm run build

# build for production and view the bundle analyzer report
npm run build --report
```

For a detailed explanation on how things work, check out the [guide](http://vuejs-templates.github.io/webpack/) and [docs for vue-loader](http://vuejs.github.io/vue-loader).
