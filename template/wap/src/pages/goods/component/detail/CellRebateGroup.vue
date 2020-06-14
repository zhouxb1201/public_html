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
      <PopupBottom v-model="show" title="商品返利">
        <van-cell-group class="list" :border="false">
          <van-cell class="item" v-for="(item,index) in list" :key="index">
            <div class="title">{{item.title}}</div>
            <div class="name">
              <div v-for="(text,index) in item.value" :key="index">{{text}}</div>
            </div>
          </van-cell>
        </van-cell-group>
      </PopupBottom>
    </div>
  </div>
</template>

<script>
import PopupBottom from "@/components/PopupBottom";
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
      return this.list.map(({ name }) => name);
    },
    list() {
      const data = this.items.data || {};
      let arr = [];
      const { commission } = this.$store.state.member.commissionSetText;
      const { point_style } = this.$store.state.member.memberSetText;
      let commission1 = parseFloat(data.commission1) || 0;
      let commission2 = parseFloat(data.commission2) || 0;
      let commission3 = parseFloat(data.commission3) || 0;
      let commission_point1 = parseFloat(data.commission_point1) || 0;
      let commission_point2 = parseFloat(data.commission_point2) || 0;
      let commission_point3 = parseFloat(data.commission_point3) || 0;
      let is_point = parseFloat(data.is_point) || 0;
      let point = parseFloat(data.point) || 0;
      if (commission1 || commission2 || commission3) {
        let obj = {};
        obj.title = "购物返佣";
        obj.name = "返" + commission;
        obj.value = [];
        if (commission1) {
          let text = "一级购物可得约 " + commission1 + " " + commission;
          if (commission_point1) {
            text += " + " + commission_point1 + " " + point_style;
          }
          obj.value.push(text);
        }
        if (commission2) {
          let text = "二级购物可得约 " + commission2 + " " + commission;
          if (commission_point2) {
            text += " +" + commission_point2 + " " + point_style;
          }
          obj.value.push(text);
        }
        if (commission3) {
          let text = "三级级购物可得约 " + commission3 + " " + commission;
          if (commission_point3) {
            text += " + " + commission_point3 + " " + point_style;
          }
          obj.value.push(text);
        }
        arr.push(obj);
      }
      if (is_point && point) {
        let obj = {};
        obj.title = "购物返利";
        obj.value = ["购买该商品可得约 " + point + point_style];
        obj.name = "返" + point_style;
        arr.push(obj);
      }
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
  height: 100%;
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
</style>