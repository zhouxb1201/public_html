<template>
  <div class="microshop-list bg-f8">
    <Navbar :title="navBarTitle" />
    <TabSortScreen :set-params="setParams" />
    <List
      v-model="loading"
      :finished="finished"
      :error.sync="error"
      :is-empty="isListEmpty"
      :empty="{
        pageType: 'microshopchoose', 
        message: '暂无商品', 
        showFoot: true,
        btnLink: '/', 
        btnText: '返回首页',
      }"
      @load="loadList"
    >
      <div class="list">
        <GoodsTab
          v-for="(item,index) in list"
          :key="index"
          :id="item.goods_id"
          :name="item.goods_name"
          :price="item.price"
          :sales="item.sales"
          :market-price="item.market_price"
          :image="item.logo"
          :selectedgoods="item.mic_selectedgoods"
        />
      </div>
    </List>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import GoodsTab from "../component/GoodsTab";
import TabSortScreen from "../component/TabSortScreen";
import { GET_GOODSLIST } from "@/api/microshop";
import { list } from "@/mixins";
export default sfc({
  name: "microshop-chooselist",
  data() {
    return {
      params: {
        order: "",
        sort: "",
        min_price: "",
        max_price: "",
        free_shipping_fee: "",
        new_goods: "",
        goods_type: "",
        search_text: this.$route.query.search_text
          ? this.$route.query.search_text
          : "",
        category_id: this.$route.query.category_id
          ? this.$route.query.category_id
          : "",
        shop_id: this.$route.query.shop_id ? this.$route.query.shop_id : "",
        microshop_type: 1
      }
    };
  },
  mixins: [list],
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
  watch: {
    "$route.query": function({ category_id, search_text }) {
      if (this.$route.name === "microshop-chooselist") {
        if (category_id == undefined && search_text == undefined) {
          this.params.category_id = "";
          this.params.search_text = "";
          this.loadList("init");
        } else if (category_id) {
          this.params.category_id = category_id;
          this.params.search_text = "";
          this.loadList("init");
        } else if (search_text) {
          this.params.category_id = "";
          this.params.search_text = search_text;
          this.loadList("init");
        }
      }
    }
  },
  mounted() {
    this.loadList();
  },
  methods: {
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
    GoodsTab
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
