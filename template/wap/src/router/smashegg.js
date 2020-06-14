export default [
  {
    path: '/smashegg/centre/:smasheggid',
    name: 'smashegg-centre',
    meta: {
      title: '',
      noKeepAlive: true,
      loginRequire: true,
      shareType: 'current'
    },
    component: () => import(/* webpackChunkName: 'smashegg' */ '@/pages/smashegg/Centre')
  }
]
