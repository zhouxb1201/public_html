import http from '@/utils/request'

// 干货列表（首页）
export function GET_THINGCIRCLELIST(data) {
    return http({
		url: '/addons/thingcircle/thingcircle/getThingcircleList', 
		method: 'post',
		data: data
	})
}

// 用户干货信息（我的）
export function GET_THINGCIRCLEUSERINFO(data){
	return http({
		url: '/addons/thingcircle/thingcircle/getThingcircleUser', 
		method: 'post',
		data: data
	})
}

//用户干货列表（我的）
export function GET_THINGCIRCLEUSERLIST(data){
	return http({
		url: '/addons/thingcircle/thingcircle/getUserThingList', 
		method: 'post',
		data: data
	})
}

//干货详情
export function GET_THINGCIRCLEDETAIL(data){
	return http({
		url: '/addons/thingcircle/thingcircle/getThingcircleDetail', 
		method: 'post',
		data: data
	})
}

export function GET_THINGCIRCLEVEDIODETAIL(data){
	return http({
		url: '/addons/thingcircle/thingcircle/getThingcircleVideoDetail', 
		method: 'post',
		data: data
	})	
}


// 获取用户评论回复
export function GET_THINGCIRCLEREPLY(data){
	return http({
		url: '/addons/thingcircle/thingcircle/getThingcircleReply',
		method: 'post',
		data: data
	})
}

// 用户关注
export function GET_THINGCIRCLEFOLLOW(data){
	return http({
		url: '/addons/thingcircle/thingcircle/attentionThingcircle',
		method: 'post',
		data: data
	})
}

// 用户收藏
export function GET_THINGCIRCLECOLLECTION(data){
	return http({
		url: '/addons/thingcircle/thingcircle/collectionThingcircle',
		method: 'post',
		data: data
	})
}


// 获取用户关注列表
export function GET_ATTENTIONUSERLIST(data){
	return http({
		url: '/addons/thingcircle/thingcircle/attentionUserList',
		method: 'post',
		data: data
	})
}

// 获取用户粉丝列表
export function GET_FANSUSERLIST(data){
	return http({
		url: '/addons/thingcircle/thingcircle/fansUserList',
		method: 'post',
		data: data
	})
}

//发布干货
export function ADD_RELEASEDRY(data){
	return http({
		url: '/addons/thingcircle/thingcircle/addThingcircleWap',
		method: 'post',
		data: data
	})
}

//用户点赞干货
export function GET_THINGCIRCLELIKES(data){
	return http({
		url: '/addons/thingcircle/thingcircle/likesThingcircle',
		method: 'post',
		data: data
	})
}

//评论干货
export function ADD_THINGCIRCLECOMMENT(data){
	return http({
		url: '/addons/thingcircle/thingcircle/pushThingcircleComment',
		method: 'post',
		data: data
	})
}

//干货评论列表
export function GET_COMMENTLIST(data){
	return http({
		url: '/addons/thingcircle/thingcircle/getComment',
		method: 'post',
		data: data
	})
}

//获取举报类型
export function GET_VIOLATIONLIST(data){
	return http({
		url: '/addons/thingcircle/thingcircle/getViolationList',
		method: 'post',
		data: data
	})
}

//用户举报
export function ADD_VIOLATION(data){
	return http({
		url: '/addons/thingcircle/thingcircle/addViolation',
		method: 'post',
		data: data
	})
}

//用户回复
export function REPLY_THINGCIRCLECOMMENT(data){
	return http({
		url: '/addons/thingcircle/thingcircle/replyThingcircleComment',
		method: 'post',
		data: data
	})
}

//删除用户评论
export function DEL_THINGCIRCLECOMMENT(data){
	return http({
		url: '/addons/thingcircle/thingcircle/delComment',
		method: 'post',
		data: data
	})
}

//用户点赞评论
export function GET_THINGCIRCLELIKESCOMMENT(data){
	return http({
		url: '/addons/thingcircle/thingcircle/likesThingcircleComment',
		method: 'post',
		data: data
	})
}

//用户推荐商品列表
export function GET_RECOMMENDGOODSLIST(data){
	return http({
		url: '/addons/thingcircle/thingcircle/getRecommendGoods',
		method: 'post',
		data: data
	})
}


//话题搜索列表（一级）
export function GET_TOPICLIST(data){
	return http({
		url: '/addons/thingcircle/thingcircle/getTopicList',
		method: 'post',
		data: data
	})
}

//话题列表（二级）
export function GET_NEXTTOPICLIST(data){
	return http({
		url: '/addons/thingcircle/thingcircle/getLowerTopicList',
		method: 'post',
		data: data
	})
}

//消息中心
export function GET_THINGCIRCLEMESSAGECENTER(data){
	return http({
		url: '/addons/thingcircle/thingcircle/getThingcircleMessageCenter',
		method: 'post',
		data: data
	})
}

//消息通知
export function GET_THINGCIRCLEMESSAGE(data){
	return http({
		url: '/addons/thingcircle/thingcircle/getThingcircleMessage',
		method: 'post',
		data: data
	})
}

//消息点赞和收藏
export function GET_THINGCIRCLELAC(data){
	return http({
		url: '/addons/thingcircle/thingcircle/getThingcircleLac',
		method: 'post',
		data: data
	})
}

//消息评论和@
export function GET_THINGCIRCLEAT(data){
	return http({
		url: '/addons/thingcircle/thingcircle/getThingcircleComment',
		method: 'post',
		data: data
	})
}

//分享
export function GET_SHAREINFO(data){
	return http({
		url: '/addons/thingcircle/thingcircle/getShareInfo',
		method: 'post',
		data: data
	})
}