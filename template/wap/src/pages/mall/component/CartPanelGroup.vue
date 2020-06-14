<template>
  <van-cell-group class="items card-group-box">
    <van-cell title-class="cell-title">
      <template slot="title">
        <van-checkbox
          v-model="items.checked"
          @click="onShopChecked"
          ref="checkboxes"
          class="cell-checkbox"
          >{{ items.shop_name }}</van-checkbox
        >
      </template>
      <div
        class="cell-delivery"
        @click="showDelivery"
        v-if="$store.state.config.addons.store"
      >
        <van-icon name="location-o" size="14px" />
        <span class="text">{{ deliveryText }}</span>
      </div>
      <div slot="right-icon" v-if="couponList.length">
        <span
          class="shop-title-rigth e-handle"
          @click="isShowPopupCoupon = true"
        >
          领券
        </span>
      </div>
    </van-cell>
    <van-cell
      class="goods-item"
      v-for="(item, index) in items.goods_list"
      :key="index"
    >
      <div class="info">
        <van-checkbox
          v-model="item.checked"
          :disabled="item.disabled"
          @change="onGoodsChecked"
        />
        <GoodsCard
          class="goods-card"
          :id="item.goods_id"
          :desc="item.sku_name"
          :thumb="item.picture_info | BASESRC"
        >
          <div slot="title" class="info-head">
            <router-link
              tag="div"
              class="name"
              :to="'/goods/detail/' + item.goods_id"
            >
              <van-tag
                type="danger"
                class="tag"
                v-if="showTag(item.promotion_type)"
                >{{ tagName(item.promotion_type) }}</van-tag
              >
              {{ item.goods_name }}
            </router-link>
            <div class="price">{{ item.price | yuan }}</div>
          </div>
          <div
            class="sku-text"
            slot="desc"
            v-if="item.sku_name"
            @click="clickSku(item)"
          >
            <div class="text">{{ item.sku_name }}</div>
            <van-icon name="arrow-down" size="14px" />
          </div>
          <div slot="bottom" class="info-foot">
            <template v-if="item.stock">
              <van-stepper
                v-model="item.num"
                integer
                async-change
                @change="onNumChange(item, index)"
                @overlimit="onStepperLimit"
                :max="
                  item.max_buy === 0
                    ? item.stock
                    : item.stock > item.max_buy
                    ? item.max_buy
                    : item.stock
                "
              />
              <div class="del e-handle" @click="onRemove(item.cart_id)">
                <van-icon name="delete" />
              </div>
            </template>
            <div v-else class="text-maintone">库存不足</div>
          </div>
        </GoodsCard>
      </div>
    </van-cell>
    <CellFullCut
      v-if="items.mansong_info.rule_id"
      :items="items.mansong_info"
    />
    <PopupCoupon
      v-model="isShowPopupCoupon"
      :items="couponList"
      :title="items.shop_name"
      :get-type="4"
    />
    <PopupDeliveryGroup
      v-model="isShowDelivery"
      :isShowExpress="isShowExpress"
      :list="storeList"
      @select="selectDelivery"
      v-if="$store.state.config.addons.store"
    />
    <SkuPopup
      v-if="goodsData"
      v-model="showSku"
      :info="goodsData"
      action="buy"
      :initial="initialSku"
      :promote-params="promoteParams"
      @action="onAction"
      @close="onSkuClose"
    />
  </van-cell-group>
</template>

