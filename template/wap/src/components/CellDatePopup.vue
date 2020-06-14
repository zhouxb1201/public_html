<template>
  <div>
    <van-field
      :label="label"
      readonly
      :placeholder="placeholder"
      :value="value*1000 | formatDate('YYYY-mm-dd')"
      :class="required?'van-cell--required':''"
      @click="isShow = true"
    />
    <div>
      <van-popup v-model="isShow" position="bottom">
        <van-datetime-picker
          v-model="formatValue"
          title="选择日期"
          type="date"
          :min-date="formatMinDate"
          :max-date="formatMaxDate"
          @confirm="onConfirmDate"
          @cancel="isShow = false"
        />
      </van-popup>
    </div>
  </div>
</template>
<script>
import { DatetimePicker } from "vant";
import {formatDate} from '@/utils/util'
export default {
  data() {
    return {
      formatValue:new Date(this.value*1000||''),
      isShow: false,
      formatMinDate:new Date(this.minDate),
      formatMaxDate:new Date(this.maxDate||new Date().getTime())
    };
  },
  props: {
    label: {
      type: String
    },
    placeholder: {
      type: String,
      default: "请选择日期"
    },
    required: {
      type: Boolean,
      default: false
    },
    value: {
      type: [Number, String]
    },
    minDate: {
      type: [Number, String],
      default: -2209017943000 // 1900开始
    },
    maxDate: {
      type:[Number, String],
      default:new Date().getTime()
    }
  },
  computed: {
    // formatValue: {
    //   get() {
    //     const timeStamp = this.value
    //       ? this.value
    //       : Math.round(new Date() / 1000);
    //     return new Date(this.value);
    //   },
    //   set(e) {}
    // },
    // formatMinDate() {
    //   return new Date(this.minDate);
    // },
    // formatMaxDate() {
    //   return new Date(new Date().getTime());
    // }
  },
  mounted() {
    // console.log(this.value*1000,new Date(this.value*1000),formatDate(Math.round(this.value*1000)))
  },
  methods: {
    onConfirmDate(value) {
      const $this = this;
      // console.log(value,Math.round(value/1000),formatDate(Math.round(value/1000)))
      $this.$emit("confirm", Math.round(value/1000));
      $this.isShow = false;
    }
  },
  components: {
    [DatetimePicker.name]: DatetimePicker
  }
};
</script>
<style scoped>
</style>
