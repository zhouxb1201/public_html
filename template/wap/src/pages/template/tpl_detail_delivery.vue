<template>
  <div :class="item.id" :style="viewStyle" v-if="isShow">
    <van-cell is-link @click="click">
      <div slot="icon" class="title" :style="{ color: item.style.titlecolor }">
        配送
      </div>
      <div class="value" :style="{ color: valueColor }">{{ valueText }}</div>
      <div slot="right-icon" class="right-box">
        <span class="a-link fs-12">切换</span>
        <van-icon name="arrow" class="van-cell__right-icon" />
      </div>
    </van-cell>
    <div>
      <PopupDeliveryGroup
        v-model="show"
        @select="select"
        :params="item.params"
      />
    </div>
  </div>
</template>

<script>
import PopupDeliveryGroup from "../goods/component/detail/PopupDeliveryGroup";
export default {
  name: "tpl_detail_delivery",
  data() {
    return {
      show: false
    };
  },
  props: {
    type: [String, Number],
    item: Object
  },
  computed: {
    isShow() {
      return !!this.$store.getters.token && this.item.params.show;
    },
    viewStyle() {
      return {
        marginTop: this.item.style.margintop + "px",
        marginBottom: this.item.style.marginbottom + "px"
      };
    },
    valueText() {
      return this.item.params.info.address || "请选择配送地址";
    },
    valueColor() {
      return this.item.params.isCurrent
        ? this.item.style.currentcolor
        : this.item.style.nocurrentcolor;
    }
  },
  methods: {
    click() {
      this.show = true;
    },
    select(item) {
      this.$emit("event", "delivery", item);
      this.item.params.info = item;
      this.item.params.isCurrent = true;
      this.show = false;
    }
  },
  components: {
    PopupDeliveryGroup
  }
};
</script>

<style scoped>
.title {
  width: 50px;
  color: #606266;
}

.value {
  color: #909399;
}

.right-box {
  display: flex;
  align-items: center;
}
</style>
