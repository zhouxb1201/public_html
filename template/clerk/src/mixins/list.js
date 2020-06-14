import { isEmpty } from '@/utils/util'
const list = {
  data() {
    return {
      list: [],

      params: {
        page_index: 1,
        page_size: 20
      },

      offset: 100,
      loading: true,
      finished: false,
      error: false,
      loadingText: '加载中...',
      finishedText: '没有更多了',
      errorText: '请求失败，点击重新加载',
      immediateCheck: false
    }
  },
  computed: {
    isListEmpty() {
      return isEmpty(this.list)
    }
  },
  methods: {
    /**
     * 加载列表
     * 每个需重新写获取列表方法
     */
    loadList() {

    },
    /**
     * 初始化列表
     * 此方法仅初始化默认参数
     * 可根据不同业务初始化参数
     */
    initList() {
      this.list = [];
      this.params.page_index = 1;
      this.loading = true;
      this.finished = false;
      if (this.error) this.error = false;
    },
    /**
     * 加载更多时合并列表
     * @param {*} list 
     * @param {*} page_count 
     */
    pushToList(list, page_count, init) {
      if (init) this.list = [];   // 防止用户连续点击list未清空完成
      this.list = this.list.concat(list);
      this.loading = false;
      this.params.page_index++;
      if (this.params.page_index > (page_count ? page_count : 1)) {
        this.finished = true;
        if (this.error) this.error = false;
      }
    },
    /**
     * 请求出错时
     */
    loadError(errorText) {
      this.error = true;
      this.loading = false;
      this.finished = false;
      if (errorText) this.errorText = errorText
    }
  },
  // deactivated() {
  //   this.params.page_index = this.params.page_index - 1
  // }
}

export default list
