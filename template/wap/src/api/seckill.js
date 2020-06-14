import http from '@/utils/request'

// 秒杀商品列表标签
export function GET_SECKILLTAG(data) {
	return http({
		url: '/addons/seckill/seckill/getAllSecTime',
		method: 'post',
		data
	})
}

// 秒杀商品列表
export function GET_SECKILLLIST(data) {
	return http({
		url: '/addons/seckill/seckill/getSeckillGoodsList',
		method: 'post',
		data
	})
}

// 首页装修模板
export function GET_CUSTOMSECKILL(seckill_goods_sort) {
	return http({
		url: '/addons/seckill/seckill/getIndexSeckillList',
		method: 'post',
		data: { seckill_goods_sort }
	})
}
