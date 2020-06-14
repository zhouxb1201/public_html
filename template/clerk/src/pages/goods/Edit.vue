<template>
  <div class="goods-edit bg-f8">
    <Navbar />
    <EditInfoPanelGroup v-model="state" :info="goodsInfo" />
    <EditSkuPanelGroup :list="sku_list" />
    <EditFootBtnGroup :list="sku_list" :loading="isLoading" @comfirm="save" />
  </div>
</template>

<script>
import EditInfoPanelGroup from "./component/EditInfoPanelGroup";
import EditSkuPanelGroup from "./component/EditSkuPanelGroup";
import EditFootBtnGroup from "./component/EditFootBtnGroup";
import { GET_STOREGOODSINFO, SAVE_STOREGOODSINFO } from "@/api/goods";
import { isIos } from "@/utils/util";
export default {
  name: "goods-edit",
  data() {
    return {
      state: false,
      isLoading: false,
      goodsInfo: {
        img: [],
        name: ""
      },
      sku_list: [
        {
          sku_name: "",
          sku_id: 0,
          price: 0,
          market_price: 0,
          stock: 0,
          bar_code: ""
        }
      ]
    };
  },
  created() {
    this.loadData();
  },
  beforeRouteEnter(to, from, next) {
    if ("/clerk" + to.path !== global.location.pathname && isIos()) {
      // ios手机 刷新页面获取当前url
      location.assign("/clerk" + to.fullPath);
    } else {
      next();
    }
  },
  methods: {
    loadData() {
      let type = this.$route.hash == "#add" ? "add" : "edit";
      GET_STOREGOODSINFO(this.$route.params.goodsid, type).then(({ data }) => {
        this.goodsInfo = {
          img: data.goods_img || [],
          name: data.goods_name
        };
        this.state = !!data.state;
        data.sku_list.forEach(e => {
          e.bar_code = e.bar_code || "";
        });
        this.sku_list = data.sku_list || [];
      });
    },
    save(sku_list) {
      let product = {};
      product.goods_id = this.$route.params.goodsid;
      product.state = this.state ? 1 : 0;
      product.sku_list = sku_list;
      // console.log(product);
      // return;
      this.isLoading = true;
      SAVE_STOREGOODSINFO({ product })
        .then(({ message }) => {
          this.$Toast.success(message);
          this.$router.back();
        })
        .catch(() => {
          this.isLoading = false;
        });
    }
  },
  components: {
    EditInfoPanelGroup,
    EditSkuPanelGroup,
    EditFootBtnGroup
  }
};
</script>

<style scoped>
</style>