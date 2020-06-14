export default [
  {
    path: '/verified',
    name: 'verified',
    meta: {
      title: '扫码',
      noKeepAlive: true,
      loginRequire: true
    },
    component: resolve => require(['@/pages/verified/Index'], resolve)
  },
  {
    path: '/verified/cardvoucher',
    name: 'verified-cardvoucher',
    meta: {
      title: '卡券核销',
      noKeepAlive: true,
      loginRequire: true
    },
    component: resolve => require(['@/pages/verified/Cardvoucher'], resolve)
  },
  {
    path: '/verified/gift',
    name: 'verified-gift',
    meta: {
      title: '礼品核销',
      noKeepAlive: true,
      loginRequire: true
    },
    component: resolve => require(['@/pages/verified/Gift'], resolve)
  },
  {
    path: '/verified/order',
    name: 'verified-order',
    meta: {
      title: '自提订单',
      noKeepAlive: true,
      loginRequire: true
    },
    component: resolve => require(['@/pages/verified/Order'], resolve)
  }
]