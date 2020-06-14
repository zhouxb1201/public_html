import http from '@/utils/request'

// 获取发票图 
export function GET_IMG(data) {
	return http({
		url: '/addons/invoice/invoice/getInvoiceImg',
		method: 'post',
		data
	})
}

//添加至微信卡包
export function ADD_WXCARDPACKAGE(data) {
	return http({
		url: '/addons/invoice/invoice/post2WxCardPackage',
		method: 'post',
		data
	})
}

//发票插卡
export function GET_AUTHURL(data){
	return http({
		url: '/addons/invoice/invoice/getAuthUrl',
		method: 'post',
		data
	})
}