<script>
import { Stepper } from "vant";
import GoodsCard from "@/components/GoodsCard";
import PopupCoupon from "@/components/PopupCoupon";
import CellFullCut from "@/components/CellFullCut";
import PopupDeliveryGroup from "./PopupDeliveryGroup";
import SkuPopup from "@/components/sku-popup/Sku";
import { GET_SHOPCOUPONLIST } from "@/api/coupon";
import {
  REMOVE_CARTGOODS,
  EDIT_CARTNUM,
  GET_SHOPCARTINFO,
  EDIT_CARTINFO
} from "@/api/mall";
import { GET_GOODSINFO } from "@/api/goods";
export default {
  data() {
    return {
      isShowPopupCoupon: false,
      couponList: [],
      isShowDelivery: false,
      storeList: [],
      deliveryText: "快递配送",

      showSku: false,
      skuAction: "",
      goodsData: null,
      promoteParams: {},
      initialSku: null,
      showExpress: false
    };
  },
  filters: {
    toNumber(value) {
      return parseFloat(value);
    }
  },
  props: {
    items: Object,
    loadData: Function,
    isShowExpress: Object
  },
  mounted() {
    if (this.$store.state.config.addons.coupontype) {
      const $this = this;
      const items = $this.items;
      let goods_id_array = [];
      items.goods_list.forEach(({ goods_id }) => {
        goods_id_array.push(goods_id);
      });
      GET_SHOPCOUPONLIST({
        goods_id_array
      }).then(({ data }) => {
        data.forEach(e => {
          e.loading = false;
        });
        $this.couponList = data;
      });
    }
    if (this.isShowExpress.has_express !== "") {
      this.deliveryText = "门店自提";
    }
  },
  methods: {
    onShopChecked() {
      const items = this.items;
      items.goods_list.forEach(item => {
        item.checked = item.disabled ? false : !items.checked;
      });
    },
    onGoodsChecked() {
      const items = this.items;
      const checkItems = items.goods_list.filter(item => {
        return item.checked == true;
      }).length;
      const InitialItems = items.goods_list.filter(item => {
        return !item.disabled;
      }).length;
      items.checked = checkItems == InitialItems;
    },
    onNumChange({ num, max_buy, stock, cart_id, shop_id }, index) {
      if (num <= 0) {
        return;
      }
      if (this.async) {
        return;
      }
      const maxCount =
        max_buy === 0 ? stock : stock > max_buy ? max_buy : stock;
      const count = num > maxCount ? maxCount : num;
      this.async = true;
      let params = {
        cart_id: cart_id,
        shop_id: shop_id,
        num: count
      };
      this.currentStoreId && (params.store_id = this.currentStoreId);
      this.editCart(params).then(() => {
        this.async = false;
      });
    },
    onRemove(cart_id) {
      const $this = this;
      $this.$Dialog
        .confirm({
          message: "确定删除该商品？"
        })
        .then(() => {
          REMOVE_CARTGOODS(cart_id).then(({ message }) => {
            $this.$Toast.success(message);
            setTimeout(() => {
              $this.loadData();
            }, 100);
          });
        });
    },
    onStepperLimit(type, num) {
      if (type === "plus") {
        this.$Toast("已超出最大购买数！");
      } else {
        this.$Toast("至少选择一件！");
      }
    },
    tagName(e) {
      let text = "";
      switch (e) {
        case 1:
          text = "秒杀";
          break;
        case 2:
          text = "团购";
          break;
        case 3:
          text = "预售";
          break;
        case 4:
          text = "砍价";
          break;
        case 5:
          text = "限时折扣";
          break;
      }
      return text;
    },
    showTag(e) {
      return parseInt(e) ? true : false;
    },
    showDelivery() {
      let sku_list = this.items.goods_list.map(e => e.sku_id);
      this.getLocation().then(location => {
        let params = {
          lng: location.lng || "",
          lat: location.lat || "",
          shop_id: this.items.shop_id,
          sku_list: sku_list
        };
        this.getStoreList(params);
        this.isShowDelivery = true;
      });
    },
    getStoreList(params) {
      this.$store.dispatch("getShopStoreList", params).then(data => {
        this.storeList = data || [];
        this.storeList.forEach(e => {
          e.address =
            e.province_name + e.city_name + e.dictrict_name + e.address;
        });
      });
    },
    getLocation() {
      return new Promise((resolve, reject) => {
        this.$store
          .dispatch("getBMapLocation")
          .then(({ location }) => {
            resolve(location);
          })
          .catch(error => {
            this.$Toast(error);
            resolve({});
          });
      });
    },
    selectDelivery(index) {
      let params = {
        shop_id: this.items.shop_id,
        store_id: 0
      };

      if (index == -1) {
        this.deliveryText = "快递配送";
        params.store_id = 0;
      } else {
        this.deliveryText = this.storeList[index].store_name;
        params.store_id = this.storeList[index].store_id;
      }
      this.currentStoreId = params.store_id;
      this.getShopCartInfo(params);
    },
    getShopCartInfo(params) {
      GET_SHOPCARTINFO(params).then(({ data }) => {
        this.resetShopCartData(data);
      });
    },
    // 重置购物车信息
    resetShopCartData(data = {}) {
      data.goods_list &&
        data.goods_list.forEach(item => {
          if (
            (item.promotion_type && item.promotion_type != 5) ||
            !item.stock ||
            !item.state
          ) {
            item.disabled = true;
          } else {
            item.checked = true;
          }
        });
      this.items.goods_list = data.goods_list || [];
      this.items.discount_info = data.discount_info || {};
      this.items.mansong_info = data.mansong_info || {};
    },
    onAction(action, data) {
      let params = {
        cart_id: this.currentCartId || 0,
        shop_id: data.shopId,
        sku_list: {
          sku_id: data.selectedSkuComb.id,
          num: data.selectedNum
        }
      };
      this.currentStoreId && (params.store_id = this.currentStoreId);
      this.editCart(params).then(() => {
        this.showSku = false;
      });
    },
    onSkuClose() {
      this.goodsData = null;
    },
    clickSku(item) {
      let params = {
        goods_id: item.goods_id
      };
      this.initialSku = {
        id: item.sku_id,
        num: item.num
      };
      this.currentStoreId && (params.store_id = this.currentStoreId);
      this.currentCartId = item.cart_id;
      GET_GOODSINFO(params, { loading: true }).then(({ data }) => {
        this.goodsData = data.goods_detail;
        this.goodsData.goods_image = data.goods_detail.goods_image_yun;
        this.goodsData.is_allow_buy =
          typeof data.is_allow_buy == "boolean" ? data.is_allow_buy : true;
        this.showSku = true;
      });
    },
    // 编辑购物车
    editCart(params) {
      return new Promise((resolve, reject) => {
        EDIT_CARTINFO(params)
          .then(({ data }) => {
            this.resetShopCartData(data);
            resolve();
          })
          .catch(() => {
            reject();
          });
      });
    }
  },
  components: {
    GoodsCard,
    PopupCoupon,
    CellFullCut,
    PopupDeliveryGroup,
    SkuPopup,
    [Stepper.name]: Stepper
  }
};
</script>
<style scoped>
.items {
  background: #fff;
}

