export default [
  {
    path: "/help/index",
    name: "help-index",
    meta: {
      title: "帮助中心",
      noKeepAlive: true
    },
    component: () => import(/* webpackChunkName: 'help' */ "@/pages/help/Index")
  },
  {
    path: "/help/list",
    name: "help-list",
    meta: {
      title: "帮助分类列表",
      noKeepAlive: true
    },
    component: () => import(/* webpackChunkName: 'help' */ "@/pages/help/List")
  },
  {
    path: "/help/detail/:id",
    name: "help-detail",
    meta: {
      title: "",
      noKeepAlive: true
    },
    component: () =>
      import(/* webpackChunkName: 'help' */ "@/pages/help/Detail")
  }
];
