<template>
  <div class="microshop-previewlist bg-f8">
    <Navbar :title="navBarTitle" :isMenu="false" />
    <TabSortScreen :set-params="setParams" />
    <List
      v-model="loading"
      :finished="finished"
      :error.sync="error"
      :is-empty="isListEmpty"
      :empty="{
        pageType: 'microshoppreview', 
        message: '暂无商品', 
        showFoot: true,
        btnLink: '/microshop/previewshop', 
        btnText: '返回微店',
      }"
      @load="loadList"
    >
      <div class="list">
        <GoodsTab
          v-for="(item,index) in list"
          :key="index"
          :name="item.goods_name"
          :price="item.price"
          :sales="item.sales"
          :market-price="item.market_price"
          :image="item.logo"
          :isShow="false"
          :link="'/goods/detail/'+item.goods_id+'?shopkeeper_id='+shopkeeper_id"
        />
      </div>
    </List>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import GoodsTab from "../component/GoodsTab";
import TabSortScreen from "../component/TabSortScreen";
import { GET_GOODSLIST } from "@/api/goods";
import { list } from "@/mixins";
export default sfc({
  name: "microshop-previewlist",
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
      },
      shopkeeper_id: ""
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
    },
    info() {
      return this.$store.state.microshop.info;
    },
    shopkeeperid() {
      const { info } = this;
      return info && info.uid ? info.uid : "";
    }
  },
  watch: {
    "$route.query": function({ category_id, search_text }) {
      if (this.$route.name === "microshop-previewlist") {
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
    if (this.$route.query.shopkeeper_id) {
      this.shopkeeper_id = this.$route.query.shopkeeper_id;
      this.loadList();
    } else {
      this.$store
        .dispatch("getMicroshopInfo")
        .then(res => {
          this.shopkeeper_id = this.shopkeeperid;
          this.loadList();
          this.$refs.load.success();
        })
        .catch(error => {
          console.log(error);
        });
    }
  },
  methods: {
    loadList(init) {
      const $this = this;
      if (init && init === "init") {
        $this.initList();
      }
      $this.params.shopkeeper_id = $this.shopkeeper_id; //获取微店id
      GET_GOODSLIST($this.params)
        .then(({ data }) => {
          let list = data.goods_list;
          $this.pushToList(list, data.page_count, init);
        })
        .catch(() => {
          console.log(error);
        });
    },
    setParams(params) {
      this.params = params;
      this.loadList("init");
    },
    toGoods(goods_id) {
      this.$router.push({
        name: "goods-detail",
        params: {
          goodsid: goods_id,
          shopkeeper_id: this.shopkeeper_id
        }
      });
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
