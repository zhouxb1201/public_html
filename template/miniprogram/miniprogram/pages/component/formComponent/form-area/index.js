var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var header = getApp().header;
Component({
  /**
   * 组件的属性列表
   */
  properties: {
    index: String,
    customitem: Object,
    customform: Object,
  },

  /**
   * 组件的初始数据
   */
  data: {
    popupShow:false,
    areaText:'请选择地址'
  },

  lifetimes: {
    attached() {
      // 在组件实例进入页面节点树时执行
    },
    ready() {
      const that = this;
      let customform = that.data.customform;
      if (customform[that.data.index].value != ''){
        let area = customform[that.data.index].value.split(',');
        let areaText = area[0];
        that.setData({
          areaText: areaText
        })
      }
      
    },
    detached() {
      // 在组件实例被从页面节点树移除时执行
    },
  },

  /**
   * 组件的方法列表
   */
  methods: {
    //弹出层开启
    areaOnShow: function (e) {
      let index = e.currentTarget.dataset.index;
      this.setData({
        popupShow: true,
        customIndex: index,
      })
      this.getAreaList();
    },

    //弹出层关闭
    areaOnClose: function () {
      this.setData({
        popupShow: false
      })
    },
    //获取省市区
    getAreaList: function () {
      const that = this;
      var postData = {};
      let header = getApp().header;
      let datainfo = requestSign.requestSign(postData);
      header.sign = datainfo;
      wx.request({
        url: api.get_area,
        data: postData,
        header: header,
        method: 'POST',
        dataType: 'json',
        responseType: 'text',
        success: (res) => {
          let areaList = {
            province_list: res.data.data.province_list,
            city_list: res.data.data.city_list,
            county_list: res.data.data.county_list,
          }
          let areaIdList = {
            province_id_list: res.data.data.province_id_list,
            city_id_list: res.data.data.city_id_list,
            county_id_list: res.data.data.county_id_list,
          }
          that.setData({
            areaList: areaList,
            areaIdList: areaIdList
          })
        },
        fail: (res) => { },
      })
    },
    //获取选择的省市区
    changeArea: function (event) {
      const that = this;
      console.log(event.detail.values);
      let areaArray = event.detail.values;
      let area = [];
      let provinceCode = '';
      let cityCode = '';
      let countyCode = '';
      let provinceId = '';
      let cityId = '';
      let countyId = '';
      for (var i = 0; i < areaArray.length; i++) {
        area.push(areaArray[i].name);
        provinceCode = areaArray[0].code;
        cityCode = areaArray[1].code;
        countyCode = areaArray[2].code;
      }
      let areaIdList = that.data.areaIdList;
      for (var key in areaIdList.province_id_list) {
        if (key == provinceCode) {
          provinceId = areaIdList.province_id_list[key];
        }
      }
      for (var key in areaIdList.city_id_list) {
        if (key == cityCode) {
          cityId = areaIdList.city_id_list[key];
        }
      }
      for (var key in areaIdList.county_id_list) {
        if (key == countyCode) {
          countyId = areaIdList.county_id_list[key];
        }
      }
      let areaText = area.join("/");
      that.setData({
        areaText: areaText,
        provinceid: provinceId,
        cityid: cityId,
        districtid: countyId
      })
      let value_array = [];
      let area_id_array = [];
      area_id_array.push(provinceId);
      area_id_array.push(cityId);
      area_id_array.push(countyId);
      let area_id_string = area_id_array.join('/');
      value_array.push(areaText);
      value_array.push(area_id_string);
      value_array.push(countyCode);
      let value_string = value_array.join(',');
      that.data.customform[that.data.customIndex].value = value_string;
      that.triggerEvent('customformInfo', { customform: that.data.customform })
      that.areaOnClose();
    },
  }
})
