export default [
  {
    path: '/statistic',
    name: 'statistic',
    meta: {
      title: '销售统计',
      noKeepAlive: true,
      loginRequire: true
    },
    component: resolve => require(['@/pages/statistic/Statistic'], resolve)
  }
]