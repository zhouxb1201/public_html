export default [
  {
    path: '/',
    name: 'centre',
    meta: {
      title: '操作台',
      noKeepAlive: true,
      loginRequire: true
    },
    component: resolve => require(['@/pages/centre/Index'], resolve)
  },
  {
    path: '/store',
    name: 'store',
    meta: {
      title: '选择门店',
      noKeepAlive: true,
      loginRequire: true
    },
    component: resolve => require(['@/pages/centre/Store'], resolve)
  },
  {
    path: '*',
    name: '404',
    meta: {
      title: '未找到相关页面'
    },
    component: resolve => require(['@/components/404'], resolve)
  },
  {
    path: '/unopened',
    name: 'unopened',
    meta: {
      title: '应用未开启'
    },
    component: resolve => require(['@/components/Unopened'], resolve)
  },
  {
    path: '/search',
    name: 'search',
    meta: {
      title: '搜索',
      noKeepAlive: true
    },
    component: resolve => require(['@/pages/search/Index'], resolve)
  }
]