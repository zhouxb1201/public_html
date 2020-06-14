<template>
  <van-cell-group class="item card-group-box" :border="false">
    <van-cell icon="shop-o" :value="items.status_name" value-class="text-maintone">
      <van-row type="flex" slot="title">
        <span>{{items.shop_name}}</span>
        <van-icon name="arrow" class="van-cell__right-icon"/>
      </van-row>
    </van-cell>
    <van-cell>
      <GoodsCard
        class="goods-card"
        v-for="(item,goods_index) in items.order_goods"
        lazyLoad
        :num="item.num"
        :price="item.price"
        :desc="item.spec | filterSpec"
        :title="item.goods_name"
        :thumb="item.pic_cover"
        :key="goods_index"
      />
    </van-cell>
    <van-cell>
      <van-row type="flex" justify="end" class="van-cell__value">
        <van-col>
          <span class="pay-type-text" v-if="items.order_status !== 0">{{items.pay_type_name}}</span>
          <span>{{items.order_status === 0 ? '待支付' : '实付'}}</span>:
          <span class="pay-money-text">{{items.order_money | yuan}}</span>
        </van-col>
      </van-row>
    </van-cell>
    <van-cell>
      <FootOperation :info="items" @callback="onCallback"/>
    </van-cell>
  </van-cell-group>
</template>
<script>
import GoodsCard from "@/components/GoodsCard";
import FootOperation from "./FootOperation";
import { isEmpty } from "@/utils/util";
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
    }
  },
  methods: {
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