.goods-item .info {
  display: flex;
}

.cell-title {
  display: flex;
  width: 50%;
}

.cell-checkbox {
  display: flex;
  align-items: center;
}

.cell-checkbox >>> .van-checkbox__label {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.cell-delivery {
  text-align: left;
  display: flex;
  align-items: center;
  font-size: 12px;
  color: #606266;
}

.cell-delivery .text {
  padding-left: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.goods-item .info >>> .van-checkbox {
  display: flex;
  align-items: center;
  margin-right: 6px;
}

.shop-title-rigth {
  padding: 4px;
  color: #ff454e;
}

.goods-item .info .goods-card {
  flex: 1;
  margin-top: 0;
}

.discount-cell {
  padding: 6px 15px;
}

.discount-list .item {
  display: flex;
  font-size: 12px;
  color: #909399;
  align-items: center;
  line-height: 18px;
  margin: 4px 0;
}

.discount-list .item span {
  padding-left: 6px;
}

.info-head {
  width: 100%;
  display: flex;
}

.info-head .name {
  max-height: 40px;
  font-weight: bold;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  height: 40px;
  line-height: 20px;
  flex: 1;
  position: relative;
}

.info-head .price {
  color: #ff454e;
  font-weight: bold;
  line-height: 1.2;
  padding-left: 4px;
}

.info-foot {
  width: 100%;
  display: flex;
  justify-content: space-between;
}

.info-foot .del {
  display: flex;
  padding: 7px;
  font-size: 16px;
}

.tag {
  margin-right: 4px;
}

.sku-text {
  display: flex;
  align-items: center;
  width: 50%;
  justify-content: space-between;
  background: #f8f8f8;
  color: #606266;
  height: 20px;
  padding: 0 5px;
}

.sku-text .text {
  padding-right: 5px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>
