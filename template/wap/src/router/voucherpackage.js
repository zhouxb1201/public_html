export default [
  {
    path: '/voucherpackage/:id',
    name: 'voucherpackage',
    meta: {
      title: '券包详情',
      noKeepAlive: true,
      shareType: 'current'
    },
    component: () => import(/* webpackChunkName: 'voucherpackage' */ '@/pages/voucherpackage/Index')
  }
]
