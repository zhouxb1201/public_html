export default [
  {
    path: '/prize/list',
    name: 'prize-list',
    meta: {
      title: '我的奖品',
      loginRequire: true,
      noKeepAlive: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'prize' */ '@/pages/prize/List')
  },
  {
    path: '/prize/confirm',
    name: 'prize-confirm',
    meta: {
      title: '奖品确认',
      loginRequire: true,
      noKeepAlive: true,
      mobileRequire: true
    },
    component: () => import(/* webpackChunkName: 'prize' */ '@/pages/prize/Confirm')
  },
  {
    path: '/prize/result',
    name: 'prize-result',
    meta: {
      title: '领取成功',
      loginRequire: true,
      noKeepAlive: true
    },
    component: () => import(/* webpackChunkName: 'prize' */ '@/pages/prize/Result')
  }
]
