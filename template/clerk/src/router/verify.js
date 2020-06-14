export default [
  {
    path: '/verify',
    name: 'verify',
    meta: {
      title: '扫码',
      noKeepAlive: true,
      loginRequire: true
    },
    component: resolve => require(['@/pages/verify/Index'], resolve)
  },
  {
    path: '/verify/cardvoucher/:code',
    name: 'verify-cardvoucher',
    meta: {
      title: '卡券核销',
      noKeepAlive: true,
      loginRequire: true
    },
    component: resolve => require(['@/pages/verify/Cardvoucher'], resolve)
  },
  {
    path: '/verify/gift/:code',
    name: 'verify-gift',
    meta: {
      title: '礼品核销',
      noKeepAlive: true,
      loginRequire: true
    },
    component: resolve => require(['@/pages/verify/Gift'], resolve)
  },
  {
    path: '/verify/order/:code',
    name: 'verify-order',
    meta: {
      title: '自提订单',
      noKeepAlive: true,
      loginRequire: true
    },
    component: resolve => require(['@/pages/verify/Order'], resolve)
  },
  {
    path: '/verify/log',
    name: 'verify-log',
    meta: {
      title: '核销记录',
      noKeepAlive: true,
      loginRequire: true
    },
    component: resolve => require(['@/pages/verify/Log'], resolve)
  }
]