<template>
  <div :class="item.id" @click="toSearch">
    <van-search
      class="vui-search"
      v-model="value"
      :style="itemStyle"
      :placeholder="item.params.placeholder"
      readonly
      :background="item.style.background"
    />
  </div>
</template>

<script>
import { Search } from "vant";
export default {
  name: "tpl_search",
  data() {
    return {
      value: "",
      itemStyle: {
        padding:
          this.item.style.paddingtop +
          "px" +
          " " +
          this.item.style.paddingleft +
          "px"
      }
    };
  },
  props: {
    type: [String, Number],
    item: Object
  },
  methods: {
    toSearch() {
      let query = {};
      query.type = this.type == 9 ? "integralgoods" : "goods";
      if (this.type == 2 && this.$route.params.shopid) {
        query.shop_id = this.$route.params.shopid;
      }
      this.$router.push({
        path: "/search",
        query
      });
    }
  },
  components: {
    [Search.name]: Search
  }
};
</script>

<style scoped>
.vui-search >>> .van-search__content {
  background: #ffffff;
}
</style>
