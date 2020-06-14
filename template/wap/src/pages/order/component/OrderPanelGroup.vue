<template>
  <van-cell-group class="item card-group-box" :border="false">
    <van-cell
      icon="shop-o"
      :value="items.status_name"
      value-class="text-maintone"
    >
      <van-row type="flex" slot="title">
        <span>{{ items.shop_name }}</span>
        <van-icon name="arrow" class="van-cell__right-icon" />
      </van-row>
    </van-cell>
    <van-cell>
      <GoodsCard
        class="goods-card"
        v-for="(item, goods_index) in items.order_goods"
        :to="
          item.goods_id && items.order_type == 10
            ? '/integral/goods/detail/' + item.goods_id
            : '/goods/detail/' + item.goods_id
        "
        lazyLoad
        :desc="item.spec | filterSpec"
        :num="item.num"
        :price="item.price"
        :title="item.goods_name"
        :thumb="item.pic_cover"
        :key="goods_index"
      >
        <div slot="bottom" class="card__bottom">
          <div class="card__price-group" v-if="item.goods_point > 0">
            <div
              class="card__price"
              v-if="item.price > 0 && item.goods_point > 0"
            >
              {{ item.price | yuan }} + {{ item.goods_point }}{{ pointText }}
            </div>
            <div class="card__price" v-else>
              {{ item.goods_point }}{{ pointText }}
            </div>
          </div>
          <div class="card__price-group" v-else>
            <div class="card__price" v-if="item.price">
              {{ item.price | yuan }}
            </div>
          </div>
          <div class="card__num" v-if="item.num">x {{ item.num }}</div>
        </div>
        <div
          slot="footer"
          v-if="item.member_operation && item.member_operation.length > 0"
        >
          <van-button
            size="mini"
            class="btn"
            v-for="(btn, btn_index) in item.member_operation"
            :key="btn_index"
            @click="onResult(item.order_goods_id)"
            >{{ btn.name }}</van-button
          >
        </div>
      </GoodsCard>
    </van-cell>
    <van-cell>
      <van-row type="flex" justify="end" class="van-cell__value">
        <van-col>
          <span class="pay-type-text" v-if="items.order_status !== 0">{{
            items.pay_type_name
          }}</span>
          <span>{{ items.order_status === 0 ? "待支付" : "实付" }}</span
          >:
          <span class="pay-money-text">{{ payText }}</span>
        </van-col>
      </van-row>
    </van-cell>
    <van-cell>
      <FootOperation :info="items" @callback="onCallback" />
    </van-cell>
  </van-cell-group>
</template>
<script>
import GoodsCard from "@/components/GoodsCard";
import FootOperation from "./FootOperation";
import { isEmpty } from "@/utils/util";
import { yuan } from "@/utils/filter";
export default {
  data() {
    return {};
  },
  props: {
    items: {
      type: Object
    }
  },
  filters: {
    filterSpec(value) {
      if (isEmpty(value)) return "";
      let newArr = [];
      value.forEach(e => {
        let str = e.spec_name + " " + e.spec_value_name;
        newArr.push(str);
      });
      return newArr.join(" , ");
    },
    afterSaleBtnText(value, status) {
      let text = "";
      if (status === 0) {
        if (value === 1) {
          text = "退款";
        } else if (value === 2 || value === 3) {
          text = "退货/退款";
        } else if (value === -1) {
          text = "售后情况";
        }
      } else {
        text = "售后情况";
      }
      return text;
    }
  },
  computed: {
    pointText() {
      return this.$store.state.member.memberSetText.point_style;
    },
    payText() {
      let text = "";
      let {
        order_type,
        order_money,
        order_real_money,
        order_point
      } = this.items;
      if (order_type == 10) {
        // 积分商城的订单
        let money = parseFloat(order_money) ? yuan(order_money) + " + " : "";
        let point = order_point ? order_point + this.pointText : "";
        text = money + point;
      } else {
        // 普通订单
        text = order_real_money;
      }
      return text;
    }
  },
  methods: {
    // 退款/退货
    onResult(order_goods_id) {
      const $this = this;
      const { unrefund, unrefund_reason } = $this.items;
      if (unrefund == 1)
        return $this.$Dialog.alert({
          message: unrefund_reason
        });
      $this.$router.push({
        name: "order-post",
        query: {
          order_goods_id
        }
      });
    },
    onCallback(res) {
      this.$emit("init-list", res);
    }
  },
  components: {
    GoodsCard,
    FootOperation
  }
};
</script>
<style scoped>
.goods-card {
  padding: 0;
  background: #ffffff;
}

.pay-type-text {
  color: #909399;
  font-size: 12px;
  padding-right: 10px;
}

.pay-money-text {
  color: #ff454e;
  padding-left: 6px;
}

.btn {
  margin-left: 5px;
  width: auto;
  padding: 0 6px;
}
</style>
