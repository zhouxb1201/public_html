<template>
  <div class="item van-hairline--bottom">
    <GoodsCard
      class="goods-card"
      :id="goods_detail.goods_id"
      :price="goods_detail.min_price"
      :title="goods_detail.goods_name"
      :thumb="goods_detail.goods_img | BASESRC"
    >
      <div slot="bottomRight">
        <div class="btn-add e-handle" @click="showSku = true">
          <van-icon name="add" />
        </div>
      </div>
    </GoodsCard>
    <SkuPopup
      v-model="showSku"
      :info="goodsInfo"
      :promote-type="promoteType"
      :promote-params="promoteParams"
      :get-container="getContainer"
      @action="onAction"
    />
  </div>
</template>

<script>
import GoodsCard from "@/components/GoodsCard";
import { isEmpty, isIos } from "@/utils/util";
import SkuPopup from "@/components/sku-popup/Sku";
import { ADD_STOREGOODSTOCART } from "@/api/store";
import { bindMobile } from "@/mixins";
import { _encode } from "@/utils/base64";
export default {
  data() {
    return {
      goods_detail: {},
      /**
       * 活动类型
       * normal    =>    普通商品类型(默认)
       * seckill   =>    秒杀商品类型
       * group     =>    拼团商品类型
       * presell   =>    预售商品类型
       * bargain   =>    砍价商品类型
       * limit     =>    限时商品类型
       */
      promoteType: "normal",
      promoteParams: {},
      showSku: false,
      addCartFlag: true, // 防止重复点击添加多次购物车
      getContainer: isIos() ? ".category" : null //ios真机会存在bfc问题，但是ios端提示报错(临时方案)
    };
  },
  props: {
    items: {
      type: Object
    },
    info: Object
  },
  mixins: [bindMobile],
  computed: {
    goodsInfo() {
      let info = this.items.goods_detail;
      info.goods_image = info.goods_img;
      info.is_allow_buy =
        typeof this.items.is_allow_buy == "boolean"
          ? this.items.is_allow_buy
          : true;
      return info;
    }
  },
  mounted() {
    const $this = this;
    const data = $this.items;
    $this.goods_detail = data.goods_detail;

    let promoteType = "normal";
    if (data.seckill_list.seckill_id) {
      promoteType = "seckill";
    } else if (data.group_list.group_id) {
      promoteType = "group";
    } else if (data.presell_list.presell_id) {
      promoteType = "presell";
    } else if (data.bargain_list.bargain_id) {
      promoteType = "bargain";
    } else if (data.limit_list && data.limit_list.discount_id) {
      promoteType = "limit";
    }
    this.promoteType = promoteType;
    if (promoteType != "normal") {
      this.promoteParams = data[`${promoteType}_list`] || {};
    }
  },
  methods: {
    onAction(action, params) {
      // return console.log(action, params);
      if (
        action === "buy" ||
        action === "group" ||
        action === "seckill" ||
        action === "presell"
      ) {
        this.onBuyNow(params);
      } else if (action === "addCart") {
        this.onAddCart(params);
      } else if (action == "bargain") {
        this.onBargain(params);
      } else {
        this.$Toast("暂无后续操作" + action);
      }
    },
    // 添加购物车
    onAddCart(data) {
      const $this = this;
      $this.bindMobile().then(() => {
        if (!$this.addCartFlag) return;
        $this.addCartFlag = false;
        let params = {};
        params.store_id = $this.$route.params.id;
        params.goods_id = data.id;
        params.num = data.selectedNum;
        params.sku_id = data.selectedSkuComb.id;
        if (data.seckill_id) params.seckill_id = data.seckill_id;
        // console.log(params);
        // return;
        ADD_STOREGOODSTOCART(params)
          .then(({ message }) => {
            $this.$Toast.success(message);
            if ($this.showSku) {
              $this.showSku = false;
              setTimeout(() => {
                $this.addCartFlag = true;
              }, 200);
              $this.$store.dispatch("getStoreCartList", {
                store_id: $this.$route.params.id
              });
            }
          })
          .catch(() => {
            $this.addCartFlag = true;
          });
      });
    },
    // 立即购买
    onBuyNow(data) {
      const $this = this;
      $this.bindMobile().then(() => {
        let params = {};
        params.order_tag = "buy_now";
        params.goodsType = data.goodsType;
        params.shipping_type = 2;
        params.sku_list = [];
        if (data.presell_id) params.presell_id = data.presell_id;
        let sku_list_obj = {};
        sku_list_obj.num = data.selectedNum;
        sku_list_obj.sku_id = data.selectedSkuComb.id;
        sku_list_obj.store_id = $this.info.store_id;
        sku_list_obj.store_name = $this.info.store_name;
        sku_list_obj.shop_id = data.shopId;
        if (data.seckill_id) sku_list_obj.seckill_id = data.seckill_id;
        if (data.channel_id) sku_list_obj.channel_id = data.channel_id;
        if (data.group_id) {
          params.group_id = data.group_id;
          if (data.record_id) {
            params.record_id = data.record_id;
          }
        }
        params.sku_list.push(sku_list_obj);
        // console.log(params);
        // return;
        $this.$router.push({
          name: "order-confirm",
          query: {
            params: _encode(JSON.stringify(params))
          }
        });
      });
    },
    // 砍价
    onBargain(data) {
      this.$router.push({
        name: "bargain-detail",
        params: {
          goodsid: data.id,
          bargainid: data.bargain_id,
          bargainuid: data.bargain_uid
        }
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
