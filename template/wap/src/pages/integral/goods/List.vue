<template>
  <div class="integral-goods-list bg-f8">
    <Navbar :title="navBarTitle" />
    <TabSortScreen :set-params="setParams" />
    <List
      v-model="loading"
      :finished="finished"
      :error.sync="error"
      :is-empty="isListEmpty"
      :empty="{
        pageType: 'integralgoods', 
        message: '暂无商品', 
        showFoot: true,
        btnLink: '/integral/index', 
        btnText: '返回积分商城',
      }"
      @load="loadList"
    >
      <div class="list">
        <GoodsTab
          v-for="(item,index) in list"
          :key="index"
          :id="item.goods_id"
          :gtype="item.type"
          :exchange="item.point_exchange"
          :sales="item.point_exchange"
          :name="item.goods_name"
          :image="item.logo"
          :price="item.price"
        />
      </div>
    </List>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import GoodsTab from "../component/GoodsTab";
import TabSortScreen from "../component/TabSortScreen";
import { GET_GOODSLIST } from "@/api/integral";
import { list } from "@/mixins";
export default sfc({
  name: "integral-goods-list",
  data() {
    return {
      params: {
        order: "",
        sort: "",
        search_text: this.$route.query.search_text
          ? this.$route.query.search_text
          : "",
        category_id: this.$route.query.category_id
          ? this.$route.query.category_id
          : "",
        shop_id: this.$route.query.shop_id ? this.$route.query.shop_id : ""
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
      if (this.$route.name === "integral-goods-list") {
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
