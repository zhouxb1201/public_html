var util = require('../../../utils/util.js');
Component({
  properties: {
    isShow: {
      type: Boolean,
      value: true
    }
  },
  data: {
    publicUrl: getApp().publicUrl
  },
  methods: {
    onKnow: function () {
      let onPageData = {
        url: '/pages/index/index',
        num: 1,
        param: '',
      }
      util.jumpPage(onPageData);
    }
  }
})