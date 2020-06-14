<template>
  <PopupBottom v-model="show" :title="title">
    <van-cell-group class="list" :border="false">
      <CellCouponCard v-for="(item,index) in list" :key="index" :item="item" @click="click" />
    </van-cell-group>
  </PopupBottom>
</template>

<script>
import PopupBottom from "./PopupBottom";
import CellCouponCard from "./CellCouponCard";
import { formatDate } from "@/utils/util";
import { yuan } from "@/utils/filter";
import { RECEIVE_COUPON } from "@/api/coupon";
export default {
  data() {
    return {};
  },
  props: {
    items: {
      type: Array
    },
    value: {
      type: Boolean,
      default: false
    },
    title: {
      type: String,
      default: "优惠券"
    },
    /**
     * 获取类型
     * 1 ==> 订单获取
     * 2 ==> 首页获取
     * 3 ==> 注册营销获取
     * 4 ==> 购物车获取
     * 5 ==> 商品详情获取
     */
    getType: {
      type: [String, Number],
      required: true
    }
  },
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
      const items = this.items || [];
      items.forEach(e => {
        var money = parseFloat(e.money),
          at_least = parseFloat(e.at_least),
          discount = parseFloat(e.discount);
        e.type = e.coupon_genre;
        e.loading = !!e.loading;
        e.disabled = !!e.selected;
        e.title = "";
        e.nameLabel = e.shop_id == 0 ? "平台优惠券" : "店铺优惠券";
        e.btnText = this.getType == 1 ? "使用" : "立即领取";
        if (e.coupon_genre == 1) {
          e.title = yuan(money);
          e.label = "无门槛";
        } else if (e.coupon_genre == 2) {
          e.title = yuan(money);
          e.label = "满" + at_least + "元减" + money + "元";
        } else if (e.coupon_genre == 3) {
          e.title = discount + "折";
          e.label = "满" + at_least + "元" + discount + "折";
        }
        e.timeText =
          "有期限 " + formatDate(e.start_time) + " ~ " + formatDate(e.end_time);
      });
      return items;
    }
  },
  methods: {
    click(item) {
      const $this = this;
      if ($this.getType == 1) {
        if (item.selected) {
          $this.$emit("cancel", item);
        } else {
          $this.$emit("use", item);
        }
      } else {
        const params = {};
        params.coupon_type_id = item.coupon_type_id;
        params.get_type = $this.getType;
        item.loading = true;
        RECEIVE_COUPON(params)
          .then(res => {
            $this.$Toast.success("领取成功");
            item.loading = false;
            $this.close();
          })
          .catch(() => {
            item.loading = false;
          });
      }
    },
    close() {
      this.$emit("input", false);
    }
  },
  components: {
    PopupBottom,
    CellCouponCard
  }
};
</script>

<style scoped>
</style>