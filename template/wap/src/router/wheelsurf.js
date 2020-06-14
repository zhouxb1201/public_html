export default [
  {
    path: '/wheelsurf/centre/:wheelsurfid',
    name: 'wheelsurf-centre',
    meta: {
      title: '',
      noKeepAlive: true,
      loginRequire: true,
      shareType: 'current'
    },
    component: () => import(/* webpackChunkName: 'wheelsurf' */ '@/pages/wheelsurf/Centre')
  }
]
