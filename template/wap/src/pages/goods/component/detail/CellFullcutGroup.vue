<template>
  <div v-if="isShow">
    <van-cell is-link @click="show=true">
      <div slot="icon" class="title" :style="{color:titleColor}">{{items.text}}</div>
      <div class="value">
        <van-tag
          class="tag"
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
      <PopupBottom v-model="show" title="满减送">
        <van-cell-group class="list" :border="false">
          <van-cell class="item" v-for="(item,index) in list" :key="index">
            <div class="title">{{item.title}}</div>
            <div class="name">{{item.name}}</div>
            <div class="time">{{item.time}}</div>
          </van-cell>
        </van-cell-group>
      </PopupBottom>
    </div>
  </div>
</template>

<script>
import PopupBottom from "@/components/PopupBottom";
import { formatDate } from "@/utils/util";
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
      this.items.show = !!this.list.length;
      return !!this.list.length;
    },
    cellValue() {
      const data = this.items.data || [];
      let obj = {};
      data.forEach(e => {
        let rules = e.rules.forEach((r, i) => {
          let discount = parseFloat(r.discount);
          let free_shipping = parseInt(r.free_shipping);
          if (discount) {
            obj["jian"] = "满减";
          }
          if (free_shipping) {
            obj["you"] = "满邮";
          }
          if (r.coupon_type_id || r.gift_card_id || r.gift_id) {
            obj["song"] = "满送";
          }
        });
      });
      return obj;
    },
    list() {
      const data = this.items.data || [];
      const arr = data.map(e => {
        let rules = e.rules.map((r, i) => {
          let discount = parseFloat(r.discount);
          let price = parseFloat(r.price);
          let free_shipping = parseInt(r.free_shipping);
          let arrText = [];
          if (discount) {
            arrText.push(`满${price}减${discount}元`);
          }
          if (free_shipping) {
            arrText.push(`满${price}包邮`);
          }
          if (r.coupon_type_id) {
            arrText.push(`满${price}送优惠券(${r.coupon_type_name})`);
          }
          if (r.gift_card_id) {
            arrText.push(`满${price}送礼品券(${r.gift_voucher_name})`);
          }
          if (r.gift_id) {
            arrText.push(`满${price}送赠品(${r.gift_name})`);
          }
          return arrText.join("，");
        });
        let arrName = [];
        rules.forEach(r => arrName.push(r));
        e.title = e.mansong_name;
        e.name = arrName.join("；");
        e.time =
          "使用期限 " +
          formatDate(e.start_time) +
          " ~ " +
          formatDate(e.end_time);
        return e;
      });
      return arr;
    }
  },
  components: {
    PopupBottom
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

.item .title {
  width: auto;
  font-size: 14px;
  color: #ff454e;
}

.item .name {
  color: #606266;
  font-size: 12px;
  line-height: 1.4;
}

.item .time {
  font-size: 10px;
  color: #909399;
  margin-top: 4px;
  line-height: 1.2;
}
</style>