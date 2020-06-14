export default [
  {
    path: '/anticounterfeiting/index',
    name: 'anticounterfeiting-index',
    meta: {
      title: '防伪溯源商品查询',
      noKeepAlive: true
    },
    component: () => import(/* webpackChunkName: 'anticounterfeiting' */ '@/pages/anticounterfeiting/Index')
  },
  {
    path: '/anticounterfeiting/result',
    name: 'anticounterfeiting-result',
    meta: {
      title: '查询结果',
      noKeepAlive: true
    },
    // component: resolve => require(['@/pages/anticounterfeiting/Result'], resolve)
    component: () => import(/* webpackChunkName: 'anticounterfeiting' */ '@/pages/anticounterfeiting/Result')
  }
]