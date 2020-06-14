<template>
  <div class="head" :style="height">
    <div class="box">
      <van-search
        v-if="showSearch"
        :placeholder="searchPlaceholder"
        v-model="text"
        show-action
        @search="onSearch"
      >
        <div slot="action" @click="onSearch">搜索</div>
      </van-search>
      <van-tabs v-model="active" @change="onTab">
        <van-tab v-for="(item,index) in tabs" :key="index" :title="item.name"/>
      </van-tabs>
    </div>
  </div> 
</template>

<script>
import { Search } from "vant";
export default {
  data() {
    return {
      active: this.value,
      text: this.searchText
    };
  },
  props: {
    value: Number,
    tabs: Array,

    showSearch: {
      type: Boolean,
      default: false
    },
    searchText: String,
    searchPlaceholder: String
  },
  computed: {
    height() {
      return {
        height: this.showSearch ? "98px" : "44px"
      };
    }
  },
  methods: {
    onTab(index) {
      this.$emit("tab-change", index);
    },
    onSearch(e) {
      this.$emit("search", this.text);
    }
  },
  components: {
    [Search.name]: Search
  }
};
</script>

<style scoped>
.head {
  min-height: 44px;
}
.head .box {
  width: 100%;
  min-height: 44px;
  position: fixed;
  z-index: 99;
}
</style>
