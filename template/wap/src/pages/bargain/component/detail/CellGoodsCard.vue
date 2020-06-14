<template>
  <GoodsCard
    class="goods-card"
    :thumb="items.picture"
    :title="items.title"
    :price="items.price"
    :id="items.id"
  >
    <div slot="tags" class="tags">
      <div class="time-box" v-if="endTime">
        <CountDown :time="endTime" done-text="00:00:00" @callback="onCallback">
          <div class="time-end">
            <span>{%d}</span>
            <i>:</i>
            <span>{%h}</span>
            <i>:</i>
            <span>{%m}</span>
            <i>:</i>
            <span>{%s}</span>
          </div>
        </CountDown>
        <span class="text">砍价将失效</span>
      </div>
    </div>
  </GoodsCard>
</template>
<script>
import GoodsCard from "@/components/GoodsCard";
import CountDown from "@/components/CountDown";
export default {
  props: {
    items: Object,
    loadData: {
      type: Function
    }
  },
  computed: {
    endTime() {
      return this.items.end_time ? parseFloat(this.items.end_time) * 1000 : "";
    }
  },
  methods: {
    onCallback() {
      this.loadData();
    }
  },
  components: {
    GoodsCard,
    CountDown
  }
};
</script>
<style scoped>
.goods-card {
  padding: 10px 15px !important;
}

.time-box {
  display: flex;
  align-items: center;
  height: 20px;
  margin-top: 10px;
}

.time-end {
  margin-right: 6px;
}

.time-end i {
  font-style: normal;
}

.time-end span {
  color: #ffffff;
  background: #ff454e;
  padding: 2px 4px;
  border-radius: 2px;
}
</style>

