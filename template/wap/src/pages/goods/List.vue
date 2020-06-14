<template>
  <div class="goods-list bg-f8">
    <Navbar :title="navBarTitle" />
    <TabSortScreen :set-params="setParams" />
    <List
      v-model="loading"
      :finished="finished"
      :error.sync="error"
      :is-empty="isListEmpty"
      :empty="{
        pageType: 'goods',
        message: '暂无商品',
        showFoot: true,
        top: $store.state.isWeixin ? 46 : 90,
        btnLink: '/',
        btnText: '返回首页'
      }"
      @load="loadList"
    >
      <div class="list">
        <GoodsBox
          v-for="(item, index) in list"
          :key="index"
          :id="item.goods_id"
          :name="item.goods_name"
          :price="item.price"
          :sales="item.sales"
          :market-price="item.market_price"
          :image="item.logo"
        />
      </div>
    </List>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import GoodsBox from "@/components/GoodsBox";
import TabSortScreen from "./component/TabSortScreen";
import { GET_GOODSLIST } from "@/api/goods";
import { list } from "@/mixins";
export default sfc({
  name: "goods-list",
  data() {
    return {};
  },
  mixins: [list],
  beforeRouteEnter(to, from, next) {
    if (from.name === "goods-detail") {
      to.meta.isBack = true;
    }
    next();
  },
  computed: {
    navBarTitle() {
      const query = this.$route.query;
      let title = this.$route.meta.title;
      if (query.search_text) {
        title = "搜索：" + query.search_text;
      } else if (query.text) {
        title = query.text;
      }
      if (title) document.title = title;
      return title;
    }
  },
  mounted() {
    this.isFirstEnter = true;
  },
  activated() {
    if (!this.$route.meta.isBack || this.isFirstEnter) {
      this.params = Object.assign(this.params, { ...this.getParams() });
      this.loadList("init");
    }
    this.$route.meta.isBack = false;
    this.isFirstEnter = false;
  },
  methods: {
    getParams() {
      const { shop_id, search_text, category_id } = this.$route.query;
      return {
        order: "",
        sort: "",
        min_price: "",
        max_price: "",
        is_recommend: 0,
        is_new: 0,
        is_hot: 0,
        is_promotion: 0,
        is_shipping_free: 0,
        goods_type: shop_id > 0 ? 2 : shop_id == 0 ? 0 : 1,
        search_text: search_text || "",
        category_id: category_id || "",
        shop_id: shop_id || ""
      };
    },
    loadList(init) {
      const $this = this;
      if (init && init === "init") {
        $this.initList();
      }
      GET_GOODSLIST($this.params)
        .then(({ data }) => {
          let list = data.goods_list;
          $this.pushToList(list, data.page_count, init);
        })
        .catch(() => {
          $this.loadError();
        });
    },
    setParams(params) {
      this.params = params;
      this.loadList("init");
    }
  },
  components: {
    TabSortScreen,
    GoodsBox
  }
});
</script>

<style scoped>
.list {
  height: auto;
  overflow: hidden;
  background: #f8f8f8;
  padding: 4px;
  display: flex;
  flex-wrap: wrap;
}
</style>
