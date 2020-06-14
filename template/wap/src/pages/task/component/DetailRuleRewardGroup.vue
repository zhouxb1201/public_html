<template>
  <div>
    <DetailCellGroup title="任务规则">
      <van-cell v-for="(item,rule) in formatRuleItem(items.task_rule)" :key="rule" class="cell">
        <div class="cell-item" :class="item.finish?'positive':'negative'">
          ● {{item.name}}
          <span class="cell-value">{{item.value}}</span>
          {{item.unit}}
        </div>
      </van-cell>
    </DetailCellGroup>
    <DetailCellGroup title="任务奖励">
      <van-cell
        v-for="(item,reward) in formatRewardItem(items.task_reward)"
        :key="reward"
        class="cell"
      >
        <div class="cell-item text-secondary">{{reward+1}}、{{item.value}} {{item.text}}</div>
      </van-cell>
    </DetailCellGroup>
  </div>
</template>

<script>
import DetailCellGroup from "./DetailCellGroup";
export default {
  props: {
    items: Object
  },
  computed: {},
  mounted() {
    this.formatRuleItem(this.items.task_rule);
    this.formatRewardItem(this.items.task_reward);
  },
  methods: {
    formatRuleItem(rules) {
      let arr = [];
      rules.forEach(item => {
        for (let key in item) {
          const value = item[key];
          if (key != "is_complete" && key != "goods_name" && value) {
            let obj = {};
            obj.value = value;
            obj.finish = item["is_complete"];
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
    },
    formatRewardItem(item) {
      const {
        balance_style,
        point_style
      } = this.$store.state.member.memberSetText;
      let arr = [];
      for (let key in item) {
        const value = item[key];
        if (key != "gift_voucher_name" && key != "coupon_name" && value) {
          let obj = {};
          obj.value = value;
          switch (key) {
            case "point":
              obj.text = "个" + point_style;
              break;
            case "balance":
              obj.text = "元" + balance_style;
              break;
            case "wchat_red_packet":
              obj.text = "元微信红包";
              break;
            case "growth":
              obj.text = "成长值";
              break;
            case "gift_voucher_id":
              obj.text = "礼品券";
              obj.value = item["gift_voucher_name"];
              break;
            case "coupon_type_id":
              obj.text = "优惠券";
              obj.value = item["coupon_name"];
              break;
          }
          arr.push(obj);
        }
      }
      return arr;
    }
  },
  components: {
    DetailCellGroup
  }
};
</script>

<style scoped>
.cell {
  padding: 5px 0;
}

.positive {
  color: #4b0;
}

.negative {
  color: #ff454e;
}

.cell-value {
  padding: 0 4px;
}
</style>
