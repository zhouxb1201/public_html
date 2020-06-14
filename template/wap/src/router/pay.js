export default [
  {
    path: '/pay/payment',
    name: 'pay-payment',
    meta: {
      title: '收银台',
      loginRequire: true,
      noKeepAlive: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'pay' */ '@/pages/pay/Payment')
  },
  {
    path: '/pay/result',
    name: 'pay-result',
    meta: {
      title: '',
      loginRequire: true,
      noKeepAlive: true
    },
    component: () => import(/* webpackChunkName: 'pay' */ '@/pages/pay/Result')
  },
  {
    path: '/pay/guide',
    name: 'pay-guide',
    meta: {
      title: '支付提示',
      noKeepAlive: true
    },
    component: () => import(/* webpackChunkName: 'pay' */ '@/pages/pay/Guide')
  }
]
