<template>
  <van-cell-group>
    <van-swipe class="swipe" :autoplay="3000" :style="maxHeight">
      <van-swipe-item v-for="(item,index) in goods_detail.goods_images" :key="index">
        <img :src="item" :style="maxHeight" :onerror="$ERRORPIC.noGoods" />
      </van-swipe-item>
    </van-swipe>
    <van-cell>
      <div class="title">{{ goods_detail.goods_name }}</div>
      <div class="price">
        <span>{{goodsInfo.goodsPrice | yuan}}</span>
        <span class="stock">库存{{goodsInfo.stock}}件</span>
      </div>
    </van-cell>
    <van-cell :title="skuSelectedText" is-link @click="onSku" />
    <SkuPopup
      v-model="showSku"
      :info="goodsData"
      action="buy"
      :promote-params="{}"
      :get-goods-info="getGoodsInfo"
      @action="onSkuAction"
    />
  </van-cell-group>
</template>

<script>
import { Swipe, SwipeItem } from "vant";
import SkuPopup from "@/components/sku-popup/Sku";
import { isEmpty } from "@/utils/util";
import { _encode } from "@/utils/base64";
import { bindMobile } from "@/mixins";
export default {
  data() {
    return {
      showSku: false,
      goodsInfo: {},
      skuSelectedText: null
    };
  },
  props: {
    goods_detail: Object,
    orderType: {
      // 确认类型 2 ==> 立即开店 /  3 ==> 立即续费 / 4 ==>立即升级
      type: [String, Number],
      required: true
    }
  },
  mixins: [bindMobile],
  computed: {
    maxHeight() {
      return {
        maxHeight: document.body.offsetWidth + "px"
      };
    },
    goodsData() {
      let data = this.goods_detail.goods_id ? this.goods_detail : null;
      if (data) {
        data.goods_image = this.goods_detail.goods_images[0];
        data.is_allow_buy = true;
      }
      return data;
    }
  },
  methods: {
    getGoodsInfo(data) {
      this.goodsInfo = data;
      if (data.isSpec) {
        this.skuSelectedText = data.selectedSkuComb
          ? data.selectedSkuComb.sku_name
          : "请选择规格";
      } else {
        this.skuSelectedText = "已选：" + data.selectedNum + "件";
      }
    },
    onSkuAction(action, data) {
      this.onBuyNow(data);
    },
    onSku(type) {
      this.showSku = true;
    },
    // 立即购买
    onBuyNow(data) {
      const $this = this;
      $this.bindMobile().then(() => {
        const params = {};
        params.sku_list = [];
        params.order_type = $this.orderType;
        let sku_list_obj = {};
        sku_list_obj.num = data.selectedNum;
        sku_list_obj.sku_id = data.selectedSkuComb.id; //sku的Id
        params.sku_list.push(sku_list_obj);
        $this.$router.push({
          name: "microshop-confirmorder",
          query: {
            params: _encode(JSON.stringify(params))
          }
        });
      });
    }
  },
  components: {
    [Swipe.name]: Swipe,
    [SwipeItem.name]: SwipeItem,
    SkuPopup
  }
};
</script>

<style scoped>
.swipe img {
  width: 100%;
  height: auto;
  display: block;
}

.title {
  font-size: 16px;
  height: 48px;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  flex: auto;
  color: #333;
}

.price {
  color: #ff454e;
  font-size: 16px;
  font-weight: 800;
  height: 26px;
}

.price .stock {
  font-weight: 400;
  color: #999;
  font-size: 12px;
  margin-left: 10px;
}
</style>

