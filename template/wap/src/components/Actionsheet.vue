<template>
  <van-popup
    v-model="value"
    position="bottom"
    :close-on-click-overlay="false"
    @click-overlay="onCancel"
  >
    <div class="bg-f8">
      <van-cell-group class="cell-group">
        <van-cell
          clickable
          v-for="(item,index) in actions"
          :key="index"
          :value="item.name"
          value-class="text-center"
          @click="$emit('select', item)"
        />
      </van-cell-group>
      <div class="foot-btn-group">
        <van-button size="normal" block round type="default" @click="onCancel">关闭</van-button>
      </div>
    </div>
  </van-popup>
</template>

<script>
import { Actionsheet } from "vant";
export default {
  data() {
    return {};
  },
  props: {
    value: {
      type: Boolean,
      default: false
    },
    actions: Array
  },
  methods: {
    onCancel() {
      this.$emit("input", false);
    }
  },
  components: {
    [Actionsheet.name]: Actionsheet
  }
};
</script>

<style scoped>
.cell-group {
  margin-bottom: 10px;
}
</style>
