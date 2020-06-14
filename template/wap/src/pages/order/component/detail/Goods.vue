<template>
  <van-cell-group>
    <van-cell icon="shop-o">
      <van-row type="flex" slot="title">
        <span>{{detail.shop_name}}</span>
        <van-icon name="arrow" class="van-cell__right-icon" />
      </van-row>
    </van-cell>
    <van-cell>
      <GoodsCard
        class="goods-card"
        :to="item.goods_id && detail.order_type == 10 ? '/integral/goods/detail/' + item.goods_id : '/goods/detail/' + item.goods_id"
        :num="item.num"
        :price="item.price"
        :desc="item.sku_name"
        :title="item.goods_name"
        :thumb="item.pic_cover"
        v-for="(item,index) in detail.order_goods"
        :key="index"
      >
        <div slot="bottom" class="card__bottom">
          <div class="card__price-group" v-if="item.goods_point > 0">
            <div
              class="card__price"
              v-if="item.price > 0 && item.goods_point > 0"
            >{{ item.price | yuan }} + {{ item.goods_point }}{{pointText}}</div>
            <div class="card__price" v-else>{{ item.goods_point }}{{pointText}}</div>
          </div>
          <div class="card__price-group" v-else>
            <div class="card__price" v-if="item.price">{{ item.price | yuan }}</div>
          </div>
          <div class="card__num" v-if="item.num">x {{ item.num }}</div>
        </div>
        <div slot="footer" v-if="item.member_operation.length>0">
          <van-button
            size="mini"
            class="btn"
            v-for="(btn,btn_index) in item.member_operation"
            :key="btn_index"
            @click="onResult(item.order_goods_id)"
          >{{btn.name}}</van-button>
        </div>
      </GoodsCard>
    </van-cell>
  </van-cell-group>
</template>

<script>
import GoodsCard from "@/components/GoodsCard";
export default {
  data() {
    return {};
  },
  props: {
    detail: {
      type: Object
    }
  },
  filters: {
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
    }
  },
  methods: {
    // 退款/退货
    onResult(order_goods_id) {
      const $this = this;
      if ($this.detail.unrefund == 1)
        return $this.$Dialog.alert({
          message: $this.detail.unrefund_reason
        });
      $this.$router.push({
        name: "order-post",
        query: {
          order_goods_id
        }
      });
    }
  },
  components: {
    GoodsCard
  }
};
</script>

<style scoped>
.van-card {
  margin-top: 0;
}

.goods-card {
  padding: 0;
  background: #ffffff;
}

.btn {
  margin-left: 5px;
  width: auto;
  padding: 0 6px;
}
</style>
