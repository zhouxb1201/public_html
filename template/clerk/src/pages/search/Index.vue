<template>
  <div class="search bg-f8">
    <HeadSearch
      :disabled="false"
      showLeft
      show-action
      :placeholder="placeholder"
      @rightAction="onSearch"
    />
    <div class="search-history" v-if="historyList.length>0">
      <div class="history-head">历史搜索</div>
      <div class="history-list">
        <van-cell-group>
          <van-cell
            clickable
            v-for="(item,index) in historyList"
            :key="index"
            :value="item"
            :to="path+item"
          />
        </van-cell-group>
      </div>
      <div class="history-foot">
        <van-button round @click="removeHistory">清空历史记录</van-button>
      </div>
    </div>
    <div class="empty" v-else>暂无搜索记录</div>
  </div>
</template>

<script>
import HeadSearch from "@/components/HeadSearch";
import { setLocal, getLocal, removeLocal } from "@/utils/storage";
export default {
  name: "search",
  data() {
    return {
      placeholder: "请输入搜索关键词",
      path: "",
      historyList: [],
      type: ""
    };
  },
  created() {
    const { type, search_type, params_type } = this.$route.query;
    let path = "/goods/search?search_text=";
    if (type === "goods") {
      path = "/goods/search?search_type=" + search_type + "&search_text=";
    }
    if (params_type) {
      path =
        "/goods/search?search_type=" +
        search_type +
        "&params_type=" +
        params_type +
        "&search_text=";
    }
    this.type = type;
    this.path = path;

    if (getLocal("store_history_key_" + type)) {
      this.historyList = getLocal("store_history_key_" + type);
    }
  },
  methods: {
    onBack() {
      this.$router.back();
    },
    onSearch(value) {
      const path = this.path;
      const search_text = value.trim();
      if (!search_text) return this.$Toast("内容不能为空");

      this.saveHistory(search_text);

      this.$router.push({
        path,
        query: {
          search_text
        }
      });
    },
    insertArray(arr, val, compare, maxLen) {
      const index = arr.findIndex(compare);
      if (index === 0) {
        return;
      }
      if (index > 0) {
        arr.splice(index, 1);
      }
      arr.unshift(val);
      if (maxLen && arr.length > maxLen) {
        arr.pop();
      }
    },
    saveHistory(query) {
      let searches = getLocal("store_history_key_" + this.type)
        ? getLocal("store_history_key_" + this.type)
        : [];
      this.insertArray(
        searches,
        query,
        item => {
          return item === query;
        },
        15
      );
      setLocal("store_history_key_" + this.type, searches);
      return searches;
    },
    removeHistory() {
      const $this = this;
      $this.$Dialog
        .confirm({
          title: "提示",
          message: "确定删除所有历史记录？"
        })
        .then(() => {
          removeLocal("store_history_key_" + $this.type);
          $this.historyList = [];
        })
        .catch(() => {});
    }
  },
  components: {
    HeadSearch
  }
};
</script>

<style scoped>
.head-search {
  background: #ffffff;
  width: 100%;
  height: 46px;
  display: flex;
  align-items: center;
  border-bottom: 1px solid #f5f5f5;
}

.head-search .left-icon {
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.head-search .left-icon .van-icon {
  width: 30px;
  height: 30px;
  line-height: 30px;
  text-align: center;
  font-size: 16px;
  font-weight: 800;
  color: #666;
}

.head-search >>> .van-search {
  width: 100%;
  padding: 5px;
  margin-left: 5px;
  background: none !important;
}

.head-search >>> .van-search .van-cell {
  background: #f5f5f5;
}

.search-history {
  margin-top: 10px;
  height: calc(100% - 60px);
}

.search-history .history-head {
  padding: 20px 14px;
  font-weight: 800;
  color: #666;
}

.search-history .history-list {
  overflow-y: auto;
  max-height: calc(100% - 180px);
}

.search-history .history-list .van-cell {
  color: #666;
}

.history-foot {
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 20px;
}

.history-foot .van-button {
  color: #666;
}

.empty {
  text-align: center;
  padding: 20px;
  color: #666;
}
</style>