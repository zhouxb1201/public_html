export default [
  {
    path: '/order/list',
    name: 'order-list',
    meta: {
      title: '门店订单',
      noKeepAlive: true,
      loginRequire: true
    },
    component: resolve => require(['@/pages/order/List'], resolve)
  },
  {
    path: '/order/detail/:orderid',
    name: 'order-detail',
    meta: {
      title: '订单详情',
      noKeepAlive: true,
      loginRequire: true
    },
    component: resolve => require(['@/pages/order/Detail'], resolve)
  },
  {
    path: '/order/after',
    name: 'order-after',
    meta: {
      title: '售后订单',
      noKeepAlive: true,
      loginRequire: true
    },
    component: resolve => require(['@/pages/order/After'], resolve)
  }
]