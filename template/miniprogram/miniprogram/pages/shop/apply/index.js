var requestSign = require('../../../utils/requestData.js');
var api = require('../../../utils/api.js').open_api;
var util = require('../../../utils/util.js');
var re = require('../../../utils/request.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    pageShow:false,
    apply_type:1,
    customform:'',
    customformShow:false,
    type_list:[
      { name: '1', value: '个人', checked: true},
      { name: '2', value: '公司', checked: false},
    ],
    shopTypeArray: '',
    shopTypeIndex:-1,

    companyTypeArray: ['私营企业', '个体户', '外企', '中外合资'],
    companyTypeIndex:-1,

    areaList: '',
    areaIdList: '',
    popupShow: false,
    //省id
    provinceid: '',
    //市id
    cityid: '',
    //区id
    districtid: '',
    publicUrl: getApp().publicUrl,
    
    exampleShow:false,
    example_img_num:'',
    //身份证照片显示
    idCardShow:false,
    //1-手持身份证照，2-身份证正照，3-身份证发照，4-营业执照
    img_type:'',
    //手持身份证照
    idCardImg:'',
    //身份证正面照显示
    idCardfrontShow:false,
    //身份证正面照
    idCardfrontImg:'',
    //身份证反面照显示
    idCardbehindShow:false,
    //身份证反面照
    idCardbehindImg:'',
    //营业执照显示
    businessCardShow:false,
    //营业执照
    businessCardImg:'',
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    const that = this;
    that.getAreaList();
    that.shopgroup();
    that.getApplyCustomForm();
  },

  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function () {

  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {

  },

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function () {

  },

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function () {

  },

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function () {

  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {

  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {

  },

  //身份类型
  typeRadioChange:function(e){
    const that = this;
    let type = e.detail.value;
    let type_list = that.data.type_list;
    for (let item of type_list){
      item.checked = false;
      if(item.name == type){
        item.checked = true
      }
    }
    that.setData({
      apply_type:type,
      type_list: type_list
    })
  },

  //获取店铺入驻的自定义表单
  getApplyCustomForm:function(){
    const that = this;
    wx.showLoading({
      title: '加载中',
    })
    var postData = {};
    let header = getApp().header;
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    wx.request({
      url: api.get_getApplyCustomForm,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {        
        if (res.data.code == 1) {
          let custom_form = res.data.data.custom_form;
          if (custom_form.length != 0){
            that.setData({
              customformShow:true,
              customform: custom_form
            })
          }
          
        } else {
          wx.showModal({
            title: '提示',
            content: res.data.message,
            showCancel: false,
          })
        }
        wx.hideLoading();
        that.setData({
          pageShow: true,
        })
      },
      fail: (res) => { },
    })
  },

  //自定义表单
  customformData: function (e) {
    console.log(e.detail);
    const that = this;
    that.setData({
      customform: e.detail.customform
    })
  },

  //获取店铺类型列表
  shopgroup:function(){
    const that = this;
    var postData = {};
    let header = getApp().header;
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    wx.request({
      url: api.get_shopgroup,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if(res.data.code == 1){
          that.setData({
            shopTypeArray: res.data.data.shop_group_list
          })
        }else{
          wx.showModal({
            title: '提示',
            content: res.data.message,
            showCancel:false,
          })
        }
      },
      fail: (res) => { },
    })
  },

  //店铺类型选择
  shopTypeChange(e) {
    this.setData({
      shopTypeIndex: e.detail.value
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
    that.areaOnClose();
  },

  //地区弹出层开启
  areaOnShow: function () {
    this.setData({
      popupShow: true
    })
  },

  //地区弹出层关闭
  areaOnClose: function () {
    this.setData({
      popupShow: false
    })
  },

  //示例图片弹出层开启
  exampleImgOnShow: function (e) {
    let img_num = e.currentTarget.dataset.imgnum;
    this.setData({
      example_img_num: img_num,
      exampleShow: true
    })
  },

  //示例图片弹出层关闭
  exampleImgOnClose: function () {
    this.setData({
      exampleShow: false
    })
  },

  //全屏预览图片
  previewImage:function(e){
    let img_src = e.currentTarget.dataset.imgsrc;
    let img_list = [];
    img_list.push(img_src);
    wx.previewImage({
      urls: img_list,
    })
  },

  //从手机获取图片
  getImagesFun:function(e){
    const that = this;
    let img_type = e.currentTarget.dataset.imgtype;
    that.data.img_type = img_type;
    wx.chooseImage({
      count: 1,
      sizeType: ['original', 'compressed'],
      sourceType: ['album', 'camera'],
      success: function(res) {
        let tempFilePaths = res.tempFilePaths  
        console.log(tempFilePaths)
        that.uploadImg(tempFilePaths)      
      },
    })
  },

  //上传图片到服务器
  uploadImg: function (tempFilePaths){
    const that = this;
    let path = tempFilePaths[0];
    wx.uploadFile({
      url: api.get_uploadImage,
      filePath: path,
      name: 'file',
      header: {
        'Content-Type': 'multipart/form-data',
        'X-Requested-With': 'XMLHttpRequest',
        'user-token': wx.getStorageSync('user_token'),
      },
      formData: {},
      success:(res)=>{        
        let image_data = res.data;
        let image_src = JSON.parse(image_data);
        console.log(image_src.data.src)
        if(that.data.img_type == 1){
          that.setData({
            idCardImg: image_src.data.src,
            idCardShow:true,
          })
        };

        if(that.data.img_type == 2){
          that.setData({
            idCardfrontImg: image_src.data.src,
            idCardfrontShow:true,
          })
        };
        if(that.data.img_type == 3){
          that.setData({
            idCardbehindImg: image_src.data.src,
            idCardbehindShow:true,
          })
        };
        if(that.data.img_type == 4){
          that.setData({
            businessCardImg: image_src.data.src,
            businessCardShow:true,
          })
        }
      }
    })
  },

  //删除图片
  deleteImg:function(e){
    const that = this;
    let img_type = e.currentTarget.dataset.imgtype;
    wx.showModal({
      title: '提示',
      content: '请确认是否删除图片',
      success(res){
        if(res.confirm){
          if (img_type == 1) {
            that.setData({
              idCardShow: false
            })
          };
          if (img_type == 2) {
            that.setData({
              idCardfrontShow: false
            })
          };
          if(img_type == 3){
            that.setData({
              idCardbehindShow:false
            })
          };
          if(img_type == 4){
            that.setData({
              businessCardShow:false
            })
          }
        }
      }
    })    
  },
  
  //联系人姓名
  contactsName:function(e){
    this.setData({
      contacts_name:e.detail.value
    })    
  },

  //联系人电话
  contactsPhone:function(e){   
    let contacts_phone = e.detail.value;     
    if (util.checkPhone(contacts_phone) == false){
      wx.showModal({
        title: '提示',
        content: '手机号码不正确',
        showCancel:false,
      })
      return
    }
    this.setData({
      contacts_phone: contacts_phone
    })
  },

  //联系人邮箱
  contactsEmail:function(e){
    this.setData({
      contacts_email: e.detail.value
    })
  },

  //公司详细地址
  companyAddressDetail:function(e){
    this.setData({
      company_address_detail: e.detail.value
    })
  },

  //申请人身份证号
  contactsCardNo:function(e){
    let idCardNo = e.detail.value  
    if (util.checkIdCardNo(idCardNo) == false){
      return
    }   
    this.setData({
      contacts_card_no: idCardNo
    })
  },

  //公司名称
  companyName:function(e){
    this.setData({
      company_name: e.detail.value
    })
  },

  //公司类型
  companyTypeChange:function(e){
    this.setData({
      companyTypeIndex: e.detail.value
    })
  },

  //公司电话
  companyPhone:function(e){
    this.setData({
      company_phone: e.detail.value
    })
  },

  //员工数量
  companyEmployeeCount:function(e){
    this.setData({
      company_employee_count: e.detail.value
    })
  },

  //注册资金（万元）
  companyRegisteredCapital:function(e){
    this.setData({
      company_registered_capital: e.detail.value
    })
  },

  //营业执照号
  businessLicenceNumber:function(e){
    this.setData({
      business_licence_number: e.detail.value
    })
  },

  //法定经营范围
  businessSphere:function(e){
    this.setData({
      business_sphere: e.detail.value
    })
  },

  //店铺名称
  shopName:function(e){
    this.setData({
      shop_name: e.detail.value
    })
  },

  //店铺入驻申请
  applyForWap:function(){
    const that = this;
    var postData = '';
    if(that.data.customformShow == false){
      //判断必填数据是否填写
      if (that.checkData() == false) {
        return
      }
      postData = that.postData()
    }else{
      //判断店铺数据是否填写
      if (that.customPostData() != false){
        postData = that.customPostData()
      }else{
        return
      }      
    }
    let header = getApp().header;
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    wx.request({
      url: api.get_applyForWap,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code > 0) {
          wx.showToast({
            title: '提交申请成功,请等待审核！',
            icon:'none'
          })
          setTimeout(function(){
            wx.navigateBack({
              delta: 1
            })
          },500);
          
        } else {
          wx.showModal({
            title: '提示',
            content: res.data.message,
            showCancel: false,
          })
        }
      },
      fail: (res) => { },
    })
  },


  //检查数据是否填写
  checkData:function(){
    const that = this;
    let check_result = true;
    //基本信息数据验证
    if (that.data.contacts_name == undefined) {
      wx.showToast({
        title: '请填写联系人名称',
        icon: 'none'
      })
      check_result = false;
    };

    if (that.data.contacts_phone == undefined) {
      wx.showToast({
        title: '请填写联系人电话',
        icon: 'none'
      })
      check_result = false;
    };

    if (that.data.contacts_email == undefined) {
      wx.showToast({
        title: '请填写联系人邮箱',
        icon: 'none'
      })
      check_result = false;
    };

    if (that.data.provinceid == '') {
      wx.showToast({
        title: '请选择联系地址',
        icon: 'none'
      })
      check_result = false;
    };

    if (that.data.company_address_detail == undefined) {
      wx.showToast({
        title: '请选择详细地址',
        icon: 'none'
      })
      check_result = false;
    };

    //检查公司数据
    if (that.data.apply_type == 2){
      if (that.data.companyTypeIndex == -1) {
        wx.showToast({
          title: '请选择公司类型',
          icon: 'none'
        })
        check_result = false;
      };

      if (that.data.company_name == undefined) {
        wx.showToast({
          title: '请填写公司名称',
          icon: 'none'
        })
        check_result = false;
      };

      if (that.data.company_phone == undefined) {
        wx.showToast({
          title: '请填写公司电话',
          icon: 'none'
        })
        check_result = false;
      };

      if (that.data.company_employee_count == undefined) {
        wx.showToast({
          title: '请填写员工数量',
          icon: 'none'
        })
        check_result = false;
      };

      if (that.data.company_registered_capital == undefined) {
        wx.showToast({
          title: '请填写注册资金（万元）',
          icon: 'none'
        })
        check_result = false;
      };

      if (that.data.business_licence_number == undefined) {
        wx.showToast({
          title: '请填写营业执照号',
          icon: 'none'
        })
        check_result = false;
      };

      if (that.data.businessCardImg == '') {
        wx.showToast({
          title: '请上传营业执照',
          icon: 'none'
        })
        check_result = false;
      };
    }

    
    // 检查身份信息数据
    if (that.data.contacts_card_no == undefined) {
      wx.showToast({
        title: '请填写申请人身份证号',
        icon: 'none'
      })
      check_result = false;
    };

    if (that.data.idCardImg == '') {
      wx.showToast({
        title: '请上传申请人手持身份证电子版',
        icon: 'none'
      })
      check_result = false;
    };

    if (that.data.idCardfrontImg == '') {
      wx.showToast({
        title: '请上传申请人身份证正面',
        icon: 'none'
      })
      check_result = false;
    };

    if (that.data.idCardbehindImg == '') {
      wx.showToast({
        title: '请上传申请人身份证反面',
        icon: 'none'
      })
      check_result = false;
    };

    if (that.data.shop_name == undefined) {
      wx.showToast({
        title: '请填写店铺名称',
        icon: 'none'
      })
      check_result = false;
    };

    if (that.data.shop_name == undefined) {
      wx.showToast({
        title: '请填写店铺名称',
        icon: 'none'
      })
      check_result = false;
    };

    if (that.data.shopTypeIndex == -1) {
      wx.showToast({
        title: '请选择店铺类型',
        icon: 'none'
      })
      check_result = false;
    };

    return check_result    
  },

  //需要提交的数据的组装
  postData:function(){
    const that = this;
    let postData = '';
    if(that.data.apply_type == 1){
      postData = {
        apply_type:1,
        contacts_name: that.data.contacts_name,
        contacts_phone: that.data.contacts_phone,
        contacts_email: that.data.contacts_email,
        company_province_id:that.data.provinceid,
        company_city_id: that.data.cityid,
        company_district_id: that.data.districtid,
        company_address_detail: that.data.company_address_detail,
        contacts_card_no: that.data.contacts_card_no,
        contacts_card_electronic_1: that.data.idCardImg,
        contacts_card_electronic_2: that.data.idCardfrontImg,
        contacts_card_electronic_3: that.data.idCardbehindImg,
        shop_name: that.data.shop_name,
        shop_group_id: that.data.shopTypeArray[that.data.shopTypeIndex].shop_group_id,
        shop_group_name: that.data.shopTypeArray[that.data.shopTypeIndex].group_name,
      }
      console.log(postData)
      return postData
    };

    if (that.data.apply_type == 2) {
      postData = {
        apply_type: 2,
        contacts_name: that.data.contacts_name,
        contacts_phone: that.data.contacts_phone,
        contacts_email: that.data.contacts_email,
        company_province_id: that.data.provinceid,
        company_city_id: that.data.cityid,
        company_district_id: that.data.districtid,
        company_address_detail: that.data.company_address_detail,
        company_name: that.data.company_name,
        company_type: that.data.companyTypeArray[that.data.companyTypeIndex],
        company_phone: that.data.company_phone,
        company_employee_count: that.data.company_employee_count,
        company_registered_capital: that.data.company_registered_capital,
        business_licence_number: that.data.business_licence_number,
        business_sphere: that.data.business_sphere,
        business_licence_number_electronic: that.data.businessCardImg,
        contacts_card_no: that.data.contacts_card_no,
        contacts_card_electronic_1: that.data.idCardImg,
        contacts_card_electronic_2: that.data.idCardfrontImg,
        contacts_card_electronic_3: that.data.idCardbehindImg,
        shop_name: that.data.shop_name,
        shop_group_id: that.data.shopTypeArray[that.data.shopTypeIndex].shop_group_id,
        shop_group_name: that.data.shopTypeArray[that.data.shopTypeIndex].group_name,
      }
      console.log(postData)
      return postData
    };
  },

  //自定义表单时需要提交的数据
  customPostData:function(){
    const that = this;
    let customform = '';
    let required = util.isRequired(that.data.customform);
    if (required) {
      customform = JSON.stringify(that.data.customform);
    } else {
      return false;
    }
    
    if (that.data.shop_name == undefined) {
      wx.showToast({
        title: '请填写店铺名称',
        icon: 'none'
      })
      return false;
    };

    if (that.data.shopTypeIndex == -1) {
      wx.showToast({
        title: '请选择店铺类型',
        icon: 'none'
      })
      return false;
    };
    let customPostData = {
      post_data: customform,
      shop_name: that.data.shop_name,
      shop_group_id: that.data.shopTypeArray[that.data.shopTypeIndex].shop_group_id,
      shop_group_name: that.data.shopTypeArray[that.data.shopTypeIndex].group_name,
    }
    return customPostData
  },  

})