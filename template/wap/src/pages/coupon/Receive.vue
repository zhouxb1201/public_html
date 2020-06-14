<template>
  <div ref="load" class="coupon-receive bg-f8">
    <Navbar />
    <ReceiveHeadInfo :detail="detail" />
    <TabSortScreen
      class="tab-box"
      :class="tabFixedClass"
      :set-params="setParams"
      ref="TabSortScreen"
    />
    <List
      v-model="loading"
      :finished="finished"
      :error.sync="error"
      :is-empty="isListEmpty"
      :empty="{
        pageType: 'goods', 
        message: '暂无商品', 
        showFoot: true,
        btnLink: '/', 
        btnText: '返回首页',
      }"
      @load="loadList"
    >
      <div class="list">
        <GoodsBox
          v-for="(item,index) in list"
          :key="index"
          :id="item.goods_id"
          :name="item.goods_name"
          :price="item.price"
          :sales="item.sales"
          :market-price="item.market_price"
          :image="item.pic_cover"
        />
      </div>
    </List>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import ReceiveHeadInfo from "./component/ReceiveHeadInfo";
import GoodsBox from "@/components/GoodsBox";
import TabSortScreen from "../goods/component/TabSortScreen";
import { GET_COUPONDETAIL, GET_COUPONDETAILGOODS } from "@/api/coupon";
import { list } from "@/mixins";
export default sfc({
  name: "coupon-receive",
  data() {
    return {
      detail: {},
      params: {
        coupon_type_id: "",
        order: "",
        sort: "",
        min_price: "",
        max_price: "",
        is_recommend: 0,
        is_new: 0,
        is_hot: 0,
        is_promotion: 0,
        is_shipping_free: 0
      },
      tabFixedClass: ""
    };
  },
  mixins: [list],
  mounted() {
    const $this = this;
    $this.loadData();
    window.addEventListener("scroll", $this.handleScroll, true);
  },
  destroyed() {
    const $this = this;
    window.removeEventListener("scroll", $this.handleScroll, true);
  },
  deactivated() {
    const $this = this;
    window.removeEventListener("scroll", $this.handleScroll, true);
  },
  methods: {
    loadData() {
      let couponid = this.$route.params.couponid;
      GET_COUPONDETAIL(couponid).then(({ data }) => {
        this.detail = data;
        this.params.coupon_type_id = data.coupon_type_id;
        this.loadList();
      });
    },
    loadList(init) {
      const $this = this;
      if (init && init === "init") {
        $this.initList();
      }
      GET_COUPONDETAILGOODS($this.params)
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
    },
    handleScroll() {
      const el = this.$refs.TabSortScreen.$el;
      let scrollTop =
        window.pageYOffset ||
        document.documentElement.scrollTop ||
        document.body.scrollTop;
      const offsetTop = el.offsetTop;
      if (!this.$store.state.isWeixin) {
        scrollTop = scrollTop + 46;
      }
      if (scrollTop > offsetTop) {
        this.tabFixedClass = !this.$store.state.isWeixin
          ? "tab-fixed-n"
          : "tab-fixed";
      } else {
        this.tabFixedClass = "";
      }
    }
  },
  components: {
    ReceiveHeadInfo,
    GoodsBox,
    TabSortScreen
  }
});
</script>
<style scoped>
.list {
  height: auto;
  overflow: hidden;
  padding: 4px;
  display: flex;
  flex-wrap: wrap;
}

.tab-box >>> .van-tabs {
  position: relative;
}
.tab-box.tab-fixed >>> .van-tabs {
  position: fixed;
  top: 0;
  z-index: 100;
  width: 100%;
}
.tab-box.tab-fixed-n >>> .van-tabs {
  position: fixed;
  top: 46px;
  z-index: 100;
  width: 100%;
}
</style>

