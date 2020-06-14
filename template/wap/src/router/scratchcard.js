export default [
  {
    path: '/scratchcard/centre/:scratchcardid',
    name: 'scratchcard-centre',
    meta: {
      title: '',
      noKeepAlive: true,
      loginRequire: true,
      shareType: 'current'
    },
    component: () => import(/* webpackChunkName: 'scratchcard' */ '@/pages/scratchcard/Centre')
  }
]
