<template>
  <PopupBottom v-model="show" title="已选商品">
    <van-cell-group class="list" :border="false" v-if="list.length>0">
      <van-cell class="item" center v-for="(item,index) in list" :key="index">
        <van-checkbox
          slot="icon"
          class="checkbox-icon"
          v-model="item.checked"
          :disabled="item.disabled"
        />
        <GoodsCard
          class="goods-card"
          :title="item.goods_name"
          :price="item.price"
          :desc="item.sku_name"
          :thumb="item.goods_img | BASESRC"
        >
          <div slot="bottom" class="info-foot">
            <van-stepper
              v-model="item.num"
              integer
              async-change
              disable-input
              @change="onNumChange(item,index)"
              @overlimit="onStepperLimit"
              :max="item.max_buy === 0 ? item.stock : item.stock > item.max_buy ? item.max_buy : item.stock"
            />
            <div class="del e-handle" @click="onRemove(item.cart_id)">
              <van-icon name="delete" />
            </div>
          </div>
        </GoodsCard>
      </van-cell>
    </van-cell-group>
    <LoadMoreEnd finished text-background="#ffffff" v-else />
    <div slot="footer" class="sku-action-group">
      <van-button
        class="action-btn"
        type="danger"
        round
        block
        :disabled="isDisabled"
        @click="onSubmit"
      >{{btnText}}</van-button>
    </div>
  </PopupBottom>
</template>

<script>
import { Stepper } from "vant";
import GoodsCard from "@/components/GoodsCard";
import LoadMoreEnd from "@/components/LoadMoreEnd";
import PopupBottom from "@/components/PopupBottom";
import { isEmpty } from "@/utils/util";
import { yuan } from "@/utils/filter";
import { bindMobile } from "@/mixins";
import { EDIT_STORECARTNUM, REMOVE_STORECART } from "@/api/store";
import { _encode } from "@/utils/base64";
export default {
  data() {
    return {};
  },
  props: {
    value: {
      type: Boolean,
      default: false
    }
  },
  mixins: [bindMobile],
  computed: {
    show: {
      get() {
        return this.value;
      },
      set(e) {
        this.$emit("input", e);
      }
    },
    list() {
      let items = this.$store.state._store.cartList;
      let cart_id_list = [];
      let sku_list = [];
      let total = 0;
      items.forEach(item => {
        if (item.checked) {
          cart_id_list.push(item.cart_id);
          sku_list.push({
            sku_id: item.sku_id,
            coupon_id: 0,
            shop_id: item.shop_id
          });
          total += parseFloat(item.price) * parseFloat(item.num);
        }
      });
      this.cart_id_list = cart_id_list;
      this.sku_list = sku_list;
      this.$store.commit("setStoreCartTotalPrice", total);
      return items;
    },
    btnText() {
      return `结算(合计：${yuan(this.$store.state._store.totalPrice)})`;
    },
    isDisabled() {
      return (
        this.list.every(({ checked }) => !checked) ||
        this.$store.state._store.isBtnDisabled
      );
    }
  },
  methods: {
    onClose() {
      this.$emit("input", false);
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
    onNumChange({ num, max_buy, stock, cart_id }, index) {
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
      const params = {};
      params.cart_id = cart_id;
      params.num = count;
      EDIT_STORECARTNUM(params).then(() => {
        this.$store
          .dispatch("getStoreCartList", {
            store_id: this.$route.params.id
          })
          .then(() => {
            this.async = false;
          });
      });
    },
    onStepperLimit(type) {
      if (type === "plus") {
        this.$Toast("已超出最大购买数！");
      } else {
        this.$Toast("至少选择一件！");
      }
    },
    onRemove(cart_id) {
      const $this = this;
      $this.$Dialog
        .confirm({
          message: "确定删除该商品？"
        })
        .then(() => {
          REMOVE_STORECART(cart_id).then(({ message }) => {
            $this.$Toast.success(message);
            $this.$store
              .dispatch("getStoreCartList", {
                store_id: $this.$route.params.id
              })
              .then(data => {
                isEmpty(data.cart_list) && $this.onClose();
              });
          });
        });
    },
    onSubmit() {
      const $this = this;
      const params = {};
      params.order_tag = "cart";
      params.cart_from = 2;
      params.cart_id_list = $this.cart_id_list;
      params.sku_list = $this.sku_list;
      // return console.log(params)
      $this.onClose();
      setTimeout(() => {
        $this.$router.push({
          name: "order-confirm",
          query: {
            params: _encode(JSON.stringify(params))
          }
        });
      }, 100);
    }
  },
  components: {
    GoodsCard,
    LoadMoreEnd,
    [Stepper.name]: Stepper,
    PopupBottom
  }
};
</script>
<style scoped>
.list {
  max-height: 400px;
  overflow-y: auto;
}

.goods-card {
  background: #fff;
  padding: 0;
}

.checkbox-icon {
  margin-right: 10px;
}

.info-head {
  width: 100%;
  display: flex;
}

.info-head .name {
  max-height: 34px;
  font-weight: bold;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  height: 32px;
  line-height: 17px;
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

.info-foot >>> .van-stepper__input[disabled] {
  color: #323233;
}
</style>
