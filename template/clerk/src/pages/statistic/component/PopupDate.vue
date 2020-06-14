<template>
  <van-popup
    v-model="value"
    position="bottom"
    :close-on-click-overlay="false"
    @click-overlay="onCancel"
  >
    <van-datetime-picker
      v-model="formatValue"
      title="选择日期"
      type="date"
      :min-date="formatMinDate"
      :max-date="formatMaxDate"
      @confirm="onConfirmDate"
      @cancel="onCancel"
    />
  </van-popup>
</template>

<script>
import { DatetimePicker } from "vant";
export default {
  data() {
    return {};
  },
  props: {
    value: {
      type: Boolean,
      default: false
    },
    date: {
      type: [Number, String]
    },
    minDate: {
      type: [Number, String]
    },
    maxDate: {
      type: [Number, String]
    }
  },
  computed: {
    formatValue: {
      get() {
        const timeStamp = this.date ? this.date : Math.round(new Date() / 1000);
        return this.formatTimeStamp(timeStamp);
      },
      set(e) {}
    },
    formatMinDate() {
      return this.formatTimeStamp(this.minDate);
    },
    formatMaxDate() {
      return this.formatTimeStamp(this.maxDate);
    }
  },
  methods: {
    formatTimeStamp(timeStamp) {
      if (!timeStamp) return undefined;
      let time =
        (timeStamp + "").length === 10
          ? new Date(parseInt(timeStamp) * 1000)
          : new Date(parseInt(timeStamp));
      return new Date(time);
    },
    onConfirmDate(value) {
      const $this = this;
      $this.$emit("confirm", Math.round(value / 1000));
      $this.onCancel();
    },
    onCancel() {
      this.$emit("input", false);
    }
  },
  components: {
    [DatetimePicker.name]: DatetimePicker
  }
};
</script>
<style scoped>
</style>
