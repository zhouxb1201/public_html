export default [
	{
		path: '/login',
		name: 'login',
		meta: {
			title: '登录'
		},
		component: resolve => require(['@/pages/login/Login'], resolve)
	},
	{
		path: '/forget',
		name: 'forget',
		meta: {
			title: '忘记密码'
		},
		component: resolve => require(['@/pages/login/Forget'], resolve)
	},
	{
		path: '/author',
		name: 'author',
		meta: {
			title: '授权中...',
			noKeepAlive: true
		},
		component: resolve => require(['@/pages/login/Author'], resolve)
	},
	{
		path: '/bind',
		name: 'bind',
		meta: {
			title: '关联账号'
		},
		component: resolve => require(['@/pages/login/Bind'], resolve)
	}
]