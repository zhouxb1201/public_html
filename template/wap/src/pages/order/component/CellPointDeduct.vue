<template>
  <van-cell-group v-if="isPointDeduct">
    <van-cell center>
      <van-checkbox
        v-model="checked"
        :disabled="isDisabled"
        :label-disabled="isDisabled"
        name="point"
        shape="square"
        slot="icon"
        class="flex-auto-center"
        @change="onChange"
      >{{$store.state.member.memberSetText.point_style}}</van-checkbox>
      <div class="text">共{{info.point}}{{$store.state.member.memberSetText.point_style}}，可使用{{info.total_deduction_point}}个</div>
      <div slot="right-icon" class="money">- {{pointDeductMoney | yuan}}</div>
    </van-cell>
  </van-cell-group>
</template>

<script>
export default {
  data() {
    return {
      checked: false
    };
  },
  props: {
    isPointDeduct: Boolean,
    info: Object
  },
  watch: {
    "info.total_deduction_money"(e) {
      if (parseFloat(e) <= 0) {
        this.checked = false;
      }
    }
  },
  computed: {
    isDisabled() {
      if (
        this.isPointDeduct &&
        parseFloat(this.info.total_deduction_money) > 0
      ) {
        return false;
      } else {
        return true;
      }
    },
    pointDeductMoney() {
      return this.checked ? this.info.total_deduction_money : 0;
    }
  },
  methods: {
    onChange(e) {
      this.$emit("load-data", e);
    }
  }
};
</script>

<style scoped>
.text {
  font-size: 12px;
  color: #909399;
  padding: 0 10px;
  line-height: 14px;
}

.money {
  font-size: 12px;
  color: #ff454e;
}
</style>

