<template>
  <div>
    <van-field
      :label="label"
      readonly
      :placeholder="placeholder"
      :value="value"
      :class="required?'van-cell--required':''"
      @click="isShow = true"
    />
    <div>
      <van-popup v-model="isShow" position="bottom">
        <van-picker
          :columns="columns"
          @confirm="onConfirm"
          @cancel="isShow = false"
          show-toolbar
          :title="popupTitle"
        />
      </van-popup>
    </div>
  </div>
</template>
<script>
import { Picker } from "vant";
export default {
  data() {
    return {
      value: "",
      isShow: false
    };
  },
  props: {
    columns: {
      type: Array,
      default: [],
      required: true
    },
    label: {
      type: String
    },
    placeholder: {
      type: String
    },
    popupTitle: {
      type: String
    },
    required: {
      type: Boolean,
      default: false
    }
  },
  methods: {
    onConfirm(value, index) {
      this.isShow = false;
      this.value = typeof value == "string" ? value : value.text;
      this.$emit("confirm", value);
    }
  },
  components: {
    [Picker.name]: Picker
  }
};
</script>
<style scoped>
</style>
