import http from '@/utils/request'

// 获取商城配置
export function GET_CONFIG() {
	return http({
		url: '/config',
		method: 'post',
		showError: true,
		noCancel:true
	})
}

// 获取商城装修数据
export function GET_CUSTOM(data) {
	return http({
		url: '/custom',
		method: 'post',
		data,
		noCancel:true
	})
}

// 获取注册协议
export function GET_REGISTERPROTOCOL() {
	return http({
		url: '/login/registerProtocol',
		method: 'post'
	})
}

// 上传图片
export function UPLOAD_IMAGES(data) {
	return http({
		url: '/upload/uploadImage',
		method: 'post',
		data,
		isWriteIn: true
	})
}

// 获取微信配置
export function GET_WXCONFIG(url) {
	return http({
		url: '/config/share',
		method: 'post',
		data: { url }
	})
}