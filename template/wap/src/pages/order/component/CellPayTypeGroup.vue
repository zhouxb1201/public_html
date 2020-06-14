<template>
  <van-cell-group class="cell-group">
    <van-cell :title="title" is-link :value="text" @click="show=true" />
    <PopupBottom v-model="show" :title="title">
      <van-radio-group v-model="value">
        <van-cell-group>
          <van-cell
            v-for="(item,index) in items"
            :key="index"
            center
            :title="item.name"
            :label="item.label"
            clickable
            @click="onSelect(item.type)"
          >
            <van-radio slot="right-icon" :name="item.type" />
          </van-cell>
        </van-cell-group>
      </van-radio-group>
    </PopupBottom>
  </van-cell-group>
</template>

<script>
import PopupBottom from "@/components/PopupBottom";
export default {
  data() {
    return {
      title: "支付方式",
      show: false,
      items: [
        { name: "在线支付", label: "支持余额、微信、支付宝、银行卡", type: 1 },
        { name: "货到付款", label: "收货时线下付款，更便捷", type: 0 }
      ]
    };
  },
  props: {
    value: {
      type: Number,
      default: 1
    }
  },
  computed: {
    text() {
      const { name } = this.items.filter(({ type }) => type == this.value)[0];
      return name;
    }
  },
  methods: {
    onClose() {
      this.show = false;
    },
    onSelect(type) {
      this.$emit("input", type);
      this.onClose();
    }
  },
  components: {
    PopupBottom
  }
};
</script>

<style scoped>
.cell-group {
  margin: 10px 0;
}
</style>