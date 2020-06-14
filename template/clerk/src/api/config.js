import http from '@/utils/request'

// 选择门店
export function SELECT_STORE(store_id) {
	return http({
		url: '/addons/execute/addons/store/controller/wapstore/action/selectStore',
		method: 'post',
		data: { store_id },
		isShowLoading: true
	})
}

// 获取门店列表
export function GET_STORELIST(data) {
	return http({
		url: '/addons/execute/addons/store/controller/wapstore/action/storeList',
		method: 'post',
		data
	})
}

// 修改头像
export function UPLOAD_IMAGES(data) {
	return http({
		url: '/addons/execute/addons/store/controller/wapstore/action/uploadImage',
		method: 'post',
		data,
		isWriteIn: true
	})
}

// 获取微信配置
export function GET_WXCONFIG(url) {
	return http({
		url: '/addons/execute/addons/store/controller/wapstore/action/share',
		method: 'post',
		data: { url }
	})
}


// 获取经营概况
export function GET_STORESURVEY(data) {
	return http({
		url: '/addons/execute/addons/store/controller/wapstore/action/getIndexCount',
		method: 'post',
		data
	})
}