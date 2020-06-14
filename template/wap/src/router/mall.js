export default [
  {
    path: '/',
    name: 'index',
    alias: ['/mall/index', '/mall'],
    meta: {
      title: ''
    },
    component: () => import(/* webpackChunkName: 'mall' */ '@/pages/mall/Index')
  },
  {
    path: '/mall/cart',
    name: 'mall-cart',
    meta: {
      title: '购物车',
      noKeepAlive: true,
      loginRequire: true
    },
    component: () => import(/* webpackChunkName: 'mall' */ '@/pages/mall/Cart')
  },
  {
    path: '/preview',
    name: 'preview',
    meta: {
      title: ''
    },
    component: () => import(/* webpackChunkName: 'mall' */ '@/pages/mall/preview')
  },
  {
    path: '/search',
    name: 'search',
    meta: {
      title: '搜索页',
      noKeepAlive: true
    },
    component: () => import(/* webpackChunkName: 'mall' */ '@/pages/mall/Search')
  },
  {
    path: '/diy/:pageid',
    name: 'diy-page',
    meta: {
      title: '',
      noKeepAlive: true,
      shareType: 'current'
    },
    component: () => import(/* webpackChunkName: 'mall' */ '@/pages/mall/Diy')
  },
  {
    path: '/unopened',
    name: 'unopened',
    meta: {
      title: '商城已关闭'
    },
    component: () => import(/* webpackChunkName: 'mall' */ '@/components/Unopened')
  },
  {
    path: '/nowechat',
    name: 'nowechat',
    meta: {
      title: '该商城未对接公众号'
    },
    component: () => import(/* webpackChunkName: 'mall' */ '@/components/Nowechat')
  }
]
