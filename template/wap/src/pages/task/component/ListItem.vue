<template>
  <van-cell value-class="item">
    <div class="text-group">
      <div class="name">{{items.task_name}}</div>
      <div class="rule">
        <div class="text" v-for="(item,index) in ruleItem" :key="index">
          {{item.name}}
          <router-link
            class="text-maintone"
            v-if="item.goods_id"
            tag="span"
            :to="'/goods/detail/'+item.goods_id"
          >{{item.value}}</router-link>
          <span class="text-maintone" v-else>{{item.value}}</span>
          {{item.unit}}
        </div>
      </div>
      <router-link :to="toDetail(items)" tag="span" class="a-link">任务详情 ></router-link>
    </div>
    <div class="right-group">
      <slot name="right" />
    </div>
  </van-cell>
</template>

<script>
export default {
  data() {
    return {};
  },
  props: {
    items: Object
  },
  computed: {
    ruleItem() {
      let arr = [];
      this.items.task_rule.forEach(item => {
        for (let key in item) {
          const value = item[key];
          if (key != "goods_name" && value) {
            let obj = {};
            obj.value = value;
            switch (key) {
              case "referrals":
                obj.name = "推荐人数达";
                obj.unit = "人";
                break;
              case "distribution_commission":
                obj.name = "分销佣金达";
                obj.unit = "元";
                break;
              case "distribution_orders":
                obj.name = "分销订单达";
                obj.unit = "笔";
                break;
              case "pay_order_total_num":
                obj.name = "支付订单达";
                obj.unit = "笔";
                break;
              case "order_total_money":
                obj.name = "订单满额";
                obj.unit = "元";
                break;
              case "order_total_sum":
                obj.name = "订单累计";
                obj.unit = "元";
                break;
              case "goods_comment_num":
                obj.name = "累计评价";
                obj.unit = "次";
                break;
              case "total_recharge":
                obj.name = "累计充值达";
                obj.unit = "元";
                break;
              case "single_recharge":
                obj.name = "单次充值满";
                obj.unit = "元";
                break;
              case "goods_id":
                obj.name = "购买";
                obj.goods_id = value;
                obj.value = item["goods_name"];
                obj.unit = "商品";
                break;
            }
            arr.push(obj);
          }
        }
      });
      return arr;
    }
  },
  methods: {
    toDetail({ general_poster_id, user_task_id }) {
      const route = {
        name: "task-detail",
        params: {
          id: general_poster_id
        }
      };
      if (user_task_id) {
        route.query = {
          user_task_id
        };
      }
      return route;
    }
  }
};
</script>

<style scoped>
.item {
  display: flex;
  justify-content: space-between;
}

.item .text-group {
  width: 70%;
}

.item .name {
  font-weight: 800;
}

.item .text {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.6;
  color: #606266;
  display: flex;
}

.item .text span {
  padding: 0 4px;
  max-width: 160px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  vertical-align: middle;
}

.item .right-group {
  width: 30%;
  text-align: center;
}
</style>
