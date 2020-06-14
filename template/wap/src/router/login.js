export default [
	{
		path: '/login',
		name: 'login',
		meta: {
			title: '会员登录',
			noKeepAlive: true
		},
		component: () => import(/* webpackChunkName: 'login' */ '@/pages/login/Login')
	},
	{
		path: '/register',
		name: 'register',
		meta: {
			title: '会员注册'
		},
		component: () => import(/* webpackChunkName: 'login' */ '@/pages/login/Register')
	},
	{
		path: '/forget',
		name: 'forget',
		meta: {
			title: '忘记密码',
			noKeepAlive: true
		},
		component: () => import(/* webpackChunkName: 'login' */ '@/pages/login/Forget')
	},
	{
		path: '/author',
		name: 'author',
		meta: {
			title: '授权中...',
			noKeepAlive: true
		},
		component: () => import(/* webpackChunkName: 'login' */ '@/pages/login/Author')
	},
	{
		path: '*',
		name: '404',
		meta: {
			title: '未找到相关页面'
		},
		component: () => import(/* webpackChunkName: 'components' */ '@/components/404')
	}
]
