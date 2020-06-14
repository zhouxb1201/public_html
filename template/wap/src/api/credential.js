import http from '@/utils/request'

//查询证书页
export function GET_SEARCHCREDENTIAL(data) {
	return http({
		url: '/addons/credential/credential/searchUserCredentialPage',
		method: 'post',
		data,
		isWriteIn: true
	})
}

//授权证书查询结果
export function GET_CREDENTIALRESULT(cred_no){
	return http({
		url: '/addons/credential/credential/searchUserCredential',
		method: 'post',
		data: { cred_no },
		isWriteIn: true
	})
}

//获取授权证书
export function GET_CREDENTIAL(data){
    return http({
        url: '/addons/credential/credential/getUserCredential',
		method: 'post',
		data,
		isWriteIn: true
    })
}

//活获取证书是否设置过微信号
export function GET_USERWECHAT(data){
	return http({
        url: '/addons/credential/credential/getUserWchat',
		method: 'post',
		data
    })
}

