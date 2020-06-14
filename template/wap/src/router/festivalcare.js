export default [
  {
    path: '/festivalcare/centre/:prizeid',
    name: 'festivalcare-centre',
    meta: {
      title: '节日关怀',
      noKeepAlive: true,
      loginRequire: true
    },
    component: () => import(/* webpackChunkName: 'festivalcare' */ '@/pages/festivalcare/Centre')
  }
]
