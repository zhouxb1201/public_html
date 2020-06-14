<template>
  <van-cell class="item" :to="{name:'goods-detail',params:{goodsid:items.goods_id}}">
    <GoodsCard :thumb="items.pic_cover_url" :title="items.goods_name">
      <div slot="tags" class="tags">
        <div class="time-box">
          <span class="text" :class="items.status == 1 ? 'ing' : 'not'">{{items.status | itemText}}</span>
          <span class="time">{{items.status == 1 ? items.end_bargain_date : items.start_bargain_date}}</span>
        </div>
      </div>
      <div slot="bottom" class="text-maintone">初始价：{{items.start_money | yuan}}</div>
    </GoodsCard>
  </van-cell>
</template>
<script>
import GoodsCard from "@/components/GoodsCard";
export default {
  props: {
    items: Object
  },
  filters: {
    itemText(value) {
      return value == 1 ? "距结束" : "距开始";
    }
  },
  components: {
    GoodsCard
  }
};
</script>
<style scoped>
.tags {
  margin-top: 10px;
}

.time-box {
  border-radius: 4px;
  background: #f1f1f1;
  display: flex;
  align-items: center;
  overflow: hidden;
}

.time-box .text {
  color: #fff;
  padding: 0 6px;
}

.time-box .text.ing {
  background: #ff454e;
}

.time-box .text.not {
  background: #07c160;
}

.time-box .time {
  padding: 0 6px;
}
</style>

