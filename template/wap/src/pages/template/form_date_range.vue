<template>
  <van-cell :title="item.label" class="cell-panel" :class="item.required?'van-cell--required':''">
    <div class="date-box">
      <CellDatePopup class="item" label="开始时间" :value="startDate" @confirm="onStartConfirm"/>
      <CellDatePopup class="item" label="结束时间" :value="endDate" @confirm="onEndConfirm"/>
    </div>
  </van-cell>
</template>
<script>
import CellDatePopup from "@/components/CellDatePopup";
export default {
  name: "form-data-range",
  data() {
    return {
      arr:[]
    };
  },
  props: {
    item: {
      type: Object
    }
  },
  computed: {
    startDate() {
      let timeStamp = 0;
      let arr = [];
      let value = this.item.value;
      if (value) {
        arr = value.split(",");
      }
      if (arr[0]) {
        return arr[0];
      }
      if (this.item.start_type == 1) {
        timeStamp = this.item.start_default
          ? Math.round(new Date(this.item.start_default) / 1000)
          : Math.round(new Date() / 1000);
      } else {
        timeStamp = Math.round(new Date() / 1000);
      }
      // arr[0] = timeStamp;
      return timeStamp;
    },
    endDate() {
      let timeStamp = 0;
      let arr = [];
      let value = this.item.value;
      if (value) {
        arr = value.split(",");
      }
      if (arr[1]) {
        return arr[1];
      }
      if (this.item.end_type == 1) {
        timeStamp = this.item.end_default
          ? Math.round(new Date(this.item.end_default) / 1000)
          : Math.round(new Date() / 1000);
      } else {
        timeStamp = Math.round(new Date() / 1000);
      }
      // arr[1] = timeStamp;
      return timeStamp;
    }
  },
  methods: {
    onStartConfirm(value) {
      this.arr[0] = value;
      this.item.value = this.arr.join(",");
    },
    onEndConfirm(value) {
      this.arr[1] = value;
      this.item.value = this.arr.join(",");
    }
  },
  components: {
    CellDatePopup
  }
};
</script>
<style scoped>
.date-box {
  margin: -10px 0;
}
.item {
  text-align: left;
  margin-left: -15px;
}
</style>
