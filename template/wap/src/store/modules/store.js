import { Toast, Dialog } from "vant";
import { GET_STORECARTLIST, GET_SHOPSTORELIST } from '@/api/store';
import { setSession, getSession } from '@/utils/storage';

const store = {
  state: {
    localInfo: {
      location: getSession('location') ? JSON.parse(getSession('location')) : null,
      address: {}
    },
    localCity: '',
    localAddress: null,
    cartList: [],
    isBtnDisabled: true,
    totalPrice: 0
  },
  getters: {
    location: state => state.localInfo.location
  },
  mutations: {
    setLocation(state, location) {
      setSession('location', JSON.stringify(location.location));
      state.localInfo = location;
    },
    setLocalAddress(state, address) {
      state.localAddress = address;
    },
    setLocalCity(state, city) {
      state.localCity = city;
    },
    setStoreCartList(state, data) {
      state.cartList = data.cart_list || [];
      state.isBtnDisabled = !state.cartList.length;
    },
    setStoreCartTotalPrice(state, price) {
      state.totalPrice = price || 0;
    }
  },
  actions: {
    getLocation(context) {
      return new Promise((resolve, reject) => {
        // 原生浏览器方法获取经纬度
        function nativeGetLocation() {
          if (navigator.geolocation) {
            const loading = Toast.loading({
              duration: 0,
              forbidClick: true,
              loadingType: 'spinner',
              message: '定位中...'
            });

            let second = 5;
            const timer = setInterval(() => {
              second--;
              if (!second) {
                clearInterval(timer)
                loading.clear()
                Toast.fail('位置获取超时')
                reject('位置获取超时')
              }
            }, 1000);
            navigator.geolocation.getCurrentPosition(
              function (position) {
                const location = {
                  lat: position.coords.latitude,
                  lng: position.coords.longitude
                }
                context.commit('setLocation', location)
                clearInterval(timer)
                loading.clear()
                resolve(location);
              },
              function (error) {
                let message = ''
                switch (error.code) {
                  case error.PERMISSION_DENIED:
                    message = "用户拒绝对获取地理位置的请求。"
                    break;
                  case error.POSITION_UNAVAILABLE:
                    message = "位置信息是不可用的。"
                    break;
                  case error.TIMEOUT:
                    message = "请求用户地理位置超时。"
                    break;
                  case error.UNKNOWN_ERROR:
                    message = "未知错误。"
                    break;
                }
                clearInterval(timer)
                loading.clear()
                reject('当前位置获取失败')
              }
            );
          } else {
            Dialog.alert({ message: "你的浏览器不支持当前地理位置信息获取" });
          }
        }
        context.dispatch('wxGetLocation').then(location => {
          context.commit('setLocation', location)
          resolve(location)
        }).catch(error => {
          nativeGetLocation()
        })
      });
    },
    initBMap() {
      const AK = "t16W0CsDyfV8QjlSgS17lgsI";
      const BMap_URL =
        "https://api.map.baidu.com/api?v=2.0&ak=" +
        AK +
        "&s=1&callback=onBMapCallback";
      return new Promise((resolve, reject) => {
        // 如果已加载直接返回
        if (typeof BMap !== "undefined") {
          resolve(BMap);
          return true;
        }
        // 百度地图异步加载回调处理
        window.onBMapCallback = function () {
          resolve(BMap);
        };
        // 插入script脚本
        let scriptNode = document.createElement("script");
        scriptNode.setAttribute("type", "text/javascript");
        scriptNode.setAttribute("src", BMap_URL);
        document.body.appendChild(scriptNode);
      });
    },
    // 获取百度经纬度
    getBMapLocation({ getters, dispatch, commit }) {
      return new Promise(function (resolve, reject) {
        dispatch('initBMap').then((BMap) => {
          var geolocation = new BMap.Geolocation();
          geolocation.getCurrentPosition(function (r) {
            if (this.getStatus() == BMAP_STATUS_SUCCESS) {
              commit('setLocation', { location: r.point, address: r.address });
              commit('setLocalCity', r.address.city);
              resolve({ location: r.point, address: r.address });
            } else {
              console.log("failed" + this.getStatus());
              reject('定位失败')
            }
          });
        })
      });
    },
    /**
     * 获取定位经纬度详情地址
     * 传入经纬度 location = {lat:'',lng:''}
    */
    getLocationAddress({ getters, dispatch, commit }, location) {
      return new Promise(function (resolve, reject) {
        dispatch('initBMap').then((BMap) => {
          var geoc = new BMap.Geocoder();
          geoc.getLocation(location, function (res) {
            var info = { ...res.addressComponents, business: res.business, address: res.address };
            commit('setLocalAddress', info);
            resolve(info);
          });
        })
      });
    },
    // 获取当前城市
    getCurrentCityName({ dispatch, commit }) {
      return new Promise(function (resolve, reject) {
        dispatch('initBMap').then((BMap) => {
          let myCity = new BMap.LocalCity();
          myCity.get(function (result) {
            commit('setLocalCity', result.name)
            resolve(result.name);
          });
        })
      });
    },
    // 获取门店购物车
    getStoreCartList({ commit }, params) {
      return new Promise((resolve, reject) => {
        GET_STORECARTLIST(params).then(({ data }) => {
          data.cart_list.forEach((item) => {
            if ((item.promotion_type && item.promotion_type != 5) || !item.stock) {
              // 活动商品不可结算
              item.disabled = true;
            } else {
              item.checked = true;
            }
          })
          commit('setStoreCartList', data || {})
          resolve(data)
        }).catch(() => {
          reject()
        })
      })
    },
    // 根据店铺id或商品id获取门店列表
    getShopStoreList(context, params) {
      return new Promise((resolve, reject) => {
        GET_SHOPSTORELIST(params).then(({ data }) => {
          resolve(data)
        }).catch(() => {
          reject()
        })
      })
    }
  }
}

export default store
