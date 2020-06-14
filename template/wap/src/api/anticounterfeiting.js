import http from '@/utils/request'

//用户端是否需要登录
export function GET_ISLOGIN(data){
    return http({
        url: '/addons/anticounterfeiting/anticounterfeiting/isLogin',
        method: 'post',
        data
    })
}

//用户端查询防伪码
export function GET_SEARCHANTI(data){
    return http({
        url: '/addons/anticounterfeiting/anticounterfeiting/searchAnticounterfeiting',
        method: 'post',
        data,
        errorCallback:true
    })
}
