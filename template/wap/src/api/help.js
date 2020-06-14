import http from '@/utils/request'

// 获取帮助中心列表
export function GET_HELPLIST(data) {
	return http({
		url: '/addons/helpcenter/helpcenter/getQuesCategoryList',
		method: 'post',
		data
	})
}

// 获取帮助列表分类
export function GET_HELPCATEGORY(data) {
	return http({
		url: '/addons/helpcenter/helpcenter/getQuesCategoryDetail',
		method: 'post',
		data
	})
}

// 获取文章详情
export function GET_HELPDETAIL(data) {
	return http({
		url: '/addons/helpcenter/helpcenter/getQuesDetail',
		method: 'post',
		data
	})
}