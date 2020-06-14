export default [
  {
    path: '/store/list',
    name: 'store-list',
    meta: {
      title: '门店列表',
      noKeepAlive: true,
      shareType: 'current'
    },
    component: () => import(/* webpackChunkName: 'store' */ '@/pages/store/List')
  },
  {
    path: '/store/search',
    name: 'store-search',
    meta: {
      title: '搜索',
      noKeepAlive: true
    },
    component: () => import(/* webpackChunkName: 'store' */ '@/pages/store/Search')
  },
  {
    path: '/store/home/:id',
    name: 'store-home',
    meta: {
      title: '门店首页',
      noKeepAlive: true
    },
    component: () => import(/* webpackChunkName: 'store' */ '@/pages/store/Home')
  }
]
