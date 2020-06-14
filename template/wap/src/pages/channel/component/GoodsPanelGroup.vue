<template>
  <div class="item van-hairline--bottom">
    <GoodsCard
      class="goods-card"
      :price="goodsInfo.goodsPrice"
      :title="detail.goods_name"
      :thumb="goodsInfo.picture"
    >
      <div slot="tags" v-if="goodsInfo.stock">库存：{{goodsInfo.stock}}</div>
      <div slot="bottom" class="goods-bottom">
        <div class="price">
          <div class="card__price">{{priceText}} {{goodsInfo.goodsPrice | yuan}}</div>
        </div>
        <div class="btn-add e-handle" @click="showSku = true">
          <van-icon name="add" />
        </div>
      </div>
    </GoodsCard>
    <SkuPopup
      v-model="showSku"
      :info="goodsData"
      action="buy"
      :get-container="getContainer"
      :get-goods-info="getGoodsInfo"
      :promote-params="promoteParams"
      @action="onAction"
    >
      <div
        slot="header-price"
        class="van-sku__goods-price"
      >{{priceText}} {{goodsInfo.goodsPrice | yuan}}</div>
    </SkuPopup>
  </div>
</template>

<script>
import GoodsCard from "@/components/GoodsCard";
import SkuPopup from "@/components/sku-popup/Sku";
import { isEmpty, isIos } from "@/utils/util";
export default {
  data() {
    return {
      showSku: false,
      goodsInfo: {},
      promoteParams: {},
      getContainer: isIos() ? ".category" : null //ios真机会存在bfc问题，但是ios端提示报错(临时方案)
    };
  },
  props: {
    detail: {
      type: Object
    }
  },
  computed: {
    priceText() {
      return this.$route.params.type == "purchase" ? "采购价：" : "商城售价：";
    },
    goodsData() {
      const detail = this.detail;
      let data = {
        goods_id: detail.goods_id,
        goods_image: detail.img_list ? detail.img_list[0] : "",
        goods_name: detail.goods_name,
        goods_type: 1,
        max_buy: 0,
        max_market_price: detail.max_market_price,
        max_price: detail.max_price,
        min_buy: 0,
        min_market_price: detail.min_market_price,
        min_price: detail.min_price,
        state: 1,
        sku: {
          list: detail.sku.list,
          tree: detail.sku.tree
        },
        is_allow_buy: true
      };
      return data;
    }
  },
  methods: {
    getGoodsInfo(data) {
      this.goodsInfo = data;
    },
    onAction(action, data) {
      this.onBuyNow(data);
    },
    onBuyNow(data) {
      const params = {};
      params.num = data.selectedNum;
      params.sku_id = data.selectedSkuComb.id;
      params.channel_goods_type = this.detail.channel_info;
      params.buy_type = this.$route.params.type;
      // return console.log(params);
      this.$store.dispatch("addChannelCart", params).then(res => {
        this.$Toast.success("添加成功");
        this.showSku = false;
        setTimeout(() => {
          this.$store.dispatch("getChannelCartList", {
            page_index: 1,
            page_size: 20,
            buy_type: this.$route.params.type
          });
        }, 500);
      });
    }
  },
  components: {
    GoodsCard,
    SkuPopup
  }
};
</script>
<style scoped>
.goods-action-group {
  z-index: 1000;
}

.item .goods-card {
  background: #ffffff;
  padding: 5px;
}

.goods-bottom {
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.btn-add {
  display: flex;
  justify-content: center;
  align-items: center;
  font-size: 20px;
  color: #1989fa;
  padding: 4px;
  border-radius: 50%;
  overflow: hidden;
}
</style>
