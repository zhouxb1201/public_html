export default [
  {
    path: '/credential/index',
    name: 'credential-index',
    meta: {
      title: '查询授权证书',
      noKeepAlive: true,
      loginRequire: true
    },
    component: () => import(/* webpackChunkName: 'credential' */ '@/pages/credential/Index')
  },
  {
    path: '/credential/result',
    name: 'credential-result',
    meta: {
      title: '授权证书查询结果',
      noKeepAlive: true,
      shareType: 'current'
    },
    component: () => import(/* webpackChunkName: 'credential' */ '@/pages/credential/Result')
  }
]