export default [{
	path: '/goods/category',
	name: 'goods-category',
	meta: {
		title: '商品分类',
		shareType: 'current'
	},
	component: () => import(/* webpackChunkName: 'goods' */ '@/pages/goods/Category')
},
{
	path: '/goods/list',
	name: 'goods-list',
	meta: {
		title: '商品列表',
		shareType: 'current'
	},
	component: () => import(/* webpackChunkName: 'goods' */ '@/pages/goods/List')
},
{
	path: '/goods/collection',
	name: 'goods-collection',
	meta: {
		title: '商品收藏',
		loginRequire: true
	},
	component: () => import(/* webpackChunkName: 'goods' */ '@/pages/goods/Collection')
},
{
	path: '/goods/share/:goodsid',
	name: 'goods-share',
	meta: {
		title: '商品分享',
		noKeepAlive: true,
		shareType: 'diy'
	},
	component: () => import(/* webpackChunkName: 'goods' */ '@/pages/goods/Share')
},
{
	path: '/goods/detail/:goodsid',
	name: 'goods-detail',
	meta: {
		title: '',
		noKeepAlive: true,
		shareType: 'diy'
	},
	component: () => import(/* webpackChunkName: 'goods' */ '@/pages/goods/Detail')
}
]
