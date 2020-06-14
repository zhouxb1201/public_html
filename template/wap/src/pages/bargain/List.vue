<template>
  <div class="bargain-list bg-f8">
    <template v-if="$store.state.config.addons.bargain">
      <Navbar />
      <List
        v-model="loading"
        :finished="finished"
        :error.sync="error"
        :is-empty="isListEmpty"
        :empty="{message: '没有相关砍价商品'}"
        @load="loadList"
      >
        <CellGoodsCard :items="item" v-for="(item,index) in list" :key="index" />
      </List>
    </template>
    <Empty v-else page-type="fail" message="未开启砍价应用" :show-foot="false" />
  </div>
</template>

<script>
import sfc from "@/utils/create";
import CellGoodsCard from "./component/CellGoodsCard";
import { GET_BARGAINLIST } from "@/api/bargain";
import Empty from "@/components/Empty";
import { list } from "@/mixins";
export default sfc({
  name: "bargain-list",
  data() {
    return {};
  },
  mixins: [list],
  mounted() {
    if (this.$route.query.shop_id) {
      this.params.shop_id = this.$route.query.shop_id;
    }
    this.$store.state.config.addons.bargain && this.loadList();
  },
  methods: {
    loadList(init) {
      const $this = this;
      GET_BARGAINLIST($this.params)
        .then(({ data }) => {
          let list = data.bargain_list;
          $this.pushToList(list, data.page_count, init);
        })
        .catch(() => {
          $this.loadError();
        });
    }
  },
  components: {
    CellGoodsCard,
    Empty
  }
});
</script>
<style scoped>
</style>
