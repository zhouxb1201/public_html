import http from '@/utils/request'

// 砸金蛋详情
export function GET_SMASHEGGINFO(smash_egg_id) {
	return http({
		url: '/addons/smashegg/smashegg/smasheggInfo',
		method: 'post',
		data: { smash_egg_id }

	})
}

// 砸金蛋次数
export function GET_FREQUENCY(smash_egg_id) {
	return http({
		url: '/addons/smashegg/smashegg/userFrequency',
		method: 'post',
		data: { smash_egg_id },
		isWriteIn: true
	})
}

// 中奖名单
export function GET_PRIZERECORDS(data) {
	return http({
		url: '/addons/smashegg/smashegg/prizeRecords',
		method: 'post',
		data
	})

}

//开始砸蛋
export function SET_USERSMASHEGG(smash_egg_id) {
	return http({
		url: '/addons/smashegg/smashegg/userSmashegg',
		method: 'post',
		data: { smash_egg_id },
		isWriteIn: true
	})
}  