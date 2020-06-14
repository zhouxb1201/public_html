export default [
  {
    path: '/address/list',
    name: 'address-list',
    meta: {
      title: '收货地址',
      loginRequire: true
    },
    component: () => import(/* webpackChunkName: 'address' */ '@/pages/address/List')
  },
  {
    path: '/address/post',
    name: 'address-post',
    meta: {
      title: '添加收货地址',
      noKeepAlive: true,
      loginRequire: true
    },
    component: () => import(/* webpackChunkName: 'address' */ '@/pages/address/Post')
  }
]
