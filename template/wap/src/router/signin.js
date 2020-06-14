export default [
  {
    path: '/signin',
    name: 'signin',
    alias: ['/signin/index'],
    meta: {
      title: '每日签到',
      noKeepAlive: true,
      loginRequire: true
    },
    component: () => import(/* webpackChunkName: 'signin' */ '@/pages/signin/Index')
  },
  {
    path: '/signin/log',
    name: 'signin-log',
    meta: {
      title: '签到明细',
      noKeepAlive: true,
      loginRequire: true
    },
    component: () => import(/* webpackChunkName: 'signin' */ '@/pages/signin/Log')
  }
]
