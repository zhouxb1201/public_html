<template>
  <div v-if="isShow">
    <van-cell is-link @click="show=true">
      <div slot="icon" class="title" :style="{color:titleColor}">{{items.text}}</div>
      <div class="value">
        <van-tag
          class="tag coupon-tag"
          round
          size="medium"
          color="#FAE9E6"
          text-color="#ff454e"
          v-for="(value,t) in cellValue"
          :key="t"
        >{{value}}</van-tag>
      </div>
    </van-cell>
    <div>
      <PopupCoupon v-model="show" get-type="5" :items="items.data||[]"></PopupCoupon>
    </div>
  </div>
</template>

<script>
import PopupCoupon from "@/components/PopupCoupon";
import { yuan } from "@/utils/filter";
export default {
  data() {
    return {
      show: false
    };
  },
  props: {
    titleColor: {
      type: String,
      default: "#606266"
    },
    items: [Object, Array]
  },
  computed: {
    isShow() {
      this.items.show = !!this.cellValue.length;
      return !!this.cellValue.length;
    },
    cellValue() {
      const data = this.items.data || [];
      let arr = [];
      data.forEach((e, i) => {
        if (i < 3) {
          var money = parseFloat(e.money),
            at_least = parseFloat(e.at_least),
            discount = parseFloat(e.discount);
          let value = "";
          if (e.coupon_genre == 1) {
            value = yuan(money);
          } else if (e.coupon_genre == 2) {
            value = yuan(money);
          } else if (e.coupon_genre == 3) {
            value = discount + "折";
          }
          value && (value = " | " + value);
          arr.push("优惠券" + value);
        }
      });
      return arr;
    }
  },
  components: {
    PopupCoupon
  }
};
</script>

<style scoped>
.title {
  width: 50px;
  color: #606266;
}

.value {
  display: flex;
  align-items: center;
  height: 24px;
  overflow: hidden;
}

.value .tag {
  white-space: nowrap;
  margin-right: 5px;
}

.value .tag.coupon-tag {
  display: block;
  position: relative;
  border-radius: 0;
  padding: 0.2em 1em;
  overflow: hidden;
}

.value .tag.coupon-tag::before,
.value .tag.coupon-tag::after {
  content: "";
  background: #fff;
  position: absolute;
  top: 50%;
  margin-top: -5px;
  width: 10px;
  height: 10px;
  border-radius: 100%;
}

.value .tag.coupon-tag::before {
  left: -5px;
}

.value .tag.coupon-tag::after {
  right: -5px;
}
</style>