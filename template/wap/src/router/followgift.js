export default [
  {
    path: '/followgift/centre/:prizeid',
    name: 'followgift-centre',
    meta: {
      title: '关注有礼',
      noKeepAlive: true,
      loginRequire: true
    },
    component: () => import(/* webpackChunkName: 'followgift' */ '@/pages/followgift/Centre')
  }
]
