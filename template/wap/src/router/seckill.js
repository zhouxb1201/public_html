export default [
  {
    path: '/seckill/list',
    name: 'seckill-list',
    meta: {
      title: '秒杀列表',
      noKeepAlive: true,
      shareType: 'current'
    },
    component: () => import(/* webpackChunkName: 'seckill' */ '@/pages/seckill/List')
  }
]
