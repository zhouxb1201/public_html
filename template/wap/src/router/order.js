export default [
  {
    path: '/order/list',
    name: 'order-list',
    meta: {
      title: '订单列表',
      loginRequire: true
    },
    component: () => import(/* webpackChunkName: 'order' */ '@/pages/order/List')
  },
  {
    path: '/order/detail/:orderid',
    name: 'order-detail',
    meta: {
      title: '订单详情',
      noKeepAlive: true,
      loginRequire: true
    },
    component: () => import(/* webpackChunkName: 'order' */ '@/pages/order/Detail')
  },
  {
    path: '/order/confirm',
    name: 'order-confirm',
    meta: {
      title: '确认订单',
      loginRequire: true,
      noKeepAlive: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'order' */ '@/pages/order/Confirm')
  },
  {
    path: '/order/post',
    name: 'order-post',
    meta: {
      title: '',
      loginRequire: true,
      noKeepAlive: true
    },
    component: () => import(/* webpackChunkName: 'order' */ '@/pages/order/Post')
  },
  {
    path: '/order/logistics/:orderid',
    name: 'order-logistics',
    meta: {
      title: '物流信息',
      loginRequire: true,
      noKeepAlive: true
    },
    component: () => import(/* webpackChunkName: 'order' */ '@/pages/order/Logistics')
  },
  {
    path: '/order/evaluate/:orderid',
    name: 'order-evaluate',
    meta: {
      title: '商品评价',
      loginRequire: true,
      noKeepAlive: true
    },
    component: () => import(/* webpackChunkName: 'order' */ '@/pages/order/Evaluate')
  }
]
