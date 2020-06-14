export default [
  {
    path: '/course/list',
    name: 'course-list',
    meta: {
      title: '我的课程',
      loginRequire: true,
      noKeepAlive: true
    },
    component: () => import(/* webpackChunkName: 'course' */ '@/pages/course/List')
  },
  {
    path: '/course/detail/:id/:cid?',
    name: 'course-detail',
    meta: {
      title: '课程详情',
      loginRequire: true,
      noKeepAlive: true
    },
    component: () => import(/* webpackChunkName: 'course' */ '@/pages/course/Detail')
  }
]